<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\OpenAi;

use Ai\Domain\Completion\CompletionServiceInterface;
use Ai\Domain\ValueObjects\Chunk;
use Ai\Domain\ValueObjects\Model;
use Ai\Infrastructure\Services\AbstractBaseService;
use Ai\Infrastructure\Services\CostCalculator;
use Billing\Domain\ValueObjects\CreditCount;
use Generator;
use Override;
use RuntimeException;
use Shared\Infrastructure\Services\ModelRegistry;
use Traversable;

class CompletionService extends AbstractBaseService implements
    CompletionServiceInterface
{
    public function __construct(
        private Client $client,
        private CostCalculator $calc,
        private ModelRegistry $registry,
    ) {
        parent::__construct($registry, 'openai', 'llm');
    }

    /**
     * @throws RuntimeException
     */
    #[Override]
    public function generateCompletion(Model $model, array $params = []): Generator
    {
        $prompt = $params['prompt'] ?? '';

        $resp = $this->client->sendRequest('POST', '/v1/chat/completions', [
            'model' => $model->value,
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
            if (isset($item->usage)) {
                $inputTokensCount += $item->usage->prompt_tokens ?? 0;
                $outputTokensCount += $item->usage->completion_tokens ?? 0;
            }

            $choice = $item->choices[0] ?? null;

            if (!$choice) {
                continue;
            }

            if (isset($choice->delta->content)) {
                yield new Chunk($choice->delta->content);
            }
        }

        if ($this->client->hasCustomKey()) {
            // Cost is not calculated for custom keys,
            $cost = new CreditCount(0);
        } else {
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
        }

        return $cost;
    }
}
