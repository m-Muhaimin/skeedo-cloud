<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\xAi;

use Ai\Domain\Title\GenerateTitleResponse;
use Ai\Domain\Title\TitleServiceInterface;
use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\Title;
use Ai\Infrastructure\Services\AbstractBaseService;
use Ai\Infrastructure\Utils\TextProcessor;
use Ai\Infrastructure\Services\CostCalculator;
use Billing\Domain\ValueObjects\CreditCount;
use Override;
use Shared\Infrastructure\Services\ModelRegistry;

class TitleGeneratorService extends AbstractBaseService implements
    TitleServiceInterface
{
    public function __construct(
        private Client $client,
        private CostCalculator $calc,
        private ModelRegistry $registry,
    ) {
        parent::__construct($registry, 'xai', 'llm');
    }

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
                    'role' => 'user',
                    'content' => TextProcessor::getUserMessage($words)
                ],
                [
                    'role' => 'assistant',
                    'content' => 'Title:'
                ]
            ],
            'system' => TextProcessor::getSystemMessage(),
            'max_tokens' => 100,
        ];

        $resp = $this->client->sendRequest('POST', '/v1/chat/completions', $body);
        $data = json_decode($resp->getBody()->getContents());

        $inputCost = $this->calc->calculate(
            $data->usage->prompt_tokens ?? 0,
            $model,
            CostCalculator::INPUT
        );

        $outputCost = $this->calc->calculate(
            $data->usage->completion_tokens ?? 0,
            $model,
            CostCalculator::OUTPUT
        );

        $cost = new CreditCount($inputCost->value + $outputCost->value);

        $title = $data->choices[0]->message->content ?? '';
        $title = explode("\n", trim($title))[0];
        $title = trim($title, ' "');
        $title = trim($title, '*');

        return new GenerateTitleResponse(
            new Title($title ?: null),
            $cost
        );
    }
}
