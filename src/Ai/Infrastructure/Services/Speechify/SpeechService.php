<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Speechify;

use Ai\Domain\Exceptions\ApiException;
use Voice\Domain\Entities\VoiceEntity;
use Ai\Domain\Exceptions\DomainException;
use Ai\Domain\Speech\GenerateSpeechResponse;
use Ai\Domain\Speech\SpeechServiceInterface;
use Voice\Domain\ValueObjects\ExternalId;
use Ai\Domain\ValueObjects\Model;
use Voice\Domain\ValueObjects\SampleUrl;
use Voice\Domain\ValueObjects\UseCase;
use Voice\Domain\ValueObjects\Name;
use Voice\Domain\ValueObjects\Tone;
use Ai\Infrastructure\Services\CostCalculator;
use Override;
use Psr\Http\Message\StreamFactoryInterface;
use Traversable;
use Voice\Domain\ValueObjects\Accent;
use Voice\Domain\ValueObjects\Age;
use Voice\Domain\ValueObjects\Gender;
use Voice\Domain\ValueObjects\LanguageCode;
use Voice\Domain\ValueObjects\Provider;

class SpeechService implements SpeechServiceInterface
{
    private array $models = [
        'simba-multilingual',
        'simba-english',
        'simba-turbo',
    ];

    public function __construct(
        private Client $client,
        private StreamFactoryInterface $factory,
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
    public function getVoiceList(): Traversable
    {
        try {
            $resp = $this->client->sendRequest('GET', '/voices');
        } catch (ApiException $th) {
            yield from [];
            return;
        }

        $content = json_decode($resp->getBody()->getContents());
        foreach ($content as $voice) {
            $model = null;
            $langs = [];

            foreach ($voice->models ?? [] as $m) {
                if (in_array($m->name, $this->models)) {
                    $model = new Model($m->name);

                    $langs = array_map(fn($lang) => LanguageCode::create($lang->locale), $m->languages);
                    $langs = array_filter($langs);
                    break;
                }
            }

            if (!$model) {
                continue;
            }

            $entity = new VoiceEntity(
                new Provider('speechify'),
                $model,
                new Name($voice->display_name),
                new ExternalId($voice->id),
                new SampleUrl($voice->preview_audio ?? null)
            );

            if (isset($voice->gender) && $voice->gender) {
                $entity->setGender(Gender::tryFrom($voice->gender));
            }

            $tags = $voice->tags ?? [];
            $tones = [];
            $useCases = [];
            foreach ($tags as $tag) {
                if (str_starts_with($tag, 'accent:')) {
                    $entity->setAccent(Accent::create(str_replace('accent:', '', $tag)));
                }

                if (str_starts_with($tag, 'age:')) {
                    $entity->setAge(Age::create(str_replace('age:', '', $tag)));
                }

                if (str_starts_with($tag, 'timbre:')) {
                    $tone = Tone::create(str_replace('timbre:', '', $tag));

                    if ($tone) {
                        $tones[] = $tone;
                    }
                }

                if (str_starts_with($tag, 'use-case:')) {
                    $cases = explode('-and-', str_replace('use-case:', '', $tag));
                    foreach ($cases as $case) {
                        $c = UseCase::tryFrom($case);

                        if ($c) {
                            $useCases[] = $c;
                        }
                    }
                }
            }

            $entity->setSupportedLanguages(...$langs);
            $entity->setTones(...$tones);
            $entity->setUseCases(...$useCases);

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

        $resp = $this->client->sendRequest(
            'POST',
            '/audio/speech',
            [
                'audio_format' => 'mp3', // wav, mp3, ogg, aac
                'input' => $params['prompt'],
                // 'language' => '', 
                'model_id' => $voice->getModel()->value,
                // 'options' => [
                //     'loudness_normalization' => true, // When true latencies are increased
                // ],
                'voice_id' => $voice->getExternalId()->value,
            ]
        );

        $body = json_decode($resp->getBody()->getContents());

        $chars = $body->billable_characters_count;
        $cost = $this->calc->calculate($chars, $voice->getModel());

        return new GenerateSpeechResponse(
            $this->factory->createStream(base64_decode($body->audio_data)),
            $cost
        );
    }
}
