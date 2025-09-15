<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Custom;

use Ai\Domain\Completion\MessageServiceInterface;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\Entities\MessageEntity;
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
use Gioni06\Gpt3Tokenizer\Gpt3Tokenizer;
use Override;
use Shared\Infrastructure\Services\ModelRegistry;
use Throwable;
use Traversable;

class MessageService implements MessageServiceInterface
{
    private array $llms = [];

    public function __construct(
        private Client $client,
        private Gpt3Tokenizer $tokenizer,
        private CostCalculator $calc,
        private FileService $fs,
        private ToolCollection $tools,
        private Helper $helper,
        private ModelRegistry $registry,
    ) {
        $this->llms = array_filter($this->registry['directory'], function ($llm) {
            return $llm['custom'] ?? false;
        });

        $this->llms = array_values($this->llms);
    }

    #[Override]
    public function supportsModel(Model $model): bool
    {
        foreach ($this->llms as $llm) {
            if (in_array($model->value, array_column($llm['models'], 'key'))) {
                return true;
            }
        }

        return false;
    }

    #[Override]
    public function getSupportedModels(): Traversable
    {
        foreach ($this->llms as $llm) {
            foreach ($llm['models'] as $model) {
                yield new Model($model['key']);
            }
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

        $modelName = str_contains($model->value, '/')
            ? explode('/', $model->value, 2)[1]
            : $model->value;

        $body = [
            'messages' => $messages,
            'model' => $modelName,
            'stream' => true,
            'stream_options' => [
                'include_usage' => true
            ]
        ];

        foreach ($this->llms as $llm) {
            if (in_array($model->value, array_column($llm['models'], 'key'))) {
                $foundModel = array_values(
                    array_filter($llm['models'], fn($m) => $m['key'] == $model->value)
                );
                if (($foundModel[0]['tools'] ?? false)) {
                    $tools = $this->getTools($message);
                    if ($tools) {
                        $body['tools'] = $tools;
                        $body['tool_choice'] = 'auto';
                    }
                }
            }
        }

        $resp = $this->client->sendRequest(
            $model,
            'POST',
            '/chat/completions',
            $body
        );
        $stream = new StreamResponse($resp);

        $calls = [];
        $isReasoning = false;
        $citations = [];
        foreach ($stream as $data) {
            $usage = $this->helper->findUsageObject($data);

            if ($usage) {
                $inputTokensCount += $usage->prompt_tokens ?? 0;
                $outputTokensCount += $usage->completion_tokens ?? 0;
            }

            if (isset($data->citations)) {
                foreach ($data->citations as $citation) {
                    $citations[] = $citation;
                }
            }

            $choice = $data->choices[0] ?? null;

            if (!$choice) {
                continue;
            }

            if (isset($choice->delta->content)) {
                if (str_starts_with(trim($choice->delta->content), '<think>')) {
                    $isReasoning = true;
                    continue;
                }

                if (str_ends_with(trim($choice->delta->content), '</think>')) {
                    $isReasoning = false;
                    continue;
                }

                $token = $choice->delta->content;

                // Check for citation references like [1], [2], etc.
                // Process all citation references in the token
                $pattern = '/\[(\d+)\]/';
                if (preg_match_all($pattern, $token, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $index = (int)$match[1] - 1; // Convert to zero-based index
                        if (isset($citations[$index])) {
                            // Replace [n] with markdown link
                            $citation = $citations[$index];
                            $linkText = "[{$match[1]}]";
                            $url = $citation;
                            $markdownLink = "[$linkText]($url)";
                            $token = str_replace($match[0], $markdownLink, $token);
                        }
                    }
                }

                if ($isReasoning) {
                    $token = new ReasoningToken($token);
                }

                yield new Chunk($token);
            }

            if (isset($choice->delta->reasoning_content)) {
                yield new Chunk(
                    new ReasoningToken($choice->delta->reasoning_content)
                );
            }

            if (isset($choice->delta->tool_calls)) {
                foreach ($choice->delta->tool_calls as $call) {
                    if (!isset($call->index) || !isset($call->function)) {
                        continue;
                    }

                    if (!isset($calls[$call->index])) {
                        $calls[$call->index] = $call;
                        continue;
                    }

                    if (isset($call->function->arguments)) {
                        $calls[$call->index]->function->arguments .= $call->function->arguments;
                    }
                }
            }
        }

        if ($calls) {
            // $body['messages'] = []; // Clear messages
            $body['messages'][] = [
                'role' => 'assistant',
                'content' => null,
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
                'content' => $content,
                'tool_call_id' => $call->id
            ];

            $callAgain = true;
        }

        if ($callAgain) {
            $resp = $this->client->sendRequest(
                $model,
                'POST',
                '/chat/completions',
                $body
            );
            $stream = new StreamResponse($resp);

            foreach ($stream as $data) {
                $usage = $this->helper->findUsageObject($data);

                if ($usage) {
                    $inputTokensCount += $usage->prompt_tokens ?? 0;
                    $outputTokensCount += $usage->completion_tokens ?? 0;
                }

                $choice = $data->choices[0] ?? null;

                if (!$choice) {
                    continue;
                }

                if (isset($choice->delta->content)) {
                    yield new Chunk($choice->delta->content);
                }
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
                if ($current->getQuote()->value) {
                    array_unshift(
                        $messages,
                        $this->generateQuoteMessage($current->getQuote())
                    );
                }

                $tokens = 0;

                if ($current->getRole()->value == 'assistant') {
                    $content = $current->getContent()->value;
                } else {
                    $content = [];
                    $img = $current->getImage();

                    if (
                        $current->getRole()->value == 'user'
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
                            $tokens += $this->calcualteImageToken(
                                $img->getWidth()->value,
                                $img->getHeight()->value
                            );
                        } catch (Throwable $th) {
                            // Unable to load image
                        }
                    }

                    $content[] = [
                        'type' => 'text',
                        'text' => $current->getContent()->value
                    ];
                }


                $tokens += $this->tokenizer->count($current->getContent()->value);

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

        // Combine all system messages
        $systemMessages = [];
        $nonSystemMessages = [];

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemMessages[] = $msg['content'];
            } else {
                $nonSystemMessages[] = $msg;
            }
        }

        $finalMessages = [];
        if ($systemMessages) {
            $finalMessages[] = [
                'role' => 'system',
                'content' => implode("\n\n", $systemMessages)
            ];
        }

        // Process non-system messages with alternating roles
        $lastRole = null;
        foreach ($nonSystemMessages as $msg) {
            // Tool messages don't break the alternating pattern
            if ($msg['role'] === 'tool') {
                $finalMessages[] = $msg;
                continue;
            }

            if ($lastRole === $msg['role'] && $lastRole !== 'tool') {
                // If we have consecutive messages of the same role, 
                // insert an intermediary message
                if ($msg['role'] === 'user') {
                    $finalMessages[] = [
                        'role' => 'assistant',
                        'content' => 'Please continue.'
                    ];
                } else {
                    $finalMessages[] = [
                        'role' => 'user',
                        'content' => 'Please continue.'
                    ];
                }
            }

            $finalMessages[] = $msg;
            $lastRole = $msg['role'];
        }

        // Ensure the last message is from the user
        $lastMessage = end($finalMessages);
        if ($lastMessage && $lastMessage['role'] !== 'user') {
            $finalMessages[] = [
                'role' => 'user',
                'content' => 'Please continue.'
            ];
        }

        return $finalMessages;
    }

    private function generateQuoteMessage(Quote $quote): array
    {
        return [
            'role' => 'system',
            'content' => 'The user is referring to this in particular:\n' . $quote->value
        ];
    }

    private function calcualteImageToken(int $width, int $height): int
    {
        if ($width > 2048) {
            // Scale down to fit 2048x2048
            $width = 2048;
            $height = (int) (2048 / $width * $height);
        }

        if ($height > 2048) {
            // Scale down to fit 2048x2048
            $height = 2048;
            $width = (int) (2048 / $height * $width);
        }

        if ($width <= $height && $width > 768) {
            $width = 768;
            $height = (int) (768 / $width * $height);
        }

        // Calculate how many 512x512 tiles are needed to cover the image
        $tiles = (int) (ceil($width / 512) + ceil($height / 512));
        return 170 * $tiles + 85;
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
