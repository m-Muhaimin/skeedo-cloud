<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Google;

use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\Exceptions\DomainException;
use Ai\Domain\Speech\GenerateSpeechResponse;
use Ai\Domain\Speech\SpeechServiceInterface;
use Traversable;
use Voice\Domain\Entities\VoiceEntity;
use Ai\Domain\ValueObjects\Model;
use Ai\Infrastructure\Services\CostCalculator;
use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\Voice;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;
use Override;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;
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
        'google-tts-standard',
        'google-tts-premium', // Wavenet, Neural2, News, Casual, Journey, Polyglot
        'google-tts-studio', // Studio, 
    ];

    /** @var array<string,string> */
    private array $names = [];

    public function __construct(
        private TextToSpeechClient $client,
        private CostCalculator $calc,
        private StreamFactoryInterface $streamFactory
    ) {
        $this->names = include __DIR__ . '/names.php';
    }

    #[Override]
    public function getVoiceList(): Traversable
    {
        if (!$this->client->enabled) {
            yield from [];
            return;
        }

        $voices = $this->client->listVoices()->getVoices();

        /** @var Voice */
        foreach ($voices as $voice) {
            if (!isset($this->names[$voice->getName()])) {
                continue;
            }

            $url = 'https://cdn.aikeedo.com/samples/google/' . $voice->getName() . '.wav';

            match (true) {
                strpos($voice->getName(), 'Standard') !== false => $model = new Model('google-tts-standard'),
                strpos($voice->getName(), 'Studio') !== false => $model = new Model('google-tts-studio'),
                default => $model = new Model('google-tts-premium'),
            };

            $entity = new VoiceEntity(
                new Provider('google'),
                $model,
                new Name(
                    $this->names[$voice->getName()] ?? $voice->getName()
                ),
                new ExternalId($voice->getName()),
                new SampleUrl($url),
            );

            match ($voice->getSsmlGender()) {
                1 => $entity->setGender(Gender::MALE),
                2 => $entity->setGender(Gender::FEMALE),
                3 => $entity->setGender(Gender::NEUTRAL),
                default => null,
            };

            if (strpos($voice->getName(), 'News') !== false) {
                $entity->setUseCases(UseCase::NEWS);
            } else {
                $entity->setUseCases(UseCase::GENERAL);
            }

            if (strpos($voice->getName(), 'Casual') !== false) {
                $entity->setTones(Tone::CASUAL);
            }

            $langs = [];
            foreach ($voice->getLanguageCodes() ?: [] as $code) {
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
    public function generateSpeech(
        VoiceEntity $voice,
        array $params = []
    ): GenerateSpeechResponse {
        if (!$params || !array_key_exists('prompt', $params)) {
            throw new DomainException('Missing parameter: prompt');
        }

        $input = new SynthesisInput();
        $input->setText($params['prompt']);

        $vsp = new VoiceSelectionParams();

        $languages = $voice->getSupportedLanguages();
        $locale = isset($languages[0]) ? $languages[0]->value : 'en-US';

        $vsp->setLanguageCode($locale);
        $vsp->setName($voice->getExternalId()->value);

        $audioConfig = new AudioConfig();
        $audioConfig->setAudioEncoding(AudioEncoding::MP3);

        try {
            $resp = $this->client->synthesizeSpeech($input, $vsp, $audioConfig);
        } catch (Throwable $th) {
            throw new ApiException(
                $th->getMessage(),
                $th->getCode(),
                $th
            );
        }

        $chars = mb_strlen($params['prompt']);
        $cost = $this->calc->calculate($chars, $voice->getModel());

        return new GenerateSpeechResponse(
            $this->streamFactory->createStream($resp->getAudioContent()),
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
