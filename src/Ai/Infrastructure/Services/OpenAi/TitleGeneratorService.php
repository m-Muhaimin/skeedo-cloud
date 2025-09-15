<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\OpenAi;

use Ai\Domain\Title\GenerateTitleResponse;
use Ai\Domain\Title\TitleServiceInterface;
use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\Title;
use Ai\Infrastructure\Services\AbstractBaseService;
use Ai\Infrastructure\Utils\TextProcessor;
use Ai\Infrastructure\Services\CostCalculator;
use Billing\Domain\ValueObjects\CreditCount;
use Gioni06\Gpt3Tokenizer\Gpt3Tokenizer;
use Override;
use Shared\Infrastructure\Services\ModelRegistry;

class TitleGeneratorService extends AbstractBaseService implements
    TitleServiceInterface
{
    public function __construct(
        private Client $client,
        private Gpt3Tokenizer $tokenizer,
        private CostCalculator $calc,
        private ModelRegistry $registry,
    ) {
        parent::__construct($registry, 'openai', 'llm');
    }

    #[Override]
    public function generateTitle(Content $content, Model $model): GenerateTitleResponse
    {
        $words = TextProcessor::sanitize($content);

        if (empty($words)) {
            $title = new Title();
            return new GenerateTitleResponse($title, new CreditCount(0));
        }

        $resp = $this->client->sendRequest('POST', '/v1/chat/completions', [
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
            ]
        ]);

        $data = json_decode($resp->getBody()->getContents());

        if ($this->client->hasCustomKey()) {
            // Cost is not calculated for custom keys,
            $cost = new CreditCount(0);
        } else {
            $inputCost = $this->calc->calculate(
                $data->usage->prompt_tokens ?? 0,
                $model,
                CostCalculator::INPUT
            );

            $outpuitCost = $this->calc->calculate(
                $data->usage->completion_tokens ?? 0,
                $model,
                CostCalculator::OUTPUT
            );

            $cost = new CreditCount($inputCost->value + $outpuitCost->value);
        }

        $title = $data->choices[0]->message->content ?? '';
        $title = explode("\n", trim($title))[0];
        $title = trim($title, ' "');

        return new GenerateTitleResponse(
            new Title($title ?: null),
            $cost
        );
    }
}
