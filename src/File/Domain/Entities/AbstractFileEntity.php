<?php

declare(strict_types=1);

namespace File\Domain\Entities;

use Ai\Domain\ValueObjects\Embedding;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use File\Domain\ValueObjects\ObjectKey;
use File\Domain\ValueObjects\Size;
use File\Domain\ValueObjects\Storage;
use File\Domain\ValueObjects\Url;
use Shared\Domain\ValueObjects\Id;

#[ORM\Entity]
#[ORM\Table(name: 'file')]
#[ORM\HasLifecycleCallbacks]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: "discr", type: Types::STRING)]
#[ORM\DiscriminatorMap([
    'file' => FileEntity::class,
    'image' => ImageFileEntity::class,
])]

abstract class AbstractFileEntity
{
    /** A unique numeric identifier of the entity. */
    #[ORM\Embedded(class: Id::class, columnPrefix: false)]
    private Id $id;

    #[ORM\Embedded(class: Storage::class, columnPrefix: false)]
    private Storage $storage;

    #[ORM\Embedded(class: ObjectKey::class, columnPrefix: false)]
    private ObjectKey $objectKey;

    #[ORM\Embedded(class: Url::class, columnPrefix: false)]
    private Url $url;

    #[ORM\Embedded(class: Size::class, columnPrefix: false)]
    private Size $size;

    #[ORM\Embedded(class: Embedding::class, columnPrefix: false)]
    private Embedding $embedding;

    /** Creation date and time of the entity */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private DateTimeInterface $createdAt;

    /** The date and time when the entity was last modified. */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'updated_at', nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    public function __construct(
        Storage $storage,
        ObjectKey $objectKey,
        Url $url,
        Size $size,
        ?Embedding $embedding = null
    ) {
        $this->id = new Id();

        $this->storage = $storage;
        $this->objectKey = $objectKey;
        $this->url = $url;
        $this->size = $size;
        $this->embedding = $embedding ?? new Embedding();

        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getStorage(): Storage
    {
        return $this->storage;
    }

    public function getObjectKey(): ObjectKey
    {
        return $this->objectKey;
    }

    public function getUrl(): Url
    {
        return $this->url;
    }

    public function getSize(): Size
    {
        return $this->size;
    }

    public function getEmbedding(): Embedding
    {
        return $this->embedding;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }

    public function getExtension(): string
    {
        return strtolower(pathinfo($this->objectKey->value, PATHINFO_EXTENSION));
    }
}
