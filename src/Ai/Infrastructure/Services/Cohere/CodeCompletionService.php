<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Cohere;

use Ai\Domain\Completion\CodeCompletionServiceInterface;
use Ai\Domain\ValueObjects\Chunk;
use Ai\Domain\ValueObjects\Model;
use Ai\Infrastructure\Services\CostCalculator;
use Billing\Domain\ValueObjects\CreditCount;
use Generator;
use Override;
use Traversable;

class CodeCompletionService implements CodeCompletionServiceInterface
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
        private CostCalculator $calc
    ) {}

    #[Override]
    public function generateCodeCompletion(
        Model $model,
        string $prompt,
        string $language,
        array $params = []
    ): Generator {
        $prompt = $params['prompt'] ?? '';

        $body = [
            'model' => $model->value,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "You're $language programming language expert."
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'stream' => true,
        ];

        if (isset($params['temperature'])) {
            $body['temperature'] = (float)$params['temperature'] / 2;
        }

        $resp = $this->client->sendRequest('POST', '/chat', $body);
        $stream = new StreamResponse($resp);

        $inputTokensCount = 0;
        $outputTokensCount = 0;

        foreach ($stream as $data) {
            $type = $data->type ?? null;

            if ($type == 'content-start' || $type == 'content-delta') {
                yield new Chunk($data->delta->message->content->text);
                continue;
            }

            if ($type == 'message-end') {
                $inputTokensCount += $data->delta->usage->billed_units->input_tokens ?? 0;
                $outputTokensCount += $data->delta->usage->billed_units->output_tokens ?? 0;
                continue;
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

        return new CreditCount($inputCost->value + $outputCost->value);
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
}
