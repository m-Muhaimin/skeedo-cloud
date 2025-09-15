<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Custom;

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

    #[Override]
    public function generateTitle(Content $content, Model $model): GenerateTitleResponse
    {
        $words = TextProcessor::sanitize($content);

        if (empty($words)) {
            $title = new Title();
            return new GenerateTitleResponse($title, new CreditCount(0));
        }

        $modelName = str_contains($model->value, '/')
            ? explode('/', $model->value, 2)[1]
            : $model->value;

        $resp = $this->client->sendRequest($model, 'POST', '/chat/completions', [
            'model' => $modelName,
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

        $usage = $this->helper->findUsageObject($data);

        $inputCost = $this->calc->calculate(
            $usage->prompt_tokens ?? 0,
            $model,
            CostCalculator::INPUT
        );

        $outpuitCost = $this->calc->calculate(
            $usage->completion_tokens ?? 0,
            $model,
            CostCalculator::OUTPUT
        );

        $cost = new CreditCount($inputCost->value + $outpuitCost->value);

        $title = $data->choices[0]->message->content ?? '';
        $title = explode("\n", trim($title))[0];
        $title = trim($title, ' "');

        return new GenerateTitleResponse(
            new Title($title ?: null),
            $cost
        );
    }
}
