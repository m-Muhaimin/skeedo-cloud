<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\xAi;

use Ai\Domain\Completion\MessageServiceInterface;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\Entities\MessageEntity;
use Ai\Domain\ValueObjects\Call;
use Ai\Domain\ValueObjects\Chunk;
use Ai\Domain\ValueObjects\Quote;
use Ai\Domain\ValueObjects\ReasoningToken;
use Ai\Infrastructure\Services\AbstractBaseService;
use Ai\Infrastructure\Services\CostCalculator;
use Ai\Infrastructure\Services\Tools\CallException;
use Ai\Infrastructure\Services\Tools\KnowledgeBase;
use Ai\Infrastructure\Services\Tools\ToolCollection;
use Billing\Domain\ValueObjects\CreditCount;
use File\Infrastructure\FileService;
use Generator;
use Override;
use Psr\Log\LoggerInterface;
use Shared\Infrastructure\Services\ModelRegistry;
use Throwable;

class MessageService extends AbstractBaseService implements
    MessageServiceInterface
{
    public function __construct(
        private Client $client,
        private CostCalculator $calc,
        private FileService $fs,
        private ToolCollection $tools,
        private ModelRegistry $registry,
        private LoggerInterface $logger
    ) {
        parent::__construct($registry, 'xai', 'llm');
    }

    #[Override]
    public function generateMessage(
        Model $model,
        MessageEntity $message
    ): Generator {
        $inTokens = 0;
        $outTokens = 0;
        $imgTokens = 0;

        $toolCost = new CreditCount(0);
        $files = [];

        $messages = $this->buildMessageHistory(
            $model,
            $message,
            131072,
            $files
        );

        $body = [
            'messages' => $messages,
            'model' => $model->value,
            'stream' => true
        ];

        $tools = $this->getTools($message);
        if ($tools) {
            $body['tools'] = $tools;
            $body['tool_choice'] = 'auto';
        }

        while (true) {
            $resp = $this->client->sendRequest('POST', '/v1/chat/completions', $body);
            $stream = new StreamResponse($resp);

            $calls = [];
            $content = '';

            foreach ($stream as $data) {
                $this->logger->info('xAI stream data', ['data' => $data]);
                $inTokens = $data->usage->prompt_tokens_details->text_tokens ?? $inTokens;
                $outTokens = $data->usage->completion_tokens ?? $outTokens;
                $imgTokens = $data->usage->prompt_tokens_details->image_tokens ?? $imgTokens;

                $choice = $data->choices[0] ?? null;

                if (!$choice) {
                    continue;
                }

                if (isset($choice->delta->content)) {
                    $chunk = $choice->delta->content;
                    $content .= $chunk;
                    yield new Chunk($chunk);
                }

                if (isset($choice->delta->reasoning_content)) {
                    $chunk = $choice->delta->reasoning_content;
                    if ($chunk != 'Thinking... ') {
                        yield new Chunk(new ReasoningToken($chunk));
                    } else {
                        yield new Chunk(new ReasoningToken(''));
                    }
                }

                if (isset($choice->delta->tool_calls)) {
                    $calls = array_merge($calls, $choice->delta->tool_calls);
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
                    'content' => $content,
                    'tool_call_id' => $call->id
                ];
            }
        }

        $inputCost = $this->calc->calculate(
            $inTokens,
            $model,
            CostCalculator::INPUT
        );

        $outputCost = $this->calc->calculate(
            $outTokens,
            $model,
            CostCalculator::OUTPUT
        );

        $imgCost = $this->calc->calculate(
            $imgTokens,
            $model,
            CostCalculator::IMAGE
        );

        return new CreditCount($inputCost->value + $outputCost->value + $toolCost->value + $imgCost->value);
    }

    private function buildMessageHistory(
        Model $model,
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
                if ($current->getQuote()->value) {
                    array_unshift(
                        $messages,
                        $this->generateQuoteMessage($current->getQuote())
                    );
                }

                $content = [];
                $tokens = 0;
                $img = $current->getImage();

                if (
                    $current->getRole()->value == 'user'
                    && $img
                    && $imageCount < $maxImages
                    && str_contains($model->value, 'vision')
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

                // Rough estimate of tokens. 0.75 is the average number of words per token
                $tokens += count(explode($current->getContent()->value, ' ')) / 0.75;

                if ($tokens + $inputTokensCount > $maxContextTokens) {
                    break;
                }

                $inputTokensCount += $tokens;

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
