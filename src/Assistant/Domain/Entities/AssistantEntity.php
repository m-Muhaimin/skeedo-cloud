<?php

declare(strict_types=1);

namespace Assistant\Domain\Entities;

use Assistant\Domain\ValueObjects\Description;
use Ai\Domain\ValueObjects\Instructions;
use Ai\Domain\ValueObjects\Model;
use Assistant\Domain\ValueObjects\Name;
use Assistant\Domain\ValueObjects\Status;
use Assistant\Domain\ValueObjects\AvatarUrl;
use Assistant\Domain\ValueObjects\Expertise;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Dataset\Domain\Entities\AbstractDataUnitEntity;
use DateTime;
use DomainException;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\Position;
use Traversable;

#[ORM\Entity]
#[ORM\Table(name: 'assistant')]
#[ORM\HasLifecycleCallbacks]
class AssistantEntity
{
    /** A unique numeric identifier of the entity. */
    #[ORM\Embedded(class: Id::class, columnPrefix: false)]
    private Id $id;

    #[ORM\Embedded(class: Name::class, columnPrefix: false)]
    private Name $name;

    #[ORM\Embedded(class: Expertise::class, columnPrefix: false)]
    private Expertise $expertise;

    #[ORM\Embedded(class: Description::class, columnPrefix: false)]
    private Description $description;

    #[ORM\Embedded(class: Instructions::class, columnPrefix: false)]
    private Instructions $instructions;

    #[ORM\Embedded(class: AvatarUrl::class, columnPrefix: false)]
    private AvatarUrl $avatar;

    #[ORM\Embedded(class: Model::class, columnPrefix: false)]
    private Model $model;

    #[ORM\Column(type: Types::SMALLINT, enumType: Status::class, name: 'status')]
    private Status $status;

    #[ORM\Embedded(class: Position::class, columnPrefix: false)]
    private Position $position;

    /** Creation date and time of the entity */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private DateTimeInterface $createdAt;

    /** The date and time when the entity was last modified. */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'updated_at', nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    /** @var Collection<int,AbstractDataUnitEntity> */
    #[ORM\ManyToMany(targetEntity: AbstractDataUnitEntity::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinTable(name: 'assistant_data_unit')]
    #[ORM\JoinColumn(name: 'assistant_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'data_unit_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $dataset;

    public function __construct(Name $name)
    {
        $this->id = new Id();
        $this->name = $name;
        $this->expertise = new Expertise();
        $this->description = new Description();
        $this->instructions = new Instructions();
        $this->avatar = new AvatarUrl();
        $this->model = new Model();
        $this->status = Status::ACTIVE;
        $this->position = new Position();
        $this->createdAt = new DateTimeImmutable();

        $this->dataset = new ArrayCollection();
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function setName(Name $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getExpertise(): Expertise
    {
        return $this->expertise;
    }

    public function setExpertise(Expertise $expertise): self
    {
        $this->expertise = $expertise;
        return $this;
    }

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function setDescription(Description $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getInstructions(): Instructions
    {
        return $this->instructions;
    }

    public function setInstructions(Instructions $instructions): self
    {
        $this->instructions = $instructions;
        return $this;
    }

    public function getAvatar(): AvatarUrl
    {
        return $this->avatar;
    }

    public function setAvatar(AvatarUrl $avatar): self
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function setModel(Model $model): self
    {
        $this->model = $model;
        return $this;
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

    public function getPosition(): Position
    {
        return $this->position;
    }

    public function placeBetween(
        ?AssistantEntity $after,
        ?AssistantEntity $before
    ): self {
        if ($after && $before) {
            $position = ($after->getPosition()->value + $before->getPosition()->value) / 2;
        } elseif ($after) {
            $position = $after->getPosition()->value * 1.001;
        } elseif ($before) {
            $position = $before->getPosition()->value * 0.999;
        } else {
            throw new DomainException('Either after or before assistant must be provided');
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

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }

    public function addDataUnit(AbstractDataUnitEntity $unit): void
    {
        $this->dataset->add($unit);
    }

    public function removeDataUnit(Id|AbstractDataUnitEntity $unit): void
    {
        if ($unit instanceof Id) {
            $unit = $this->dataset->filter(
                fn(AbstractDataUnitEntity $dataUnit) => $dataUnit->getId()->equals($unit)
            )->first();
        }

        $this->dataset->removeElement($unit);
    }

    /**
     * @return Traversable<AbstractDataUnitEntity>
     */
    public function getDataset(): Traversable
    {
        return $this->dataset->getIterator();
    }

    public function hasDataset(): bool
    {
        return !$this->dataset->isEmpty();
    }
}
