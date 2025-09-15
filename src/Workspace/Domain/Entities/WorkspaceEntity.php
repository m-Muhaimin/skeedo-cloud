<?php

declare(strict_types=1);

namespace Workspace\Domain\Entities;

use Billing\Domain\Entities\SubscriptionEntity;
use Billing\Domain\ValueObjects\CreditCount;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Exception;
use Shared\Domain\ValueObjects\Id;
use Traversable;
use User\Domain\Entities\UserEntity;
use Voice\Domain\Entities\VoiceEntity;
use Workspace\Domain\Exceptions\InvitationNotFoundException;
use Workspace\Domain\Exceptions\MemberAlreadyJoinedException;
use Workspace\Domain\Exceptions\MemberCapExceededException;
use Workspace\Domain\Exceptions\WorkspaceUserNotFoundException;
use Workspace\Domain\ValueObjects\Address;
use Workspace\Domain\ValueObjects\ApiKey;
use Workspace\Domain\ValueObjects\Email;
use Workspace\Domain\ValueObjects\Name;

#[ORM\Entity]
#[ORM\Table(name: 'workspace')]
#[ORM\HasLifecycleCallbacks]
class WorkspaceEntity
{
    /** A unique numeric identifier of the entity. */
    #[ORM\Embedded(class: Id::class, columnPrefix: false)]
    private Id $id;

    #[ORM\Embedded(class: Name::class, columnPrefix: false)]
    private Name $name;

    #[ORM\Embedded(class: CreditCount::class, columnPrefix: 'credit_')]
    protected CreditCount $creditCount;

    #[ORM\Embedded(class: CreditCount::class, columnPrefix: 'allocated_credit_')]
    protected CreditCount $allocated;

    #[ORM\Column(type: Types::BOOLEAN, name: 'is_trialed', options: ['default' => false])]
    private bool $isTrialed = false;

    #[ORM\Column(type: Types::JSON, name: "address", nullable: true)]
    protected null|array|Address $address = null;

    #[ORM\Embedded(class: ApiKey::class, columnPrefix: 'openai_')]
    private ApiKey $openaiApiKey;

    #[ORM\Embedded(class: ApiKey::class, columnPrefix: 'anthropic_')]
    private ApiKey $anthropicApiKey;

    /** Creation date and time of the entity */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private DateTimeInterface $createdAt;

