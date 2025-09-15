<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\OpenAi;

use Ai\Domain\Classification\ClassificationResponse;
use Ai\Domain\Classification\ClassificationServiceInterface;
use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\ValueObjects\Classification;
use Ai\Domain\ValueObjects\Model;
use Ai\Infrastructure\Services\CostCalculator;
use Billing\Domain\ValueObjects\CreditCount;
use ErrorException;
use Override;
use Traversable;

class ClassificationService implements ClassificationServiceInterface
{
    private array $models = [
        'omni-moderation-latest'
    ];

    public function __construct(
        private Client $client,
        private CostCalculator $calc
    ) {}

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
    public function generateClassification(
        Model $model,
        string $input,
    ): ClassificationResponse {
        try {
            $resp = $this->client->sendRequest('POST', '/v1/moderations', [
                'model' => $model->value,
                'input' => $input,
            ]);
        } catch (ErrorException $th) {
            throw new ApiException($th->getMessage(), previous: $th);
        }

        // Currently, OpenAI does not provide cost for moderation API, 
        // so we set it to 0 for now.
        $cost = new CreditCount(0);

        $json = json_decode($resp->getBody()->getContents());

        $flags = [];
        foreach ($json->results[0]->categories as $flag => $val) {
            if ($val) {
                $flags[] = $flag;
            }
        }

        $classification = new Classification($flags);

        return new ClassificationResponse(
            $cost,
            $classification,
        );
    }
}
