<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Custom;

use Ai\Domain\Completion\CompletionServiceInterface;
use Ai\Domain\ValueObjects\Chunk;
use Ai\Domain\ValueObjects\Model;
use Ai\Infrastructure\Services\CostCalculator;
use Billing\Domain\ValueObjects\CreditCount;
use Generator;
use Override;
use RuntimeException;
use Shared\Infrastructure\Services\ModelRegistry;
use Traversable;

class CompletionService implements CompletionServiceInterface
{
    private array $llms = [];

    public function __construct(
        private Client $client,
        private CostCalculator $calc,
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

    /**
     * @throws RuntimeException
     */
    #[Override]
    public function generateCompletion(Model $model, array $params = []): Generator
    {
        $prompt = $params['prompt'] ?? '';

        $modelName = str_contains($model->value, '/')
            ? explode('/', $model->value, 2)[1]
            : $model->value;

        $resp = $this->client->sendRequest($model, 'POST', '/chat/completions', [
            'model' => $modelName,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ],
            ],
            'temperature' => (int)($params['temperature'] ?? 1),
            'stream' => true,
            'stream_options' => [
                'include_usage' => true
            ]
        ]);

        $inputTokensCount = 0;
        $outputTokensCount = 0;

        $stream = new StreamResponse($resp);
        foreach ($stream as $item) {
            $usage = $this->helper->findUsageObject($item);
            if ($usage) {
                $inputTokensCount += $usage->prompt_tokens ?? 0;
                $outputTokensCount += $usage->completion_tokens ?? 0;
            }

            $choice = $item->choices[0] ?? null;

            if (!$choice) {
                continue;
            }

            if (isset($choice->delta->content)) {
                yield new Chunk($choice->delta->content);
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

        $cost = new CreditCount($inputCost->value + $outputCost->value);

        return $cost;
    }
}
