<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\OpenAi;

use Ai\Domain\Transcription\GenerateTranscriptionResponse;
use Ai\Domain\ValueObjects\Transcription;
use Ai\Domain\Transcription\TranscriptionServiceInterface;
use Ai\Domain\ValueObjects\Model;
use Ai\Infrastructure\Services\CostCalculator;
use Billing\Domain\ValueObjects\CreditCount;
use Override;
use Psr\Http\Message\StreamInterface;
use Traversable;

class TranscriptionService implements TranscriptionServiceInterface
{
    private array $models = [
        'whisper-1'
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
    public function generateTranscription(
        Model $model,
        StreamInterface $file,
        array $params = [],
    ): GenerateTranscriptionResponse {
        $model = $model ?: $this->models[0];

        $resp = $this->client->sendRequest(
            'POST',
            '/v1/audio/transcriptions',
            body: [
                'file' => $file,
                'model' => $model->value,
                'response_format' => 'verbose_json',
                // 'timestamp_granularities' => ['segment', 'word'],
            ],
            headers: [
                'Content-Type' => 'multipart/form-data'
            ]
        );

        $data = json_decode($resp->getBody()->getContents());

        $segments = array_map(
            fn($segment): array => [
                'text' => $segment->text,
                'start' => $segment->start,
                'end' => $segment->end,
            ],
            $data->segments
        );

        $transcription = new Transcription(
            $data->text,
            $data->language,
            $data->duration,
            $segments,
            [], // API Client does not return words
        );

        if ($this->client->hasCustomKey()) {
            // Cost is not calculated for custom keys,
            $cost = new CreditCount(0);
        } else {
            $cost = $this->calc->calculate($data->duration ?? 0, $model);
        }

        return new GenerateTranscriptionResponse(
            $cost,
            $transcription,
        );
    }
}
