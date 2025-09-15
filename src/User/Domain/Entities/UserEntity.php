<?php

declare(strict_types=1);

namespace User\Domain\Entities;

use Affiliate\Domain\Entities\AffiliateEntity;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObjects\CityName;
use Shared\Domain\ValueObjects\CountryCode;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\Ip;
use Traversable;
use User\Domain\Exceptions\InvalidPasswordException;
use User\Domain\Exceptions\InvalidTokenException;
use User\Domain\ValueObjects\ApiKey;
use User\Domain\ValueObjects\Email;
use User\Domain\ValueObjects\EmailVerificationToken;
use User\Domain\ValueObjects\FirstName;
use User\Domain\ValueObjects\IsEmailVerified;
use User\Domain\ValueObjects\Language;
use User\Domain\ValueObjects\LastName;
use User\Domain\ValueObjects\Password;
use User\Domain\ValueObjects\PasswordHash;
use User\Domain\ValueObjects\RecoveryToken;
use User\Domain\ValueObjects\Role;
use User\Domain\ValueObjects\Status;
use User\Domain\ValueObjects\WorkspaceCap;
use User\Domain\Exceptions\OwnedWorkspaceCapException;
use User\Domain\ValueObjects\PhoneNumber;
use User\Domain\ValueObjects\Preferences;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;
use Workspace\Domain\ValueObjects\Name;

#[ORM\Entity]
#[ORM\Table(name: 'user')]
#[ORM\Index(columns: ['first_name'])]
#[ORM\Index(columns: ['last_name'])]
#[ORM\HasLifecycleCallbacks]
class UserEntity
{
    /** Number of seconds after which a user is considered offline */
    const ONLINE_THRESHOLD = 300; // 5 minutes

    /** A unique numeric identifier of the entity. */
    #[ORM\Embedded(class: Id::class, columnPrefix: false)]
    private Id $id;

    #[ORM\Column(type: Types::SMALLINT, enumType: Role::class, name: 'role')]
    private Role $role;

    /** The email of the user entity */
    #[ORM\Embedded(class: Email::class, columnPrefix: false)]
    private Email $email;

    /** Password hash of the user */
    #[ORM\Embedded(class: PasswordHash::class, columnPrefix: false)]
    private PasswordHash $passwordHash;

    /** First name of the user entity */
    #[ORM\Embedded(class: FirstName::class, columnPrefix: false)]
    private FirstName $firstName;

    /** Last name of the user entity */
    #[ORM\Embedded(class: LastName::class, columnPrefix: false)]
    private LastName $lastName;

    /** Phone number of the user */
    #[ORM\Embedded(class: PhoneNumber::class, columnPrefix: false)]
    private PhoneNumber $phoneNumber;

    /** Language of the user */
    #[ORM\Embedded(class: Language::class, columnPrefix: false)]
    private Language $language;

    #[ORM\Embedded(class: ApiKey::class, columnPrefix: false)]
    private ApiKey $apiKey;

    #[ORM\Embedded(class: Ip::class, columnPrefix: false)]
    private Ip $ip;

    #[ORM\Column(type: Types::STRING, enumType: CountryCode::class, name: 'country_code', nullable: true)]
    private ?CountryCode $countryCode = null;

    #[ORM\Embedded(class: CityName::class, columnPrefix: false)]
    private CityName $cityName;

    /** Creation date and time of the entity */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private DateTimeInterface $createdAt;

