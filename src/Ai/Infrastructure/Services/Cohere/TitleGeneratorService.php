<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Cohere;

use Ai\Domain\Title\GenerateTitleResponse;
use Ai\Domain\Title\TitleServiceInterface;
use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\Title;
use Ai\Infrastructure\Utils\TextProcessor;
use Ai\Infrastructure\Services\CostCalculator;
use Billing\Domain\ValueObjects\CreditCount;
use Override;
use Traversable;

class TitleGeneratorService implements TitleServiceInterface
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
    public function generateTitle(
        Content $content,
        Model $model
    ): GenerateTitleResponse {
        $words = TextProcessor::sanitize($content);

        if (empty($words)) {
            $title = new Title();
            return new GenerateTitleResponse($title, new CreditCount(0));
        }

        $body = [
            'model' => $model->value,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => TextProcessor::getSystemMessage(),
                ],
                [
                    'role' => 'user',
                    'content' => TextProcessor::getUserMessage($words),
                ]
            ],
            'max_tokens' => 100
        ];

        $resp = $this->client->sendRequest('POST', '/chat', $body);
        $data = json_decode($resp->getBody()->getContents());

        $inputCost = $this->calc->calculate(
            $data->usage->billed_units->input_tokens ?? 0,
            $model,
            CostCalculator::INPUT
        );

        $outputCost = $this->calc->calculate(
            $data->usage->billed_units->output_tokens ?? 0,
            $model,
            CostCalculator::OUTPUT
        );

        $cost = new CreditCount($inputCost->value + $outputCost->value);

        $title = $data->message->content[0]->text ?? '';
        $title = explode("\n", trim($title))[0];
        $title = trim($title, ' "');


        return new GenerateTitleResponse(
            new Title($title ?: null),
            $cost
        );
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
