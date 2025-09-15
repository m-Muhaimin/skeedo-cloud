<?php

declare(strict_types=1);

namespace Voice\Domain\Entities;

use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\Visibility;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\Position;
use User\Domain\Entities\UserEntity;
use Voice\Domain\ValueObjects\ExternalId;
use Voice\Domain\ValueObjects\SampleUrl;
use Voice\Domain\ValueObjects\UseCase;
use Voice\Domain\ValueObjects\Name;
use Voice\Domain\ValueObjects\Tone;
use Voice\Domain\ValueObjects\Accent;
use Voice\Domain\ValueObjects\Age;
use Voice\Domain\ValueObjects\Gender;
use Voice\Domain\ValueObjects\LanguageCode;
use Voice\Domain\ValueObjects\Provider;
use Voice\Domain\ValueObjects\Status;
use Workspace\Domain\Entities\WorkspaceEntity;

#[ORM\Entity]
#[ORM\Table(name: 'voice')]
#[ORM\HasLifecycleCallbacks]
class VoiceEntity
{
    /** A unique numeric identifier of the entity. */
    #[ORM\Embedded(class: Id::class, columnPrefix: false)]
    private Id $id;

    #[ORM\Column(type: Types::SMALLINT, enumType: Status::class, name: 'status')]
    private Status $status;

    #[ORM\Embedded(class: Provider::class, columnPrefix: false)]
    private Provider $provider;

    #[ORM\Embedded(class: Model::class, columnPrefix: false)]
    private Model $model;

    #[ORM\Embedded(class: ExternalId::class, columnPrefix: false)]
    private ExternalId $externalId;

    #[ORM\Embedded(class: Name::class, columnPrefix: false)]
    private Name $name;

    #[ORM\Embedded(class: SampleUrl::class, columnPrefix: false)]
    private SampleUrl $sampleUrl;

    #[ORM\Column(type: Types::SMALLINT, enumType: Visibility::class, name: 'visibility')]
    private Visibility $visibility;

    /** @var array<string> */
    #[ORM\Column(type: Types::SIMPLE_ARRAY, name: 'tones', nullable: true)]
    private ?array $tone = null;

    /** @var array<string> */
    #[ORM\Column(type: Types::SIMPLE_ARRAY, name: 'use_cases', nullable: true)]
    private ?array $useCase = null;

    #[ORM\Column(type: Types::STRING, enumType: Gender::class, name: 'gender', nullable: true, length: 16)]
    private ?Gender $gender = null;

    #[ORM\Column(type: Types::STRING, enumType: Accent::class, name: 'accent', nullable: true, length: 64)]
    private ?Accent $accent = null;

    #[ORM\Column(type: Types::STRING, enumType: Age::class, name: 'age', nullable: true, length: 16)]
    private ?Age $age = null;

    #[ORM\Embedded(class: Position::class, columnPrefix: false)]
    private Position $position;

    /** Creation date and time of the entity */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private DateTimeInterface $createdAt;

    /** The date and time when the entity was last modified. */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'updated_at', nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    /** @var array<string> */
    #[ORM\Column(type: Types::SIMPLE_ARRAY, name: 'supported_languages', nullable: true)]
    private ?array $supportedLanguages = null;

    #[ORM\ManyToOne(targetEntity: WorkspaceEntity::class, inversedBy: 'voices')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?WorkspaceEntity $workspace = null;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?UserEntity $user = null;

    public function __construct(
        Provider $provider,
        Model $model,
        Name $name,
        ExternalId $externalId,
        SampleUrl $sampleUrl,
    ) {
        $this->id = new Id();
        $this->status = Status::ACTIVE;
        $this->provider = $provider;
        $this->model = $model;
        $this->name = $name;
        $this->externalId = $externalId;
        $this->sampleUrl = $sampleUrl;
        $this->createdAt = new DateTimeImmutable();
        $this->visibility = Visibility::PUBLIC;
        $this->position = new Position();
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getProvider(): Provider
    {
        return $this->provider;
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function getExternalId(): ExternalId
    {
        return $this->externalId;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function setName(Name $name): void
    {
        $this->name = $name;
    }

    public function getSampleUrl(): SampleUrl
    {
        return $this->sampleUrl;
    }

    public function setSampleUrl(SampleUrl $sampleUrl): void
    {
        $this->sampleUrl = $sampleUrl;
    }

    public function getVisibility(): Visibility
    {
        return $this->visibility;
    }

    public function setVisibility(Visibility $visibility): void
    {
        $this->visibility = $visibility;
    }

    /** @return array<Tone> */
    public function getTones(): array
    {
        $tones = array_map(
            fn(string $tone) => Tone::create($tone),
            $this->tone ?: []
        );

        return array_filter(
            $tones,
            fn($tone) => $tone !== null
        );
    }

    public function setTones(Tone ...$tones): void
    {
        $this->tone = array_map(
            fn(Tone $tone) => $tone->value,
            $tones
        );
    }

    /** @return array<UseCase> */
    public function getUseCases(): array
    {
        $useCases = array_map(
            fn(string $useCase) => UseCase::create($useCase),
            $this->useCase ?: []
        );

        return array_filter(
            $useCases,
            fn($useCase) => $useCase !== null
        );
    }

    public function setUseCases(UseCase ...$useCase): void
    {
        $this->useCase = array_map(
            fn(UseCase $useCase) => $useCase->value,
            $useCase
        );
    }

    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    public function setGender(?Gender $gender): void
    {
        $this->gender = $gender;
    }

    public function getAccent(): ?Accent
    {
        return $this->accent;
    }

    public function setAccent(?Accent $accent): void
    {
        $this->accent = $accent;
    }

    public function getAge(): ?Age
    {
        return $this->age;
    }

    public function setAge(?Age $age): void
    {
        $this->age = $age;
    }

    public function getPosition(): Position
    {
        return $this->position;
    }

    public function placeBetween(
        ?VoiceEntity $after,
        ?VoiceEntity $before
    ): self {
        if ($after && $before) {
            $position = ($after->getPosition()->value + $before->getPosition()->value) / 2;
        } elseif ($after) {
            $position = $after->getPosition()->value * 1.001;
        } elseif ($before) {
            $position = $before->getPosition()->value * 0.999;
        } else {
            throw new DomainException('Either after or before voice must be provided');
        }

        $this->position = new Position($position);
        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getSupportedLanguages(): array
    {
        $langs = array_map(
            fn(string $lang) => LanguageCode::create($lang),
            $this->supportedLanguages ?: []
        );

        return array_filter(
            $langs,
            fn($lang) => $lang !== null
        );
    }

    public function setSupportedLanguages(
        LanguageCode ...$languages
    ): void {
        $this->supportedLanguages = array_map(
            fn(LanguageCode $lang) => $lang->value,
            $languages
        );
    }

    public function getWorkspace(): ?WorkspaceEntity
    {
        return $this->workspace;
    }

    public function getUser(): ?UserEntity
    {
        return $this->user;
    }

    public function setOwner(
        WorkspaceEntity $workspace,
        UserEntity $user
    ): void {
        $this->workspace = $workspace;
        $this->user = $user;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }
}
