<?php

declare(strict_types=1);

namespace User\Infrastructure\Repositories\DoctrineOrm;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use DomainException;
use InvalidArgumentException;
use Iterator;
use Override;
use RuntimeException;
use Shared\Domain\ValueObjects\CountryCode;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;
use Shared\Infrastructure\Repositories\DoctrineOrm\AbstractRepository;
use User\Domain\Entities\UserEntity;
use User\Domain\Exceptions\EmailTakenException;
use User\Domain\Exceptions\UserNotFoundException;
use User\Domain\Repositories\UserRepositoryInterface;
use User\Domain\ValueObjects\ApiKey;
use User\Domain\ValueObjects\Email;
use User\Domain\ValueObjects\IsEmailVerified;
use User\Domain\ValueObjects\Role;
use User\Domain\ValueObjects\SortParameter;
use User\Domain\ValueObjects\Status;

class UserRepository extends AbstractRepository implements
    UserRepositoryInterface
{
    private const ENTITY_CLASS = UserEntity::class;
    private const ALIAS = 'user';
    private ?SortParameter $sortParameter = null;

    /**
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, self::ENTITY_CLASS, self::ALIAS);
    }

    #[Override]
    public function add(UserEntity $user): static
    {
        try {
            $this->ofEmail($user->getEmail());
            throw new EmailTakenException($user->getEmail());
        } catch (UserNotFoundException $th) {
            // Do nothing, the email is not taken
        }

        $this->em->persist($user);
        return $this;
    }

    #[Override]
    public function remove(UserEntity $user): static
    {
        $this->em->remove($user);
        return $this;
    }

    #[Override]
    public function ofId(Id $id): UserEntity
    {
        $object = $this->em->find(self::ENTITY_CLASS, $id);

        if ($object instanceof UserEntity) {
            return $object;
        }

        throw new UserNotFoundException($id);
    }

    #[Override]
    public function ofEmail(Email $email): UserEntity
    {
        try {
            $object = $this->query()
                ->where(self::ALIAS . '.email.value = :email')
                ->setParameter(':email', $email->value)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            throw new UserNotFoundException($email);
        } catch (NonUniqueResultException $e) {
            throw new DomainException('More than one result found');
        }

        return $object;
    }

    public function ofApiKey(ApiKey $key): UserEntity
    {
        try {
            $object = $this->query()
                ->where(self::ALIAS . '.apiKey.hash = :apiKey')
                ->setParameter(':apiKey', $key->hash)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            throw new UserNotFoundException($key);
        } catch (NonUniqueResultException $e) {
            throw new DomainException('More than one result found');
        }

        return $object;
    }

    #[Override]
    public function ofUniqueKey(Id|Email|ApiKey $key): UserEntity
    {
        return match (true) {
            $key instanceof Id => $this->ofId($key),
            $key instanceof Email => $this->ofEmail($key),
            $key instanceof ApiKey => $this->ofApiKey($key),
            default => throw new InvalidArgumentException(
                'Invalid key type'
            )
        };
    }

    #[Override]
    public function filterByRole(Role $role): static
    {
        return $this->filter(static function (QueryBuilder $qb) use ($role) {
            $qb->andWhere(self::ALIAS . '.role = :role')
                ->setParameter(':role', $role->value, Types::SMALLINT);
        });
    }

    #[Override]
    public function filterByStatus(Status $status): static
    {
        if ($status === Status::ONLINE) {
            return $this->filter(static function (QueryBuilder $qb) {
                $qb->andWhere(self::ALIAS . '.status = :status')
                    ->andWhere(self::ALIAS . '.lastSeenAt >= :lastSeenAt')
                    ->setParameter(
                        ':status',
                        Status::ACTIVE->value,
                        Types::SMALLINT
                    )
                    ->setParameter(
                        ':lastSeenAt',
                        new DateTime(sprintf('-%d seconds', UserEntity::ONLINE_THRESHOLD)),
                        Types::DATETIME_MUTABLE
                    );
            });
        }

        if ($status === Status::AWAY) {
            return $this->filter(static function (QueryBuilder $qb) {
                $qb->andWhere(
                    $qb->expr()->andX(
                        self::ALIAS . '.status = :status',
                        $qb->expr()->orX(
                            self::ALIAS . '.lastSeenAt IS NULL',
                            self::ALIAS . '.lastSeenAt < :lastSeenAt'
                        )
                    )
                )
                    ->setParameter(
                        ':status',
                        Status::ACTIVE->value,
                        Types::SMALLINT
                    )
                    ->setParameter(
                        ':lastSeenAt',
                        new DateTime(sprintf('-%d seconds', UserEntity::ONLINE_THRESHOLD)),
                        Types::DATETIME_MUTABLE
                    );
            });
        }

        return $this->filter(static function (QueryBuilder $qb) use ($status) {
            $qb->andWhere(self::ALIAS . '.status = :status')
                ->setParameter(':status', $status->value, Types::SMALLINT);
        });
    }

    #[Override]
    public function filterByCountryCode(CountryCode $countryCode): static
    {
        return $this->filter(static function (QueryBuilder $qb) use ($countryCode) {
            $qb->andWhere(self::ALIAS . '.countryCode = :countryCode')
                ->setParameter(':countryCode', $countryCode->value, Types::STRING);
        });
    }

    #[Override]
    public function filterByEmailVerificationStatus(IsEmailVerified $isEmailVerified): static
    {
        return $this->filter(static function (QueryBuilder $qb) use ($isEmailVerified) {
            if ($isEmailVerified->value === true) {
                $qb->andWhere(self::ALIAS . '.isEmailVerified.value = :isEmailVerified')
                    ->setParameter(':isEmailVerified', true, Types::BOOLEAN);
            } else {
                $qb->andWhere(
                    $qb->expr()->orX(
                        self::ALIAS . '.isEmailVerified.value = :isEmailVerified',
                        self::ALIAS . '.isEmailVerified.value IS NULL'
                    )
                )->setParameter(':isEmailVerified', false, Types::BOOLEAN);
            }
        });
    }

    #[Override]
    public function filterByRef(Id|UserEntity $ref): static
    {
        $id = $ref instanceof UserEntity
            ? $ref->getId()
            : $ref;

        return $this->filter(static function (QueryBuilder $qb) use ($id) {
            $qb->andWhere(self::ALIAS . '.referredBy = :ref')
                ->setParameter(':ref', $id->getValue()->getBytes(), Types::BINARY);
        });
    }

    #[Override]
    public function search(string $terms): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($terms) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        self::ALIAS . '.firstName.value LIKE :search',
                        self::ALIAS . '.lastName.value LIKE :search',
                        self::ALIAS . '.email.value LIKE :search'
                    )
                )->setParameter('search', $terms . '%');
            }
        );
    }

    #[Override]
    public function createdAfter(
        DateTimeInterface $date
    ): static {
        return $this->filter(static function (QueryBuilder $qb) use ($date) {
            $qb->andWhere('user.createdAt >= :after')
                ->setParameter(':after', $date, Types::DATETIME_MUTABLE);
        });
    }

    #[Override]
    public function createdBefore(
        DateTimeInterface $date
    ): static {
        return $this->filter(static function (QueryBuilder $qb) use ($date) {
            $qb->andWhere('user.createdAt <= :before')
                ->setParameter(':before', $date, Types::DATETIME_MUTABLE);
        });
    }

    #[Override]
    public function sort(
        SortDirection $dir,
        ?SortParameter $sortParameter = null
    ): static {
        $asp = [SortParameter::CLICKS, SortParameter::REFERRALS, SortParameter::WITHDRAWN];

        $cloned = $this;

        // First join the affiliate table if needed
        if (in_array($sortParameter, $asp)) {
            $cloned = $this->filter(static function (QueryBuilder $qb) {
                $qb->leftJoin(self::ALIAS . '.affiliate', 'affiliate');
            });
        }

        // Then do the sorting
        $cloned = $cloned->doSort($dir, $this->getSortKey($sortParameter));
        $cloned->sortParameter = $sortParameter;

        return $cloned;
    }

    #[Override]
    public function startingAfter(UserEntity $cursor): Iterator
    {
        return $this->doStartingAfter(
            $cursor->getId(),
            $this->getCompareValue($cursor)
        );
    }

    #[Override]
    public function endingBefore(UserEntity $cursor): Iterator
    {
        return $this->doEndingBefore(
            $cursor->getId(),
            $this->getCompareValue($cursor)
        );
    }

    /**
     * Returns the sort key based on the given SortParameter.
     *
     * @param SortParameter|null $param The SortParameter to determine the 
     * sort key.
     * @return string|null The sort key corresponding to the given SortParameter, 
     * or null if the SortParameter is not recognized.
     */
    private function getSortKey(
        ?SortParameter $param
    ): ?string {
        return match ($param) {
            SortParameter::ID => 'id.value',
            SortParameter::FIRST_NAME => 'firstName.value',
            SortParameter::LAST_NAME => 'lastName.value',
            SortParameter::CREATED_AT => 'createdAt',
            SortParameter::UPDATED_AT => 'updatedAt',

            SortParameter::CLICKS => 'affiliate/clickCount.value',
            SortParameter::REFERRALS => 'affiliate/referralCount.value',
            SortParameter::WITHDRAWN => 'affiliate/withdrawn.value',
            default => null
        };
    }

    /**
     * Get the compare value based on the sort parameter.
     *
     * @param UserEntity $cursor The user entity to compare.
     * @return null|string|DateTimeInterface The compare value based on the 
     * sort parameter.
     */
    private function getCompareValue(
        UserEntity $cursor
    ): null|int|string|DateTimeInterface {
        return match ($this->sortParameter) {
            SortParameter::ID => $cursor->getId()->getValue()->getBytes(),
            SortParameter::FIRST_NAME => $cursor->getFirstName()->value,
            SortParameter::LAST_NAME => $cursor->getLastName()->value,
            SortParameter::CREATED_AT => $cursor->getCreatedAt(),
            SortParameter::UPDATED_AT => $cursor->getUpdatedAt(),

            SortParameter::CLICKS => $cursor->getAffiliate()->getClickCount()->value,
            SortParameter::REFERRALS => $cursor->getAffiliate()->getReferralCount()->value,
            SortParameter::WITHDRAWN => $cursor->getAffiliate()->getWithdrawn()->value,
            default => null
        };
    }
}
