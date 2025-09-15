<?php

declare(strict_types=1);

namespace Workspace\Domain\Entities;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Shared\Domain\ValueObjects\Id;
use Workspace\Domain\ValueObjects\Email;

#[ORM\Entity]
#[ORM\Table(name: 'workspace_invitation')]
#[ORM\UniqueConstraint(columns: ['email', 'workspace_id'])]
#[ORM\HasLifecycleCallbacks]
class WorkspaceInvitationEntity
{
    /** A unique numeric identifier of the entity. */
    #[ORM\Embedded(class: Id::class, columnPrefix: false)]
    private Id $id;

    #[ORM\Embedded(class: Email::class, columnPrefix: false)]
    private Email $email;

    /** Creation date and time of the entity */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private DateTimeInterface $createdAt;

    /** The date and time when the entity was last modified. */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'updated_at', nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: WorkspaceEntity::class, inversedBy: 'invitations')]
    private WorkspaceEntity $workspace;

    public function __construct(
        WorkspaceEntity $workspace,
        Email $email
    ) {
        $this->id = new Id();
        $this->email = $email;
        $this->createdAt = new DateTimeImmutable();
        $this->workspace = $workspace;
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getEmail(): Email
    {
        return $this->email;
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
    public function setUpdatedAt(): void
    {
        $this->updatedAt = new DateTime();
    }

    public function getWorkspace(): WorkspaceEntity
    {
        return $this->workspace;
    }
}
