<?php

declare(strict_types=1);

namespace User\Domain\Repositories;

use DateTimeInterface;
use Iterator;
use Shared\Domain\Repositories\RepositoryInterface;
use Shared\Domain\ValueObjects\CountryCode;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;
use User\Domain\Entities\UserEntity;
use User\Domain\Exceptions\EmailTakenException;
use User\Domain\Exceptions\UserNotFoundException;
use User\Domain\ValueObjects\ApiKey;
use User\Domain\ValueObjects\Email;
use User\Domain\ValueObjects\IsEmailVerified;
use User\Domain\ValueObjects\Role;
use User\Domain\ValueObjects\SortParameter;
use User\Domain\ValueObjects\Status;

interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Add new user entity to the repository
     *
     * @param UserEntity $user
     * @return static
     * @throws EmailTakenException
     */
    public function add(UserEntity $user): static;

    /**
     * Remove the user entity from the repository
     *
     * @param UserEntity $user
     * @return static
     */
    public function remove(UserEntity $user): static;

    /**
     * Find user entity by id
     *
     * @param Id $id
     * @return UserEntity
     * @throws UserNotFoundException
     */
    public function ofId(Id $id): UserEntity;

    /**
     * Find a single user entity by email
     *
     * @param Email $email
     * @return UserEntity
     * @throws UserNotFoundException
     */
    public function ofEmail(Email $email): UserEntity;

    /**
     * Find a single user entity by api key
     *
     * @param ApiKey $key
     * @return UserEntity
     * @throws UserNotFoundException
     */
    public function ofApiKey(ApiKey $key): UserEntity;

    /**
     * Find a single user entity by unique key
     *
     * @param Id|Email|ApiKey $key
     * @return UserEntity
     * @throws UserNotFoundException
     */
    public function ofUniqueKey(Id|Email|ApiKey $key): UserEntity;

    /**
     * @param Role $role
     * @return static
     */
    public function filterByRole(Role $role): static;

    /**
     * @param Status $status
     * @return static
     */
    public function filterByStatus(Status $status): static;

    /**
     * @param CountryCode $countryCode
     * @return static
     */
    public function filterByCountryCode(CountryCode $countryCode): static;

    /**
     * @param IsEmailVerified $isEmailVerified
     * @return static
     */
    public function filterByEmailVerificationStatus(IsEmailVerified $isEmailVerified): static;

    /**
     * @param Id|UserEntity $ref
     * @return static
     */
    public function filterByRef(Id|UserEntity $ref): static;

    /**
     * Filter user entities collection by the created after the date
     *
     * @param DateTimeInterface $date
     * @return static
     */
    public function createdAfter(DateTimeInterface $date): static;

    /**
     * Filter user entities collection by the created before the date
     *
     * @param DateTimeInterface $date
     * @return static
     */
    public function createdBefore(DateTimeInterface $date): static;

    /**
     * @param string $terms
     * @return static
     */
    public function search(string $terms): static;

    /**
     * @param SortDirection $dir 
     * @param null|SortParameter $sortParameter
     * @return static 
     */
    public function sort(
        SortDirection $dir,
        ?SortParameter $sortParameter = null
    ): static;

    /**
     * @param UserEntity $cursor 
     * @return Iterator<UserEntity> 
     */
    public function startingAfter(UserEntity $cursor): Iterator;

    /**
     * @param UserEntity $cursor 
     * @return Iterator<UserEntity> 
     */
    public function endingBefore(UserEntity $cursor): Iterator;
}
