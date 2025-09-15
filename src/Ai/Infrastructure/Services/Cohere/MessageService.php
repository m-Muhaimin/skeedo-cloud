<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Cohere;

use Ai\Domain\Completion\MessageServiceInterface;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\Entities\MessageEntity;
use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\ValueObjects\Call;
use Ai\Domain\ValueObjects\Chunk;
use Ai\Domain\ValueObjects\Quote;
use Ai\Domain\ValueObjects\ReasoningToken;
use Ai\Infrastructure\Services\CostCalculator;
use Ai\Infrastructure\Services\Tools\CallException;
use Ai\Infrastructure\Services\Tools\KnowledgeBase;
use Ai\Infrastructure\Services\Tools\ToolCollection;
use Billing\Domain\ValueObjects\CreditCount;
use File\Infrastructure\FileService;
use Generator;
use Override;
use Throwable;
use Traversable;

class MessageService implements MessageServiceInterface
{
    private array $models = [
        'c4ai-aya-vision-8b',
        'c4ai-aya-vision-32b',
        'c4ai-aya-expanse-8b',
        'c4ai-aya-expanse-32b',
        'command-a-03-2025',
        'command-r-plus',
        'command-r',
        'command-r7b-12-2024',
        'command',
        'command-light',
    ];

    public function __construct(
        private Client $client,
        private CostCalculator $calc,
        private FileService $fs,
        private ToolCollection $tools,
    ) {}

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

        $messages = $this->buildMessageHistory($message, $files);

        $body = [
            'stream' => true,
            'model' => $model->value,
            'messages' => $messages,
        ];

        $supported = [
            'command-a-03-2025',
            'command-r-plus',
            'command-r',
            'command-r7b-12-2024',
            'command',
        ];

        if (in_array($model->value, $supported)) {
            $tools = $this->getTools($message);

            if ($tools) {
                $body['tools'] = $tools;
            }
        }

        $embeddings = [];
        if ($message->getAssistant()?->hasDataset()) {
            foreach ($message->getAssistant()->getDataset() as $unit) {
                $embeddings[] = $unit->getEmbedding();
            }
        }

        while (true) {
            $resp = $this->client->sendRequest('POST', '/chat', $body);
            $stream = new StreamResponse($resp);

            $calls = [];
            $content = '';

            foreach ($stream as $data) {
                $type = $data->type ?? null;

                if ($type == 'content-start' || $type == 'content-delta') {
                    $chunk = $data->delta->message->content->text;
                    $content .= $chunk;
                    yield new Chunk($chunk);
                    continue;
                }

                if ($type == 'message-end') {
                    $inputTokensCount += $data->delta->usage->billed_units->input_tokens ?? 0;
                    $outputTokensCount += $data->delta->usage->billed_units->output_tokens ?? 0;
                    continue;
                }

                if ($type == 'tool-call-start') {
                    $calls[$data->index] = $data->delta->message->tool_calls;
                    continue;
                }

                if ($type == 'tool-plan-delta') {
                    $token = new ReasoningToken($data->delta->message->tool_plan);
                    yield new Chunk($token);
                    continue;
                }

                if ($type == 'tool-call-delta') {
                    $calls[$data->index]->function->arguments .= $data->delta->message->tool_calls->function->arguments;
                    continue;
                }
            }

            if (!$calls) {
                break;
            }

            $body['messages'][] = [
                'role' => 'assistant',
                'content' => $content,
                'tool_calls' => $calls
            ];

            foreach ($calls as $call) {
                $tool = $this->tools->find($call->function->name);

                if (!$tool) {
                    continue;
                }

                $arguments = json_decode($call->function->arguments, true);
                yield new Chunk(new Call($call->function->name, $arguments));

                try {
                    $cr = $tool->call(
                        $message->getConversation()->getUser(),
                        $message->getConversation()->getWorkspace(),
                        $arguments,
                        $files,
                        $embeddings
                    );

                    $toolCost = new CreditCount($cr->cost->value + $toolCost->value);

                    if ($cr->item) {
                        yield new Chunk($cr->item);
                    }

                    $content = $cr->content;
                } catch (CallException $th) {
                    $content = $th->getMessage();
                }

                $body['messages'][] = [
                    'role' => 'tool',
                    'tool_call_id' => $call->id,
                    'content' => $content,
                ];
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
        array &$files = [],
        int $maxMessages = 20,
        int $maxImages = 2
    ): array {
        $model = $message->getModel();
        $messages = [];
        $current = $message;
        $imageModels = [
            'c4ai-aya-vision-8b',
            'c4ai-aya-vision-32b',
            'c4ai-aya-expanse-8b',
            'c4ai-aya-expanse-32b',
        ];

        $imageCount = 0;
        while (true) {
            $file = $current->getFile();
            if ($file) {
                $files[] = $file;
            }

            if ($current->getContent()->value) {
                if ($current->getQuote()->value) {
                    array_unshift(
                        $messages,
                        $this->generateQuoteMessage($current->getQuote())
                    );
                }

                $content = [];
                $img = $current->getImage();

                if (
                    $current->getRole()->value == 'user'
                    && in_array($model->value, $imageModels)
                    && $img
                    && $imageCount < $maxImages
                ) {
                    try {
                        $imgContent = $this->fs->getFileContents($img);

                        $content[] = [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => 'data:'
                                    . 'image/' .  $img->getExtension()
                                    . ';base64,'
                                    . base64_encode($imgContent)
                            ]
                        ];

                        $imageCount++;
                    } catch (Throwable $th) {
                        // Unable to load image
                    }
                }

                $content[] = [
                    'type' => 'text',
                    'text' => $current->getContent()->value
                ];

                array_unshift($messages, [
                    'role' => $current->getRole()->value,
                    'content' => $content
                ]);
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
