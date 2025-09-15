<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Ollama;

use Ai\Domain\Completion\MessageServiceInterface;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\Entities\MessageEntity;
use Ai\Domain\ValueObjects\Call;
use Ai\Domain\ValueObjects\Chunk;
use Ai\Domain\ValueObjects\Quote;
use Ai\Infrastructure\Services\CostCalculator;
use Ai\Infrastructure\Services\Tools\CallException;
use Ai\Infrastructure\Services\Tools\KnowledgeBase;
use Ai\Infrastructure\Services\Tools\ToolCollection;
use Billing\Domain\ValueObjects\CreditCount;
use File\Infrastructure\FileService;
use Generator;
use Gioni06\Gpt3Tokenizer\Gpt3Tokenizer;
use Override;
use Shared\Infrastructure\Services\ModelRegistry;
use Throwable;
use Traversable;

class MessageService implements MessageServiceInterface
{
    /** @var string[] */
    private array $models = [];

    public function __construct(
        private Client $client,
        private Gpt3Tokenizer $tokenizer,
        private CostCalculator $calc,
        private FileService $fs,
        private ToolCollection $tools,
        private ModelRegistry $registry,

    ) {
        // Find the Ollama provider in the directory array
        foreach ($this->registry['directory'] as $provider) {
            if (($provider['key'] ?? null) === 'ollama') {
                foreach ($provider['models'] ?? [] as $model) {
                    $this->models[] = trim($model['key']);
                }

                break;
            }
        }
    }

    #[Override]
    public function supportsModel(Model $model): bool
    {
        return in_array($model->value, $this->models);
    }

    #[Override]
    public function getSupportedModels(): Traversable
    {
        foreach ($this->models as $model) {
            yield new Model($model);
        }
    }

    #[Override]
    public function generateMessage(
        Model $model,
        MessageEntity $message
    ): Generator {
        $inputTokensCount = 0;
        $outputTokensCount = 0;
        $toolCost = new CreditCount(0);
        $files = [];

        $messages = $this->buildMessageHistory(
            $message,
            128000,
            $files
        );

        $body = [
            // Remove the ollama/ prefix as this prefix added to identify the provider
            'model' => preg_replace('/^ollama\//', '', trim($model->value)),
            'messages' => $messages,
            'stream' => true
        ];

        $foundModel = array_values(
            array_filter($this->models, fn($m) => $m == $model->value)
        );

        if (($foundModel[0]['tools'] ?? false)) {
            $tools = $this->getTools($message);
            if ($tools) {
                $body['tools'] = $tools;
                $body['stream'] = false;
            }
        }

        $resp = $this->client->sendRequest('POST', '/api/chat', $body);
        $stream = new StreamResponse($resp);

        $calls = [];
        foreach ($stream as $data) {
            if (isset($data->prompt_eval_count)) {
                $inputTokensCount += $data->prompt_eval_count ?? 0;
            }

            if (isset($data->eval_count)) {
                $outputTokensCount += $data->eval_count ?? 0;
            }

            $msg = $data->message ?? null;

            if (!$msg) {
                continue;
            }

            yield new Chunk($msg->content);

            if (isset($msg->tool_calls)) {
                $calls = array_merge($calls, $msg->tool_calls);
            }
        }

        if ($calls) {
            // $body['messages'] = []; // Clear messages
            $body['messages'][] = [
                'role' => 'assistant',
                'content' => "",
                'tool_calls' => $calls
            ];
        }

        $callAgain = false;

        $embeddings = [];
        if ($message->getAssistant()?->hasDataset()) {
            foreach ($message->getAssistant()->getDataset() as $unit) {
                $embeddings[] = $unit->getEmbedding();
            }
        }

        foreach ($calls as $call) {
            $tool = $this->tools->find($call->function->name);

            if (!$tool) {
                continue;
            }

            $arguments = (array) $call->function->arguments;
            yield new Chunk(new Call($call->function->name, $arguments));

            try {
                $cr = $tool->call(
                    $message->getConversation()->getUser(),
                    $message->getConversation()->getWorkspace(),
                    $arguments,
                    $files,
                    $embeddings
                );

                $toolCost =  new CreditCount($cr->cost->value + $toolCost->value);

                if ($cr->item) {
                    yield new Chunk($cr->item);
                }

                $content = $cr->content;
            } catch (CallException $th) {
                $content = $th->getMessage();
            }

            $body['messages'][] = [
                'role' => 'tool',
                'content' => $content
            ];

            $callAgain = true;
        }

        if ($callAgain) {
            $body['stream'] = true;
            $resp = $this->client->sendRequest('POST', '/api/chat', $body);
            $stream = new StreamResponse($resp);

            foreach ($stream as $data) {
                if (isset($data->prompt_eval_count)) {
                    $inputTokensCount += $data->prompt_eval_count ?? 0;
                }

                if (isset($data->eval_count)) {
                    $outputTokensCount += $data->eval_count ?? 0;
                }

                $message = $data->message ?? null;

                if (!$message) {
                    continue;
                }

                yield new Chunk($message->content);
            }
        }

        $inputCost = $this->calc->calculate(
            $inputTokensCount,
            $model,
            CostCalculator::INPUT
        );

        $outputCost = $this->calc->calculate(
            $outputTokensCount,
            $model,
            CostCalculator::OUTPUT
        );

        return new CreditCount($inputCost->value + $outputCost->value + $toolCost->value);
    }

