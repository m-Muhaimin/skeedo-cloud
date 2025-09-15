<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Ollama;

use Ai\Domain\Title\GenerateTitleResponse;
use Ai\Domain\Title\TitleServiceInterface;
use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\Title;
use Ai\Infrastructure\Utils\TextProcessor;
use Ai\Infrastructure\Services\CostCalculator;
use Billing\Domain\ValueObjects\CreditCount;
use Override;
use Shared\Infrastructure\Services\ModelRegistry;
use Traversable;

class TitleGeneratorService implements TitleServiceInterface
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
    public function generateTitle(Content $content, Model $model): GenerateTitleResponse
    {
        $words = TextProcessor::sanitize($content);

        if (empty($words)) {
            $title = new Title();
            return new GenerateTitleResponse($title, new CreditCount(0));
        }

        $resp = $this->client->sendRequest('POST', '/api/chat', [
            // Remove the ollama/ prefix as this prefix added to identify the provider
            'model' => preg_replace('/^ollama\//', '', trim($model->value)),
            'stream' => false,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => TextProcessor::getSystemMessage(),
                ],
                [
                    'role' => 'user',
                    'content' => TextProcessor::getUserMessage($words),
                ]
            ]
        ]);

        $contents = $resp->getBody()->getContents();
        $data = json_decode($contents);

        $inputCost = $this->calc->calculate(
            $data->prompt_eval_count ?? 0,
            $model,
            CostCalculator::INPUT
        );

        $outpuitCost = $this->calc->calculate(
            $data->eval_count ?? 0,
            $model,
            CostCalculator::OUTPUT
        );

        $cost = new CreditCount($inputCost->value + $outpuitCost->value);

        $title = $data->message->content ?? '';
        $title = explode("\n", trim($title))[0];
        $title = trim($title, ' "');

        return new GenerateTitleResponse(
            new Title($title ?: null),
            $cost
        );
    }
}
