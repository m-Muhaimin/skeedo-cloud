<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\xAi;

use Ai\Domain\Completion\CompletionServiceInterface;
use Ai\Domain\ValueObjects\Chunk;
use Ai\Domain\ValueObjects\Model;
use Ai\Infrastructure\Services\AbstractBaseService;
use Ai\Infrastructure\Services\CostCalculator;
use Billing\Domain\ValueObjects\CreditCount;
use Generator;
use Override;
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
        parent::__construct($registry, 'xai', 'llm');
    }

    #[Override]
    public function generateCompletion(Model $model, array $params = []): Generator
    {
        $prompt = $params['prompt'] ?? '';

        $body = [
            'model' => $model->value,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ],
            ],
            'stream' => true,
            'temperature' => (int)($params['temperature'] ?? 1),
        ];

        $resp = $this->client->sendRequest('POST', '/v1/chat/completions', $body);
        $stream = new StreamResponse($resp);

        $inTokens = 0;
        $outTokens = 0;

        foreach ($stream as $data) {
            $inTokens = $data->usage->prompt_tokens ?? $inTokens;
            $outTokens = $data->usage->completion_tokens ?? $outTokens;

            $choice = $data->choices[0] ?? null;

            if (!$choice) {
                continue;
            }

            if (isset($choice->delta->content)) {
                yield new Chunk($choice->delta->content);
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

        return new CreditCount($inputCost->value + $outputCost->value);
    }
}
