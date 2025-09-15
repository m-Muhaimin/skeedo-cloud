<?php

declare(strict_types=1);

namespace Ai\Domain\ValueObjects;

use JsonSerializable;
use Override;

class Transcription implements JsonSerializable
{
    public readonly array $words;
    public readonly array $segments;

    public function __construct(
        public readonly string $text,
        public readonly ?string $language,
        public readonly ?float $duration,
        array $segments = [],
        array $words = [],
    ) {
        $this->words = array_map(
            fn (array $word): array => $this->createWord(
                $word['word'],
                $word['start'],
                $word['end']
            ),
            $words
        );

        $this->segments = array_map(
            fn (array $segment): array => $this->createSegment(
                $segment['text'],
                $segment['start'],
                $segment['end']
            ),
            $segments
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'text' => $this->text,
            'language' => $this->language,
            'duration' => $this->duration,
            'segments' => $this->segments,
            'words' => $this->words,
        ];
    }

    private function createWord(string $word, float $start, float $end): array
    {
        return [
            'word' => $word,
            'start' => $start,
            'end' => $end,
        ];
    }

    private function createSegment(string $text, float $start, float $end): array
    {
        return [
            'text' => $text,
            'start' => $start,
            'end' => $end,
        ];
    }
}
