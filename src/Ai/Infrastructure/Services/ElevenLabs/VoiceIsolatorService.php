<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\ElevenLabs;

use Ai\Domain\IsolatedVoice\IsolatedVoiceResponse;
use Ai\Domain\IsolatedVoice\VoiceIsolatorServiceInterface;
use Ai\Domain\ValueObjects\Model;
use Ai\Infrastructure\Services\CostCalculator;
use Override;
use Psr\Http\Message\StreamInterface;
use Traversable;

class VoiceIsolatorService implements VoiceIsolatorServiceInterface
{
    private array $models = [
        'elevenlabs'
    ];

    public function __construct(
        private Client $client,
        private CostCalculator $calc,
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
    public function generateIsolatedVoice(
        Model $model,
        StreamInterface $file,
        array $params = []
    ): IsolatedVoiceResponse {
        // Generate isolated voice
        $resp = $this->client->sendRequest(
            'POST',
            '/audio-isolation',
            [
                'audio' => $file
            ],
            [
                'Content-Type' => 'multipart/form-data'
            ]
        );

        $chars = (int) $resp->getHeaderLine('Character-Cost');
        $cost = $this->calc->calculate($chars, $model);

        return new IsolatedVoiceResponse(
            $resp->getBody(),
            $cost,
        );
    }
}
