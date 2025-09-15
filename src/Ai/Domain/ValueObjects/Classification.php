<?php

namespace Ai\Domain\ValueObjects;

use JsonSerializable;
use Override;

class Classification implements JsonSerializable
{
    public readonly bool $isFlagged;
    public readonly bool $isHate;
    public readonly bool $isHateThreatening;
    public readonly bool $isHarassment;
    public readonly bool $isHarassmentThreatening;
    public readonly bool $isSefHarm;
    public readonly bool $isSefHarmIntent;
    public readonly bool $isSefHarmInstructions;
    public readonly bool $isSexual;
    public readonly bool $isSexualMinors;
    public readonly bool $isViolence;
    public readonly bool $isViolenceGraphic;

    public function __construct(
        array $flags = []
    ) {
        $check = [
            'hate', 'hate/threatening', 'harassment', 'harassment/threatening',
            'self-harm', 'self-harm/intent', 'self-harm/instructions', 'sexual',
            'sexual/minors', 'violence', 'violence/graphic'
        ];

        $flags = array_filter($flags, fn ($flag) => in_array($flag, $check));

        $this->isFlagged = count($flags) > 0;
        $this->isHate = in_array('hate', $flags);
        $this->isHateThreatening = in_array('hate/threatening', $flags);
        $this->isHarassment = in_array('harassment', $flags);
        $this->isHarassmentThreatening = in_array('harassment/threatening', $flags);
        $this->isSefHarm = in_array('self-harm', $flags);
        $this->isSefHarmIntent = in_array('self-harm/intent', $flags);
        $this->isSefHarmInstructions = in_array('self-harm/instructions', $flags);
        $this->isSexual = in_array('sexual', $flags);
        $this->isSexualMinors = in_array('sexual/minors', $flags);
        $this->isViolence = in_array('violence', $flags);
        $this->isViolenceGraphic = in_array('violence/graphic', $flags);
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'flagged' => $this->isFlagged,
            'categories' => [
                'hate' => $this->isHate,
                'hate/threatening' => $this->isHateThreatening,
                'harassment' => $this->isHarassment,
                'harassment/threatening' => $this->isHarassmentThreatening,
                'self-harm' => $this->isSefHarm,
                'self-harm/intent' => $this->isSefHarmIntent,
                'self-harm/instructions' => $this->isSefHarmInstructions,
                'sexual' => $this->isSexual,
                'sexual/minors' => $this->isSexualMinors,
                'violence' => $this->isViolence,
                'violence/graphic' => $this->isViolenceGraphic,
            ]
        ];
    }
}