    private function buildMessageHistory(
        MessageEntity $message,
        int $maxContextTokens,
        array &$files = [],
        int $maxMessages = 20,
        int $maxImages = 2
    ): array {
        $messages = [];
        $current = $message;
        $inputTokensCount = 0;

        $imageCount = 0;
        while (true) {
            $file = $current->getFile();
            if ($file) {
                $files[] = $file;
            }

            if ($current->getContent()->value) {
                $tokens = 0;
                $img = $current->getImage();
                $images = [];

                if (
                    $current->getRole()->value == 'user'
                    && $img
                    && $imageCount < $maxImages
                ) {
                    try {
                        $imgContent = $this->fs->getFileContents($img);
                        $images[] = base64_encode($imgContent);

                        $imageCount++;
                    } catch (Throwable $th) {
                        // Unable to load image
                    }
                }

                $tokens += $this->tokenizer->count($current->getContent()->value);

                if ($tokens + $inputTokensCount > $maxContextTokens) {
                    break;
                }

                $inputTokensCount += $tokens;

                $msg = [
                    'role' => $current->getRole()->value,
                    'content' => $current->getContent()->value
                ];

                if ($images) {
                    $msg['images'] = $images;
                }

                array_unshift($messages, $msg);

                if ($current->getQuote()->value) {
                    array_unshift(
                        $messages,
                        $this->generateQuoteMessage($current->getQuote())
                    );
                }
            }

            if (count($messages) >= $maxMessages) {
                break;
            }

            if ($current->getParent()) {
                $current = $current->getParent();
                continue;
            }

            break;
        }

        $assistant = $message->getAssistant();
        if ($assistant) {
            if ($assistant->getInstructions()->value) {
                array_unshift($messages, [
                    'role' => 'system',
                    'content' => $assistant->getInstructions()->value
                ]);
            }
        }

        if ($files) {
            $messages[] = [
                'role' => 'system',
                'content' => 'The user has uploaded some files.'
            ];
        }

        if ($message->getAssistant()?->hasDataset()) {
            $messages[] = [
                'role' => 'system',
                'content' => 'Knowledge base is available. Use the ' . KnowledgeBase::LOOKUP_KEY . ' tool to access the knowledge base.'
            ];
        }

        if ($messages && end($messages)['role'] === 'system') {
            $messages[] = [
                'role' => 'assistant',
                'content' => ''
            ];
        }

        return $messages;
    }

    private function generateQuoteMessage(Quote $quote): array
    {
        return [
            'role' => 'system',
            'content' => 'The user is referring to this in particular:\n' . $quote->value
        ];
    }

    private function getTools(MessageEntity $message): array
    {
        $tools = [];

        foreach ($this->tools->getToolsForMessage($message) as $key => $tool) {
            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => $key,
                    'description' => $tool->getDescription(),
                    'parameters' => $tool->getDefinitions()
                ]
            ];
        }

        return $tools;
    }
}
