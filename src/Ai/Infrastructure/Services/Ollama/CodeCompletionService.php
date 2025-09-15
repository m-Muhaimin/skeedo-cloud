<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Ollama;

use Ai\Domain\Completion\CodeCompletionServiceInterface;
use Ai\Domain\ValueObjects\Chunk;
use Ai\Domain\ValueObjects\Model;
use Ai\Infrastructure\Services\CostCalculator;
use Billing\Domain\ValueObjects\CreditCount;
use Generator;
use Override;
use Shared\Infrastructure\Services\ModelRegistry;
use Traversable;

class CodeCompletionService implements CodeCompletionServiceInterface
{
    /** @var string[] */
    private array $models = [];

    public function __construct(
        private Client $client,
        private CostCalculator $calc,
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
    public function generateCodeCompletion(
        Model $model,
        string $prompt,
        string $language,
        array $params = [],
    ): Generator {
        $resp = $this->client->sendRequest('POST', '/api/chat', [
            // Remove the ollama/ prefix as this prefix added to identify the provider
            'model' => preg_replace('/^ollama\//', '', trim($model->value)),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "You're $language programming language expert."
                ],
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