    /** The date and time when the entity was last modified. */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'updated_at', nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    /** The date and time when the entity was last modified. */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'last_seen_at', nullable: true)]
    private ?DateTimeInterface $lastSeenAt = null;

    #[ORM\Column(type: Types::SMALLINT, enumType: Status::class, name: 'status')]
    private Status $status;

    #[ORM\Embedded(class: RecoveryToken::class, columnPrefix: false)]
    private RecoveryToken $recoveryToken;

    #[ORM\Embedded(class: IsEmailVerified::class, columnPrefix: false)]
    private IsEmailVerified $isEmailVerified;

    #[ORM\Embedded(class: EmailVerificationToken::class, columnPrefix: false)]
    private EmailVerificationToken $emailVerificationToken;

    #[ORM\Embedded(class: WorkspaceCap::class, columnPrefix: false)]
    private WorkspaceCap $workspaceCap;

    /** @var Collection<int,WorkspaceEntity> */
    #[ORM\ManyToMany(targetEntity: WorkspaceEntity::class, mappedBy: 'users')]
    private Collection $workspaces;

    /** @var Collection<int,WorkspaceEntity> */
    #[ORM\OneToMany(targetEntity: WorkspaceEntity::class, mappedBy: 'owner', cascade: ['persist', 'remove'])]
    private Collection $ownedWorkspaces;

    #[ORM\ManyToOne(targetEntity: WorkspaceEntity::class)]
    #[ORM\JoinColumn(name: 'current_workspace_id', nullable: true, onDelete: 'SET NULL')]
    private ?WorkspaceEntity $currentWorkspace = null;

    #[ORM\OneToOne(targetEntity: AffiliateEntity::class, cascade: ['persist', 'remove'], mappedBy: 'user')]
    private ?AffiliateEntity $affiliate = null;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: 'referred_by', nullable: true, onDelete: 'SET NULL')]
    private ?UserEntity $referredBy = null;

    #[ORM\Column(type: Types::JSON, name: "preferences", nullable: true)]
    private null|array|Preferences $preferences = null;

    public function __construct(
        Email $email,
        FirstName $firstName,
        LastName $lastName
    ) {
        $this->id = new Id();
        $this->role = Role::USER;
        $this->email = $email;
        $this->passwordHash = new PasswordHash();
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->phoneNumber = new PhoneNumber();
        $this->language = new Language();
        $this->apiKey = new ApiKey();
        $this->ip = new Ip();
        $this->cityName = new CityName();
        $this->createdAt = new DateTimeImmutable();
        $this->status = Status::ACTIVE;
        $this->recoveryToken = new RecoveryToken();

        $this->isEmailVerified = new IsEmailVerified();
        $this->emailVerificationToken = new EmailVerificationToken(Uuid::uuid4()->toString());
        $this->workspaceCap = new WorkspaceCap(0);

        $this->workspaces = new ArrayCollection();
        $this->ownedWorkspaces = new ArrayCollection();

        $this->createDefaultWorkspace();
        $this->createAffiliate();
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setRole(Role $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getFirstName(): FirstName
    {
        return $this->firstName;
    }

    public function setFirstName(FirstName $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): LastName
    {
        return $this->lastName;
    }

    public function setLastName(LastName $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getPhoneNumber(): PhoneNumber
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(PhoneNumber $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function setLanguage(Language $language): self
    {
        $this->language = $language;
        return $this;
    }

    public function getApiKey(): ApiKey
    {
        return $this->apiKey;
    }

    public function generateApiKey(): void
    {
        $this->apiKey = new ApiKey();
    }

    public function getIp(): Ip
    {
        return $this->ip;
    }

    public function setIp(Ip $ip): self
    {
        $this->ip = $ip;
        return $this;
    }

    public function getCountryCode(): ?CountryCode
    {
        return $this->countryCode;
    }

    public function setCountryCode(?CountryCode $countryCode): self
    {
        $this->countryCode = $countryCode;
        return $this;
    }

    public function getCityName(): CityName
    {
        return $this->cityName;
    }

    public function setCityName(CityName $cityName): self
    {
        $this->cityName = $cityName;
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

    public function getLastSeenAt(): ?DateTimeInterface
    {
        return $this->lastSeenAt;
    }

    public function getPreferences(): Preferences
    {
        if (!$this->preferences instanceof Preferences) {
            $this->preferences = new Preferences($this->preferences);
        }

        return $this->preferences;
    }

    public function setPreferences(array|Preferences $preferences): self
    {
        if (is_array($preferences)) {
            $preferences = new Preferences($preferences);
        }

        $this->preferences = $this->getPreferences()->mergeWith($preferences);
        return $this;
    }

    /**
     * Check if the user is online (last seen within the last 5 minutes)
     * 
     * @return bool
     */
    public function isOnline(): bool
    {
        return
            $this->status === Status::ACTIVE
            && $this->lastSeenAt
            && $this->lastSeenAt->getTimestamp() >= time() - self::ONLINE_THRESHOLD;
    }

    /**
     * Update the last seen at timestamp
     * 
     * @return UserEntity
     */
    public function touch(): self
    {
        $this->lastSeenAt = new DateTime();
        return $this;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): self
    {
        if ($status === Status::ONLINE || $status === Status::AWAY) {
            $status = Status::ACTIVE;
        }

        $this->status = $status;
        return $this;
    }

    public function getRecoveryToken(): RecoveryToken
    {
        return $this->recoveryToken;
    }

    public function generateRecoveryToken(): void
    {
        $this->recoveryToken = new RecoveryToken(Uuid::uuid4()->toString());
    }

    public function validateRecoveryToken(RecoveryToken $token): true
    {
        if ($this->recoveryToken->value !== $token->value) {
            throw new InvalidTokenException($this, $token);
        }

        return true;
    }

    public function isEmailVerified(): IsEmailVerified
    {
        return $this->isEmailVerified;
    }

    public function verifyEmail(EmailVerificationToken $token): void
    {
        if ($this->emailVerificationToken->value !== $token->value) {
            throw new InvalidTokenException($this, $token);
        }

        $this->isEmailVerified = new IsEmailVerified(true);
        $this->emailVerificationToken = new EmailVerificationToken();
    }

    public function getEmailVerificationToken(): EmailVerificationToken
    {
        return $this->emailVerificationToken;
    }

    public function unverifyEmail(): void
    {
        $this->isEmailVerified = new IsEmailVerified(false);
        $this->emailVerificationToken = new EmailVerificationToken(
            Uuid::uuid4()->toString()
        );
    }

    public function getWorkspaceCap(): WorkspaceCap
    {
        return $this->workspaceCap;
    }

    public function setWorkspaceCap(WorkspaceCap $workspaceCap): self
    {
        $this->workspaceCap = $workspaceCap;
        return $this;
    }

    /**
     * Password is required to change the email
     *
     * @param Email $email
     * @param Password $password
     * @return UserEntity
     * @throws InvalidPasswordException
     */
    public function updateEmail(Email $email, Password $password): self
    {
        $this->verifyPassword($password);

        $this->email = $email;
        $this->isEmailVerified = new IsEmailVerified(false);
        $this->emailVerificationToken = new EmailVerificationToken(
            Uuid::uuid4()->toString()
        );

        return $this;
    }

    /**
     * Current password is required to change the password
     *
     * @param Password $currentPassword
     * @param Password $password
     * @return UserEntity
     * @throws InvalidPasswordException
     */
    public function updatePassword(
        Password $currentPassword,
        Password $password
    ): self {
        $this->verifyPassword($currentPassword);

        if ($currentPassword->value === $password->value) {
            throw new InvalidPasswordException(
                $this,
                $password,
                InvalidPasswordException::TYPE_SAME_AS_OLD
            );
        }

        $this->setPassword($password);
        return $this;
    }

    public function resetPassword(
        RecoveryToken $token,
        Password $password
    ): self {
        // Validate the recovery token
        $this->validateRecoveryToken($token);

        // Set the new password
        $this->setPassword($password);

        // Reset the recovery token
        $this->recoveryToken = new RecoveryToken();

        return $this;
    }

    /**
     * @param Password $password
     * @return bool
     * @throws InvalidPasswordException
     */
    public function verifyPassword(Password $password): bool
    {
        if (
            !$this->passwordHash->value
            || !password_verify(
                $password->value,
                $this->passwordHash->value
            )
        ) {
            throw new InvalidPasswordException(
                $this,
                $password,
                InvalidPasswordException::TYPE_INCORRECT
            );
        }

        return true;
    }

    public function hasPassword(): bool
    {
        return !is_null($this->passwordHash->value);
    }

    public function setPassword(Password $password): void
    {
        $this->passwordHash = new PasswordHash(
            $password->value ?
                password_hash($password->value, PASSWORD_DEFAULT)
                : null
        );
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }

    public function createWorkspace(Name $name): WorkspaceEntity
    {
        if (
            $this->workspaceCap->value > 0
            && $this->ownedWorkspaces->count() >= $this->workspaceCap->value
        ) {
            throw new OwnedWorkspaceCapException();
        }

        $workspace = new WorkspaceEntity($this, $name);
        $this->ownedWorkspaces->add($workspace);

        // Switch to the new workspace
        $this->currentWorkspace = $workspace;

        return $workspace;
    }

    /** 
     * @return Traversable<WorkspaceEntity>
     * @throws Exception
     */
    public function getWorkspaces(): Traversable
    {
        return $this->workspaces->getIterator();
    }

    /**
     * @return Traversable<WorkspaceEntity>
     * @throws Exception
     */
    public function getOwnedWorkspaces(): Traversable
    {
        return $this->ownedWorkspaces->getIterator();
    }

    #[ORM\PostLoad]
    public function postLoad(): void
    {
        if ($this->ownedWorkspaces->count() == 0) {
            $this->createDefaultWorkspace();
        }

        if (!$this->currentWorkspace) {
            $this->currentWorkspace = $this->ownedWorkspaces->first();
        }

        if (!$this->affiliate) {
            $this->createAffiliate();
        }
    }

    private function createDefaultWorkspace(): void
    {
        $this->createWorkspace(new Name('Personal'));
    }

    private function createAffiliate(): void
    {
        $this->affiliate = new AffiliateEntity($this);
    }

    public function getAffiliate(): AffiliateEntity
    {
        return $this->affiliate;
    }

    public function getReferredBy(): ?UserEntity
    {
        return $this->referredBy;
    }

    public function setReferredBy(UserEntity $referredBy): void
    {
        $this->referredBy = $referredBy;
    }

    public function getCurrentWorkspace(): WorkspaceEntity
    {
        return $this->currentWorkspace;
    }

    public function setCurrentWorkspace(WorkspaceEntity $workspace): void
    {
        $this->currentWorkspace = $workspace;
    }

    public function getWorkspaceById(Id $id): WorkspaceEntity
    {
        /** @var WorkspaceEntity */
        foreach ($this->workspaces as $workspace) {
            if ($workspace->getId()->getValue() == $id->getValue()) {
                return $workspace;
            }
        }

        /** @var WorkspaceEntity */
        foreach ($this->ownedWorkspaces as $workspace) {
            if ($workspace->getId()->getValue() == $id->getValue()) {
                return $workspace;
            }
        }

        throw new WorkspaceNotFoundException($id);
    }
}
