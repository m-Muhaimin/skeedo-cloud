<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Azure;

use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\Speech\GenerateSpeechResponse;
use Ai\Domain\Speech\SpeechServiceInterface;
use Traversable;
use Voice\Domain\Entities\VoiceEntity;
use Ai\Domain\ValueObjects\Model;
use Ai\Infrastructure\Services\CostCalculator;
use DomainException;
use Override;
use Psr\Http\Message\StreamFactoryInterface;
use Voice\Domain\ValueObjects\ExternalId;
use Voice\Domain\ValueObjects\Gender;
use Voice\Domain\ValueObjects\LanguageCode;
use Voice\Domain\ValueObjects\Name;
use Voice\Domain\ValueObjects\Provider;
use Voice\Domain\ValueObjects\SampleUrl;
use Voice\Domain\ValueObjects\Tone;
use Voice\Domain\ValueObjects\UseCase;

class SpeechService implements SpeechServiceInterface
{
    private array $models = [
        'azure-tts',
    ];

    public function __construct(
        private Client $client,
        private CostCalculator $calc,
        private StreamFactoryInterface $streamFactory,
    ) {}

    #[Override]
    public function getVoiceList(): Traversable
    {
        $samples = include __DIR__ . '/samples.php';

        try {
            $resp = $this->client->sendRequest('GET', '/cognitiveservices/voices/list');
        } catch (ClientException $th) {
            yield from [];
            return;
        }


        if ($resp->getStatusCode() !== 200) {
            throw new ApiException('Failed to get voice list');
        }

        $voices = json_decode($resp->getBody()->getContents());

        foreach ($voices as $voice) {
            $sampleUrl = $samples[$voice->ShortName] ?? null;

            if (!$sampleUrl) {
                continue;
            }

            $entity = new VoiceEntity(
                new Provider('azure'),
                new Model('azure-tts'),
                new Name($voice->DisplayName),
                new ExternalId($voice->ShortName),
                new SampleUrl($sampleUrl),
            );

            match ($voice->Gender) {
                'Female' => $entity->setGender(Gender::FEMALE),
                'Male' => $entity->setGender(Gender::MALE),
                'Neutral' => $entity->setGender(Gender::NEUTRAL),
                default => null,
            };

            $locales = [$voice->Locale];

            if (isset($voice->SecondaryLocaleList)) {
                $locales = array_merge($locales, $voice->SecondaryLocaleList);
            }

            $langs = [];

            foreach ($locales as $code) {
                $lang = LanguageCode::create($code);

                if ($lang) {
                    $langs[] = $lang;
                }
            }

            $entity->setSupportedLanguages(...$langs);

            if (isset($voice->StyleList)) {
                $tones = [];
                $useCases = [];

                foreach ($voice->StyleList as $style) {
                    $tone = Tone::create($style);

                    if ($tone) {
                        $tones[] = $tone;
                        continue;
                    }

                    $useCase = UseCase::create($style);

                    if ($useCase) {
                        $useCases[] = $useCase;
                    }
                }

                $entity->setTones(...$tones);
                $entity->setUseCases(...$useCases);
            }

            yield $entity;
        }
    }

    #[Override]
    public function generateSpeech(
        VoiceEntity $voice,
        array $params = []
    ): GenerateSpeechResponse {
        if (!$params || !array_key_exists('prompt', $params)) {
            throw new DomainException('Missing parameter: prompt');
        }

        $languages = $voice->getSupportedLanguages();
        $locale = isset($languages[0]) ? $languages[0]->value : 'en-US';

        $body = '<speak version="1.0" xmlns="http://www.w3.org/2001/10/synthesis" xml:lang="' . $locale . '">
        <voice name="' . $voice->getExternalId()->value . '">
            ' . $params['prompt'] . '
        </voice>
    </speak>';

        $resp = $this->client->sendRequest(
            'POST',
            '/cognitiveservices/v1',
            body: $body,
            headers: [
                'Content-Type' => 'application/ssml+xml',
                'X-Microsoft-OutputFormat' => 'audio-24khz-48kbitrate-mono-mp3',
            ]
        );

        $chars = mb_strlen($params['prompt']);
        $cost = $this->calc->calculate($chars, $voice->getModel());;

        return new GenerateSpeechResponse(
            $resp->getBody(),
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
