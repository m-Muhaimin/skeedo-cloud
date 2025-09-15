<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Google;

use Easy\Container\Attributes\Inject;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient as BaseClient;

class TextToSpeechClient extends BaseClient
{
    public readonly bool $enabled;

    public function __construct(
        #[Inject('option.gcp')]
        private ?array $options = null,
    ) {
        if ($options) {
            $this->enabled = true;
            parent::__construct($options);
        } else {
            $this->enabled = false;
        }
    }
}
