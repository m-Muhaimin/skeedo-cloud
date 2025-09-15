<?php

declare(strict_types=1);

namespace Voice\Application\Commands;

use Ai\Domain\ValueObjects\Visibility;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use Voice\Application\CommandHandlers\UpdateVoiceCommandHandler;
use Voice\Domain\Entities\VoiceEntity;
use Voice\Domain\ValueObjects\Accent;
use Voice\Domain\ValueObjects\Age;
use Voice\Domain\ValueObjects\Gender;
use Voice\Domain\ValueObjects\Name;
use Voice\Domain\ValueObjects\SampleUrl;
use Voice\Domain\ValueObjects\Status;
use Voice\Domain\ValueObjects\Tone;
use Voice\Domain\ValueObjects\UseCase;

#[Handler(UpdateVoiceCommandHandler::class)]
class UpdateVoiceCommand
{
    public Id|VoiceEntity $voice;

    public ?Visibility $visibility = null;
    public ?Status $status = null;
    public ?Name $name = null;
    public ?SampleUrl $sampleUrl = null;

    /** @var null|array<Tone> */
    public ?array $tones = null;

    /** @var null|array<UseCase> */
    public ?array $useCases = null;

    public null|false|Gender $gender = false;
    public ?Accent $accent = null;
    public null|false|Age $age = false;

    public ?Id $before = null;
    public ?Id $after = null;

    public function __construct(string|Id|VoiceEntity $voice)
    {
        $this->voice =  is_string($voice) ? new Id($voice) : $voice;
    }

    public function setStatus(int $status): void
    {
        $this->status = Status::from($status);
    }

    public function setName(string|Name $name): void
    {
        $this->name = $name instanceof Name ? $name : new Name($name);
    }

    public function setSampleUrl(null|string|SampleUrl $sampleUrl): void
    {
        $this->sampleUrl = $sampleUrl instanceof SampleUrl
            ? $sampleUrl : new SampleUrl($sampleUrl);
    }

    public function setTones(string|Tone ...$tones): void
    {
        $tones = array_map(
            fn($tone) => $tone instanceof Tone
                ? $tone : Tone::tryFrom($tone),
            $tones
        );

        $this->tones = array_filter($tones);
    }

    public function setUseCases(string|UseCase ...$useCases): void
    {
        $useCases = array_map(
            fn($useCase) => $useCase instanceof UseCase
                ? $useCase : UseCase::tryFrom($useCase),
            $useCases
        );

        $this->useCases = array_filter($useCases);
    }

    public function setGender(null|string|Gender $gender): void
    {
        $this->gender = is_string($gender)
            ? Gender::from($gender)
            : $gender;
    }

    public function setAccent(null|string|Accent $accent): void
    {
        $this->accent = $accent instanceof Accent
            ? $accent : Accent::from($accent);
    }

    public function setAge(null|string|Age $age): void
    {
        $this->age = is_string($age)
            ? Age::from($age)
            : $age;
    }

    public function setVisibility(int $visibility): void
    {
        $this->visibility = Visibility::from($visibility);
    }

    public function setBefore(string|Id $before): void
    {
        $this->before = $before instanceof Id ? $before : new Id($before);
    }

    public function setAfter(string|Id $after): void
    {
        $this->after = $after instanceof Id ? $after : new Id($after);
    }
}
