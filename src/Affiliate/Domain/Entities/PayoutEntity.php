<?php

declare(strict_types=1);

namespace Affiliate\Domain\Entities;

use Affiliate\Domain\ValueObjects\Amount;
use Affiliate\Domain\ValueObjects\Status;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Shared\Domain\ValueObjects\Id;

#[ORM\Entity]
#[ORM\Table(name: 'payout')]
#[ORM\HasLifecycleCallbacks]
class PayoutEntity
{
    /** A unique numeric identifier of the entity. */
    #[ORM\Embedded(class: Id::class, columnPrefix: false)]
    private Id $id;

    #[ORM\Column(type: Types::STRING, enumType: Status::class, name: 'status')]
    private Status $status;

    #[ORM\Embedded(class: Amount::class, columnPrefix: false)]
    private Amount $amount;

    /** Creation date and time of the entity */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private DateTimeInterface $createdAt;

    /** The date and time when the entity was last modified. */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'updated_at', nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: AffiliateEntity::class, inversedBy: 'payouts')]
    #[ORM\JoinColumn(nullable: false)]
    private AffiliateEntity $affiliate;

    public function __construct(
        AffiliateEntity $affiliate,
        Amount $amount,
    ) {
        $this->id = new Id();
        $this->affiliate = $affiliate;
        $this->amount = $amount;
        $this->status = Status::PENDING;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getAffiliate(): AffiliateEntity
    {
        return $this->affiliate;
    }

    public function approve(): void
    {
        if ($this->status == Status::PENDING) {
            $this->affiliate->approvePayout($this);
            $this->status = Status::APPROVED;
        }
    }

    public function reject(): void
    {
        if ($this->status === Status::PENDING) {
            $this->affiliate->rejectPayout($this);
            $this->status = Status::REJECTED;
        }
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }
}