    /** The date and time when the entity was last modified. */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'updated_at', nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    /** The date and time when the entity was last modified. */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'credits_adjusted_at', nullable: true)]
    private ?DateTimeInterface $creditsAdjustedAt = null;

    #[ORM\ManyToOne(targetEntity: UserEntity::class, inversedBy: 'ownedWorkspaces')]
    private UserEntity $owner;

    /** @var Collection<int,UserEntity> */
    #[ORM\ManyToMany(targetEntity: UserEntity::class, inversedBy: 'workspaces')]
    #[ORM\JoinTable(name: 'workspace_user')]
    #[ORM\JoinColumn(name: 'workspace_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private Collection $users;

    /** @var Collection<int,WorkspaceInvitationEntity> */
    #[ORM\OneToMany(targetEntity: WorkspaceInvitationEntity::class, mappedBy: 'workspace', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $invitations;

    /** @var Collection<int,SubscriptionEntity> */
    #[ORM\OneToMany(targetEntity: SubscriptionEntity::class, mappedBy: 'workspace', cascade: ['persist', 'remove'])]
    private Collection $subscriptions;

    /** @var Collection<int,VoiceEntity> */
    #[ORM\OneToMany(targetEntity: VoiceEntity::class, mappedBy: 'workspace', cascade: ['persist', 'remove'])]
    private Collection $voices;

    #[ORM\OneToOne(targetEntity: SubscriptionEntity::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?SubscriptionEntity $subscription = null;

    public function __construct(UserEntity $user, Name $name)
    {
        $this->id = new Id();
        $this->name = $name;
        $this->creditCount = new CreditCount(0);
        $this->openaiApiKey = new ApiKey();
        $this->anthropicApiKey = new ApiKey();
        $this->createdAt = new DateTimeImmutable();
        $this->owner = $user;
        $this->users = new ArrayCollection();
        $this->invitations = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
        $this->voices = new ArrayCollection();
        $this->allocated = new CreditCount(0);
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

    public function getCreditCount(): CreditCount
    {
        return $this->creditCount;
    }

    public function setCreditCount(CreditCount $count): self
    {
        $this->creditCount = $count;
        $this->creditsAdjustedAt = new DateTime();
        return $this;
    }

    public function getTotalCreditCount(): CreditCount
    {
        $sub = $this->getSubscription();

        $totalCreditCount = $this->getCreditCount()->value;
        if (!is_null($totalCreditCount) && $sub) {
            $subCreditCount = $sub->getCredit()->value;

            $totalCreditCount = is_null($subCreditCount)
                ? null : (float) $totalCreditCount + (float) $subCreditCount;
        }

        return new CreditCount(is_null($totalCreditCount) ? null : (float) $totalCreditCount);
    }

    public function addCredits(CreditCount $count): self
    {
        if (is_null($count->value) || is_null($this->creditCount->value)) {
            $this->creditCount = new CreditCount(); // Unlimited
            return $this;
        }

        $this->creditCount = new CreditCount(
            (float) $this->creditCount->value + (float) $count->value
        );

        return $this;
    }

    public function deductCredit(
        CreditCount $count,
        bool $allowNegative = false
    ): self {
        if ($this->subscription) {
            $count = $this->subscription->deductCredit($count);
        }

        if ((float) $count->value <= 0) {
            return $this;
        }

        if ($this->creditCount->value === null) {
            // Has unlimited add-on credits
            return $this;
        }

        $val = (float) $this->creditCount->value - (float) $count->value;
        if ($val < 0 && !$allowNegative) {
            $val = 0;
        }

        $this->creditCount = new CreditCount($val);
        return $this;
    }

    public function allocate(CreditCount $count): self
    {
        if ($count->value < 0) {
            throw new DomainException('Allocated credit cannot be negative');
        }

        if (!$this->hasSufficientCredit($count)) {
            throw new DomainException('Insufficient credit to allocate');
        }

        $this->allocated = new CreditCount(
            (float) $this->allocated->value + (float) $count->value
        );

        return $this;
    }

    public function unallocate(CreditCount $count): self
    {
        $this->allocated = new CreditCount(
            (float) $this->allocated->value - (float) $count->value
        );

        if ($this->allocated->value < 0) {
            $this->allocated = new CreditCount(0);
        }

        return $this;
    }

    public function hasSufficientCredit(CreditCount $count): bool
    {
        return
            is_null($this->getTotalCreditCount()->value)
            || $this->getTotalCreditCount()->value - $this->allocated->value >= $count->value;
    }

    public function isEligibleForTrial(): bool
    {
        return !$this->isTrialed;
    }

    public function isEligibleForFreePlan(): bool
    {
        $owner = $this->owner;

        foreach ($owner->getOwnedWorkspaces() as $ws) {
            if (!$ws->getSubscription()) {
                continue;
            }

            if ($ws->getId()->equals($this->id)) {
                continue;
            }

            if ($ws->getSubscription()->getPlan()->getPrice()->value == 0) {
                return false;
            }
        }

        return true;
    }

    public function getAddress(): ?Address
    {
        if (is_array($this->address)) {
            $this->address = new Address($this->address);
        }

        return $this->address;
    }

    public function setAddress(Address $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getOpenaiApiKey(): ApiKey
    {
        return $this->openaiApiKey;
    }

    public function setOpenaiApiKey(ApiKey $key): self
    {
        $this->openaiApiKey = $key;
        return $this;
    }

    public function getAnthropicApiKey(): ApiKey
    {
        return $this->anthropicApiKey;
    }

    public function setAnthropicApiKey(ApiKey $key): self
    {
        $this->anthropicApiKey = $key;
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

    public function getCreditsAdjustedAt(): ?DateTimeInterface
    {
        return $this->creditsAdjustedAt;
    }

    public function getOwner(): UserEntity
    {
        return $this->owner;
    }

    public function setOwner(UserEntity|Id $owner): self
    {
        if ($owner instanceof Id) {
            $owner = $this->getUserById($owner);
        }

        if (
            $owner->getId()->getValue()->toString()
            === $this->owner->getId()->getValue()->toString()
        ) {
            return $this;
        }

        $this->addUser($this->owner);
        $this->removeUser($owner);
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Traversable<UserEntity>
     * @throws Exception
     */
    public function getUsers(): Traversable
    {
        return $this->users->getIterator();
    }

    public function addUser(UserEntity $user): self
    {
        $this->users->add($user);
        return $this;
    }

    /**
     * @throws WorkspaceUserNotFoundException
     * @throws Exception
     */
    public function removeUser(UserEntity|Id $user): self
    {
        if ($user instanceof Id) {
            $user = $this->getUserById($user);
        }

        $this->users->removeElement($user);
        $user->setCurrentWorkspace($user->getOwnedWorkspaces()[0]);
        return $this;
    }

    /**
     * @throws WorkspaceUserNotFoundException
     */
    private function getUserById(Id $id): UserEntity
    {
        /** @var UserEntity */
        foreach ($this->users as $user) {
            if ($user->getId()->getValue() == $id->getValue()) {
                return $user;
            }
        }

        throw new WorkspaceUserNotFoundException($id);
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }

    /**
     * @return Traversable<WorkspaceInvitationEntity>
     * @throws Exception
     */
    public function getInvitations(): Traversable
    {
        return $this->invitations->getIterator();
    }

    /**
     * @throws MemberAlreadyJoinedException
     */
    public function invite(Email $email): WorkspaceInvitationEntity
    {
        if ($this->owner->getEmail()->value == $email->value) {
            throw new MemberAlreadyJoinedException($email);
        }

        // Check if member is already in workspace
        foreach ($this->users as $user) {
            if ($user->getEmail()->value == $email->value) {
                throw new MemberAlreadyJoinedException($email);
            }
        }

        foreach ($this->invitations as $invitation) {
            if ($invitation->getEmail()->value == $email->value) {
                return $invitation;
            }
        }

        $plan = $this->getSubscription()?->getPlan();
        $cap = $plan?->getMemberCap()->value;

        if (
            $cap !== null
            && $cap <= $this->users->count() + $this->invitations->count()
        ) {
            throw new MemberCapExceededException();
        }

        $invitation = new WorkspaceInvitationEntity($this, $email);
        $this->invitations->add($invitation);

        return $invitation;
    }

    /**
     * @throws InvitationNotFoundException
     */
    public function removeInvitation(WorkspaceInvitationEntity|Id $invitation): self
    {
        if ($invitation instanceof Id) {
            $invitation = $this->getInvitationById($invitation);
        }

        $this->invitations->removeElement($invitation);
        return $this;
    }

    /**
     * @throws InvitationNotFoundException
     */
    public function acceptInvitation(
        WorkspaceInvitationEntity|Id $invitation,
        UserEntity $user
    ): self {
        if ($invitation instanceof Id) {
            $invitation = $this->getInvitationById($invitation);
        }

        if ($invitation->getEmail()->value != $user->getEmail()->value) {
            throw new InvitationNotFoundException($invitation->getId());
        }

        $this->invitations->removeElement($invitation);
        $this->addUser($user);

        return $this;
    }

    /**
     * @throws InvitationNotFoundException
     */
    public function getInvitationById(Id $id): WorkspaceInvitationEntity
    {
        /** @var WorkspaceInvitationEntity */
        foreach ($this->invitations as $invitation) {
            if ($invitation->getId()->getValue() == $id->getValue()) {
                return $invitation;
            }
        }

        throw new InvitationNotFoundException($id);
    }

    public function addSubscription(SubscriptionEntity $sub): self
    {
        $this->subscriptions->add($sub);
        return $this;
    }

    public function subscribe(SubscriptionEntity $sub): self
    {
        $this->subscription = $sub;

        if ($sub->getTrialPeriodDays()->value > 0) {
            $this->isTrialed = true;
        }

        $snapshot = $sub->getPlan();
        $balance = $this->getCreditCount();

        if ($balance->value !== null && (float) $balance->value < 0) {
            $max = $snapshot->getCreditCount();
            $debt = abs((float) $balance->value);

            if ($debt <= (float) $max->value) {
                $sub->deductCredit(new CreditCount($debt));
            } else {
                $sub->deductCredit($max);
            }

            $this->addCredits($sub->getUsageCount());
        }

        return $this;
    }

    public function removeSubscription(): self
    {
        $this->subscription = null;
        return $this;
    }

    public function getSubscription(): ?SubscriptionEntity
    {
        return $this->subscription;
    }

    public function getVoiceCount(): int
    {
        return $this->voices->count();
    }

    public function isVoiceCapExceeded(): bool
    {
        if (!$this->subscription) {
            return true;
        }

        $plan = $this->getSubscription()->getPlan();
        $cap = $plan->getConfig()->voiceover->cap;

        if ($cap === null) {
            // Unliomited
            return false;
        }

        if ($cap === 0) {
            // Voice cloning is disabled for this plan
            return true;
        }

        return $this->getVoiceCount() >= $cap;
    }
}
