<?php

declare(strict_types=1);

namespace Preset\Domain\Entities;

use Category\Domain\Entities\CategoryEntity;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Preset\Domain\Exceptions\LockedPresetException;
use Preset\Domain\ValueObjects\Color;
use Preset\Domain\ValueObjects\Description;
use Preset\Domain\ValueObjects\Image;
use Preset\Domain\ValueObjects\Status;
use Preset\Domain\ValueObjects\Template;
use Preset\Domain\ValueObjects\Title;
use Preset\Domain\ValueObjects\Type;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\Position;

#[ORM\Entity]
#[ORM\Table(name: 'preset')]
#[ORM\HasLifecycleCallbacks]
class PresetEntity
{
    /** A unique numeric identifier of the entity. */
    #[ORM\Embedded(class: Id::class, columnPrefix: false)]
    private Id $id;

    #[ORM\Column(type: Types::STRING, enumType: Type::class, name: 'type')]
    private Type $type;

    #[ORM\Column(type: Types::SMALLINT, enumType: Status::class, name: 'status')]
    private Status $status;

    #[ORM\Embedded(class: Title::class, columnPrefix: false)]
    private Title $title;

    #[ORM\Embedded(class: Description::class, columnPrefix: false)]
    private Description $description;

    #[ORM\Embedded(class: Template::class, columnPrefix: false)]
    private Template $template;

    #[ORM\Embedded(class: Image::class, columnPrefix: false)]
    private Image $image;

    #[ORM\Embedded(class: Color::class, columnPrefix: false)]
    private Color $color;

    #[ORM\Embedded(class: Position::class, columnPrefix: false)]
    private Position $position;

    #[ORM\Column(type: Types::BOOLEAN, name: "is_locked", nullable: false)]
    private bool $isLocked = false;

    /** Creation date and time of the entity */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private DateTimeInterface $createdAt;

    /** The date and time when the entity was last modified. */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'updated_at', nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: CategoryEntity::class)]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    private ?CategoryEntity $category = null;

    public function __construct(
        Type $type,
        Title $title,
        Status $status = Status::ACTIVE
    ) {
        $this->id = new Id();
        $this->type = $type;
        $this->status = $status;
        $this->title = $title;
        $this->description = new Description();
        $this->template = new Template();
        $this->image = new Image();
        $this->color = new Color();
        $this->position = new Position();
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getType(): Type
    {
        return $this->type;
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

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function setTitle(Title $title): self
    {
        $this->title = $title;
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

    public function getTemplate(): Template
    {
        return $this->template;
    }

    public function setTemplate(Template $template): self
    {
        if ($this->isLocked) {
            throw new LockedPresetException();
        }

        $this->template = $template;
        return $this;
    }

    public function getImage(): Image
    {
        return $this->image;
    }

    public function setImage(Image $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getColor(): Color
    {
        return $this->color;
    }

    public function setColor(Color $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getPosition(): Position
    {
        return $this->position;
    }

    public function placeBetween(
        ?PresetEntity $after,
        ?PresetEntity $before
    ): self {
        if ($after && $before) {
            $position = ($after->getPosition()->value + $before->getPosition()->value) / 2;
        } elseif ($after) {
            $position = $after->getPosition()->value * 1.001;
        } elseif ($before) {
            $position = $before->getPosition()->value * 0.999;
        } else {
            throw new DomainException('Either after or before preset must be provided');
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

    public function getCategory(): ?CategoryEntity
    {
        return $this->category;
    }

    public function setCategory(?CategoryEntity $category): self
    {
        $this->category = $category;
        return $this;
    }

    /** 
     * Locked presets' template and settings cannot be modified.
     * Once a preset is locked, it cannot be unlocked.
     * 
     * @return void
     */
    public function lock(): void
    {
        $this->isLocked = true;
    }

    public function isLocked(): bool
    {
        return $this->isLocked;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }
}
