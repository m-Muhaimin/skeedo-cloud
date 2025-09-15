<?php

declare(strict_types=1);

namespace Ai\Domain\Entities;

use Ai\Domain\Exceptions\DomainException;
use Ai\Domain\ValueObjects\Meta;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\Progress;
use Ai\Domain\ValueObjects\RequestParams;
use Ai\Domain\ValueObjects\State;
use Ai\Domain\ValueObjects\Title;
use Ai\Domain\ValueObjects\Visibility;
use Billing\Domain\ValueObjects\CreditCount;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use File\Domain\Entities\FileEntity;
use Shared\Domain\ValueObjects\Id;
use Traversable;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[ORM\Entity]
#[ORM\Table(name: 'library_item')]
#[ORM\HasLifecycleCallbacks]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: "discr", type: Types::STRING)]
#[ORM\DiscriminatorMap([
    'document' => DocumentEntity::class,
    'code_document' => CodeDocumentEntity::class,
    'image' => ImageEntity::class,
    'transcription' => TranscriptionEntity::class,
    'speech' => SpeechEntity::class,
    'conversation' => ConversationEntity::class,
    'isolated_voice' => IsolatedVoiceEntity::class,
    'classification' => ClassificationEntity::class,
    'composition' => CompositionEntity::class,
    'video' => VideoEntity::class,
    'memory' => MemoryEntity::class,
])]
abstract class AbstractLibraryItemEntity
{
    /** A unique numeric identifier of the entity. */
    #[ORM\Embedded(class: Id::class, columnPrefix: false)]
    private Id $id;

    #[ORM\Embedded(class: Model::class, columnPrefix: false)]
    private Model $model;

    #[ORM\Column(type: Types::SMALLINT, enumType: Visibility::class, name: 'visibility')]
    protected Visibility $visibility;

    /** This is required for all kind of items for the search function to work properly */
    #[ORM\Embedded(class: Title::class, columnPrefix: false)]
    private Title $title;

    #[ORM\Embedded(class: CreditCount::class, columnPrefix: 'used_credit_')]
    protected CreditCount $cost;

    /** Creation date and time of the entity */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private DateTimeInterface $createdAt;

    /** The date and time when the entity was last modified. */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'updated_at', nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: WorkspaceEntity::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private WorkspaceEntity $workspace;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private UserEntity $user;

    #[ORM\Column(type: Types::SMALLINT, enumType: State::class, name: 'state')]
    protected State $state;

    #[ORM\Embedded(class: Progress::class, columnPrefix: false)]
    protected Progress $progress;

    #[ORM\Embedded(class: Meta::class, columnPrefix: false)]
    protected Meta $meta;

    #[ORM\Column(type: Types::JSON, name: 'request_params')]
    private $requestParams; //! Fix this

    public function __construct(
        WorkspaceEntity $workspace,
        UserEntity $user,
        Model $model,
        ?Title $title = null,
        ?RequestParams $requestParams = null,
        ?CreditCount $cost = null,
        ?Visibility $visibility = null,
    ) {
        $this->id = new Id();
        $this->visibility = $visibility ?? Visibility::PRIVATE;
        $this->cost = $cost ?? new CreditCount(0);
        $this->model = $model;
        $this->title = $title ?? new Title;
        $this->requestParams = $requestParams ?: new RequestParams;
        $this->createdAt = new DateTimeImmutable();
        $this->workspace = $workspace;
        $this->user = $user;
        $this->state = State::DRAFT;
        $this->progress = new Progress();
        $this->meta = new Meta();
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getVisibility(): Visibility
    {
        return $this->visibility;
    }

    public function setVisibility(Visibility $visibility): void
    {
        $this->visibility = $visibility;
    }

    public function getCost(): CreditCount
    {
        return $this->cost;
    }

    public function setCost(CreditCount $cost): void
    {
        $this->cost = $cost;
    }

    public function addCost(CreditCount $cost): self
    {
        $this->cost = new CreditCount(
            (float) $this->cost->value + (float) $cost->value
        );

        return $this;
    }

    public function deductCost(CreditCount $cost): self
    {
        $value = (float) $this->cost->value - (float) $cost->value;

        if ($value < 0) {
            $value = 0;
        }

        $this->cost = new CreditCount($value);

        return $this;
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function setTitle(Title $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getRequestParams(): RequestParams
    {
        return is_array($this->requestParams)
            ? RequestParams::fromArray($this->requestParams) : $this->requestParams;
    }

    public function setRequestParams(RequestParams $requestParams): void
    {
        $this->requestParams = $requestParams;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getWorkspace(): WorkspaceEntity
    {
        return $this->workspace;
    }

    public function getUser(): UserEntity
    {
        return $this->user;
    }

    public function getState(): State
    {
        return $this->state;
    }

    public function setState(State $state): void
    {
        $this->state = $state;
    }

    public function getProgress(): Progress
    {
        return $this->progress;
    }

    public function setProgress(Progress $progress): void
    {
        $this->progress = $progress;
    }

    public function hasMeta(string $key): bool
    {
        return array_key_exists($key, $this->meta->data ?? []);
    }

    public function getMeta(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->meta;
        }

        return $this->meta->data[$key] ?? null;
    }

    public function setMeta(Meta $meta): void
    {
        $this->meta = $meta;
    }

    public function addMeta(string $key, mixed $value): self
    {
        $data = $this->meta->data ?? [];
        $data[$key] = $value;
        $this->meta = new Meta($data);

        return $this;
    }

    public function removeMeta(string $key): self
    {
        $data = $this->meta->data ?? [];
        unset($data[$key]);
        $this->meta = new Meta($data);

        return $this;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }

    /**
     * @return Traversable<FileEntity>
     */
    public function getFiles(): Traversable
    {
        yield from [];
    }
}
