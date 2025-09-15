<?php

declare(strict_types=1);

namespace Billing\Domain\ValueObjects;

use Ai\Domain\ValueObjects\Model;
use Billing\Domain\ValueObjects\PlanConfig\ChatConfig;
use Billing\Domain\ValueObjects\PlanConfig\ClassifierConfig;
use Billing\Domain\ValueObjects\PlanConfig\CoderConfig;
use Billing\Domain\ValueObjects\PlanConfig\ComposerConfig;
use Billing\Domain\ValueObjects\PlanConfig\ImagineConfig;
use Billing\Domain\ValueObjects\PlanConfig\TitlerConfig;
use Billing\Domain\ValueObjects\PlanConfig\TranscriberConfig;
use Billing\Domain\ValueObjects\PlanConfig\VideoConfig;
use Billing\Domain\ValueObjects\PlanConfig\VoiceIsolatorConfig;
use Billing\Domain\ValueObjects\PlanConfig\VoiceOverConfig;
use Billing\Domain\ValueObjects\PlanConfig\WriterConfig;
use JsonSerializable;
use Override;

class PlanConfig implements JsonSerializable
{
    public readonly WriterConfig $writer;
    public readonly CoderConfig $coder;
    public readonly ImagineConfig $imagine;
    public readonly TranscriberConfig $transcriber;
    public readonly VoiceOverConfig $voiceover;
    public readonly TitlerConfig $titler;
    public readonly ChatConfig $chat;
    public readonly VoiceIsolatorConfig $voiceIsolator;
    public readonly ClassifierConfig $classifier;
    public readonly ComposerConfig $composer;
    public readonly VideoConfig $video;

    /** @var array<string,bool> */
    public readonly array $models;

    /** @var array<string,bool> */
    public readonly array $tools;

    public readonly Model $embeddingModel;

    /** @var null|array<string> */
    public readonly ?array $assistants;

    /** @var null|array<string> */
    public readonly ?array $presets;

    public function __construct(?array $data = null)
    {
        $data = $data ?? [];

        $this->writer = new WriterConfig(
            $data['writer']['is_enabled'] ?? false,
            new Model($data['writer']['model'] ?? 'gpt-4')
        );

        $this->coder = new CoderConfig(
            $data['coder']['is_enabled'] ?? false,
            new Model($data['coder']['model'] ?? 'gpt-4')
        );

        $this->imagine = new ImagineConfig(
            $data['imagine']['is_enabled'] ?? false
        );

        $this->transcriber = new TranscriberConfig(
            $data['transcriber']['is_enabled'] ?? false
        );

        $this->voiceover = new VoiceOverConfig(
            $data['voiceover']['is_enabled'] ?? false,
            isset($data['voiceover']['clone_cap']) ? (int) $data['voiceover']['clone_cap'] : null
        );

        $this->titler = new TitlerConfig(
            new Model($data['titler']['model'] ?? 'gpt-4')
        );

        $this->chat = new ChatConfig(
            $data['chat']['is_enabled'] ?? false
        );

        $this->voiceIsolator = new VoiceIsolatorConfig(
            $data['voice_isolator']['is_enabled'] ?? false
        );

        $this->classifier = new ClassifierConfig(
            $data['classifier']['is_enabled'] ?? false
        );

        $this->composer = new ComposerConfig(
            $data['composer']['is_enabled'] ?? false
        );

        $this->video = new VideoConfig(
            $data['video']['is_enabled'] ?? false
        );

        $models = [];

        if (isset($data['models']) && is_array($data['models'])) {
            foreach ($data['models'] as $model => $enabled) {
                $models[$model] = (bool) $enabled;
            }
        }

        $this->models = $models;

        $tools = [
            'embedding_search' => false,
            'google_search' => false,
            'youtube' => false,
            'web_scrap' => false,
            'generate_image' => false,
            'memory' => false,
        ];

        foreach ($tools as $tool => $enabled) {
            $tools[$tool] = (bool) ($data['tools'][$tool] ?? false);
        }

        $this->tools = $tools;
        $this->embeddingModel = new Model($data['embedding_model'] ?? 'text-embedding-3-small');

        $this->assistants = isset($data['assistants']) && is_array($data['assistants'])
            ? array_filter(
                $data['assistants'],
                fn($assistant) =>  is_string($assistant)
            ) : null;

        $this->presets = isset($data['presets']) && is_array($data['presets'])
            ? array_filter(
                $data['presets'],
                fn($assistant) =>  is_string($assistant)
            ) : null;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'writer' => $this->writer,
            'coder' => $this->coder,
            'imagine' => $this->imagine,
            'transcriber' => $this->transcriber,
            'voiceover' => $this->voiceover,
            'chat' => $this->chat,
            'voice_isolator' => $this->voiceIsolator,
            'classifier' => $this->classifier,
            'composer' => $this->composer,
            'video' => $this->video,
            'titler' => $this->titler,
            'models' => $this->models,
            'tools' => $this->tools,
            'embedding_model' => $this->embeddingModel,
            'assistants' => $this->assistants,
            'presets' => $this->presets,
        ];
    }

    public function toArray(): array
    {
        return json_decode(json_encode($this), true);
    }
}
