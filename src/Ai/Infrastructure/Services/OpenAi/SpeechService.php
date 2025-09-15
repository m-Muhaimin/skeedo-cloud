<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\OpenAi;

use Voice\Domain\Entities\VoiceEntity;
use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\Exceptions\DomainException;
use Ai\Domain\Speech\GenerateSpeechResponse;
use Ai\Domain\Speech\SpeechServiceInterface;
use Voice\Domain\ValueObjects\ExternalId;
use Traversable;
use Ai\Domain\ValueObjects\Model;
use Voice\Domain\ValueObjects\SampleUrl;
use Voice\Domain\ValueObjects\UseCase;
use Voice\Domain\ValueObjects\Name;
use Ai\Infrastructure\Services\CostCalculator;
use Billing\Domain\ValueObjects\CreditCount;
use Override;
use Throwable;
use Voice\Domain\ValueObjects\Gender;
use Voice\Domain\ValueObjects\LanguageCode;
use Voice\Domain\ValueObjects\Provider;

class SpeechService implements SpeechServiceInterface
{
    private array $models = [
        'tts-1',
        'tts-1-hd'
    ];

    private array $languages = [
        'af-ZA',
        'ar-SA',
        'hy-AM',
        'az-AZ',
        'be-BY',
        'bs-BA',
        'bg-BG',
        'ca-ES',
        'zh-CN',
        'hr-HR',
        'cs-CZ',
        'da-DK',
        'nl-NL',
        'en-US',
        'et-EE',
        'fi-FI',
        'fr-FR',
        'gl-ES',
        'de-DE',
        'el-GR',
        'he-IL',
        'hi-IN',
        'hu-HU',
        'is-IS',
        'id-ID',
        'it-IT',
        'ja-JP',
        'kn-IN',
        'kk-KZ',
        'ko-KR',
        'lv-LV',
        'lt-LT',
        'mk-MK',
        'ms-MY',
        'mr-IN',
        'mi-NZ',
        'ne-NP',
        'nb-NO',
        'fa-IR',
        'pl-PL',
        'pt-PT',
        'ro-RO',
        'ru-RU',
        'sr-RS',
        'sk-SK',
        'sl-SI',
        'es-ES',
        'sw-KE',
        'sv-SE',
        'tl-PH',
        'ta-IN',
        'th-TH',
        'tr-TR',
        'uk-UA',
        'ur-PK',
        'vi-VN',
        'cy-GB'
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
    public function getVoiceList(): Traversable
    {
        $voices = [
            [
                'name' => 'alloy',
                'gender' => 'male'
            ],
            [
                'name' => 'ash',
                'gender' => 'male'
            ],
            [
                'name' => 'coral',
                'gender' => 'female'
            ],
            [
                'name' => 'echo',
                'gender' => 'male'
            ],
            [
                'name' => 'fable',
                'gender' => 'male'
            ],
            [
                'name' => 'onyx',
                'gender' => 'male'
            ],
            [
                'name' => 'nova',
                'gender' => 'female'
            ],
            [
                'name' => 'sage',
                'gender' => 'female'
            ],
            [
                'name' => 'shimmer',
                'gender' => 'female'
            ]
        ];

        foreach ($voices as $voice) {
            $url = 'https://cdn.openai.com/API/docs/audio/'
                . $voice['name'] . '.wav';

            $entity = new VoiceEntity(
                new Provider('openai'),
                new Model('tts-1'),
                new Name(ucfirst($voice['name'])),
                new ExternalId($voice['name']),
                new SampleUrl($url)
            );

            $entity->setGender(Gender::tryFrom($voice['gender']));
            $entity->setUseCases(UseCase::GENERAL);

            $langs = [];
            foreach ($this->languages as $code) {
                $lang = LanguageCode::create($code);

                if ($lang) {
                    $langs[] = $lang;
                }
            }

            $entity->setSupportedLanguages(...$langs);

            yield $entity;
        }
    }

    #[Override]
    public function generateSpeech(VoiceEntity $voice, array $params = []): GenerateSpeechResponse
    {
        if (!$params || !array_key_exists('prompt', $params)) {
            throw new DomainException('Missing parameter: prompt');
        }

        $data = [
            'model' => $voice->getModel()->value,
            'input' => $params['prompt'],
            'voice' => $voice->getExternalId()->value
        ];

        $resp = $this->client->sendRequest('POST', '/v1/audio/speech', $data);

        if ($this->client->hasCustomKey()) {
            // Cost is not calculated for custom keys,
            $cost = new CreditCount(0);
        } else {
            $chars = mb_strlen($params['prompt']);
            $cost = $this->calc->calculate($chars, $voice->getModel());
        }


        return new GenerateSpeechResponse(
            $resp->getBody(),
            $cost
        );
    }
}
