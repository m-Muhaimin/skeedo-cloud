<?php

declare(strict_types=1);

namespace Voice\Domain;

use Ai\Domain\ValueObjects\Model;
use Shared\Domain\Repositories\RepositoryInterface;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;
use Traversable;
use User\Domain\Entities\UserEntity;
use Voice\Domain\Entities\VoiceEntity;
use Voice\Domain\Exceptions\VoiceNotFoundException;
use Voice\Domain\ValueObjects\Accent;
use Voice\Domain\ValueObjects\Age;
use Voice\Domain\ValueObjects\Gender;
use Voice\Domain\ValueObjects\LanguageCode;
use Voice\Domain\ValueObjects\Provider;
use Voice\Domain\ValueObjects\SortParameter;
use Voice\Domain\ValueObjects\Status;
use Voice\Domain\ValueObjects\Tone;
use Voice\Domain\ValueObjects\UseCase;
use Workspace\Domain\Entities\WorkspaceEntity;

interface VoiceRepositoyInterface extends RepositoryInterface
{
    /**
     * Adds a new entity to the repository.
     * 
     * @param VoiceEntity $voice The entity to add.
     * @return static Returns the repository instance.
     */
    public function add(VoiceEntity $voice): static;

    /**
     * Removes an entity from the repository.
     * 
     * @param VoiceEntity $voice The entity to remove.
     * @return static Returns the repository instance.
     */
    public function remove(VoiceEntity $voice): static;

    /**
     * Retrieves an entity by its ID.
     * 
     * @param Id $id The ID of the entity.
     * @return VoiceEntity Returns the entity.
     * @throws VoiceNotFoundException If the entity is not found.
     */
    public function ofId(Id $id): VoiceEntity;

    /**
     * Filters entities owned by user.
     * 
     * @param Id|UserEntity $user
     * @return static
     */
    public function filterByUser(Id|UserEntity $user): static;

    /**
     * Filters entities owned by workspace.
     * 
     * @param Id|WorkspaceEntity $workspace
     * @return static
     */
    public function filterByWorkspace(Id|WorkspaceEntity $workspace): static;

    /**
     * Filters entities accssible to user within workspace.
     * 
     * @param Id|UserEntity $user
     * @param Id|WorkspaceEntity $workspace
     * @return static
     */
    public function filterByAccess(
        Id|UserEntity $user,
        Id|WorkspaceEntity $workspace
    ): static;

    /**
     * Filters entities by their status.
     * 
     * @param Status $status The status to filter by.
     * @return static Returns the repository instance.
     */
    public function filterByStatus(Status $status): static;

    /**
     * Filters entities by their provider.
     * 
     * @param Provider $provider
     * @return static
     */
    public function filterByProvider(Provider $provider): static;

    /**
     * Filters entities by their tone.
     * 
     * @param Tone $tone The tone to filter by.
     * @return static Returns the repository instance.
     */
    public function filterByTone(Tone $tone): static;

    /**
     * Filters entities by their use case.
     * 
     * @param UseCase $useCase The use case to filter by.
     * @return static Returns the repository instance.
     */
    public function filterByUseCase(UseCase $useCase): static;

    /**
     * Filters entities by gender.
     * 
     * @param Gender $gender The gender to filter by.
     * @return static Returns the repository instance.
     */
    public function filterByGender(Gender $gender): static;

    /**
     * Filters entities by accent.
     * 
     * @param Accent $accent
     * @return static
     */
    public function filterByAccent(Accent $accent): static;

    /**
     * Filters entities by age.
     * 
     * @param Age $age The age to filter by.
     * @return static Returns the repository instance.
     */
    public function filterByAge(Age $age): static;

    /**
     * Sets the maximum number of results to return.
     * 
     * @param int $max The maximum number of results.
     * @return static Returns the repository instance.
     */
    public function filterByLanguage(string|LanguageCode $language): static;

    /**
     * Filters entities by model.
     * 
     * @param Model ...$models The models to filter by.
     * @return static Returns the repository instance.
     */
    public function filterByModel(Model ...$models): static;

    /**
     * Searches for entities in the repository.
     * 
     * @param string $query The search query.
     * @return static Returns the repository instance.
     */
    public function search(string $query): static;

    /**
     * Sorts the entities in the repository.
     * 
     * @param SortDirection $dir The sort direction.
     * @param null|SortParameter $param The sort parameter (optional).
     * @return static Returns the repository instance.
     */
    public function sort(
        SortDirection $dir,
        ?SortParameter $param = null
    ): static;

    /**
     * Retrieves entities starting after a given cursor.
     *
     * @param VoiceEntity $cursor The cursor entity.
     * @return Traversable<int,VoiceEntity> Returns a traversable 
     * collection of entities.
     */
    public function startingAfter(VoiceEntity $cursor): Traversable;

    /**
     * Retrieves entities ending before a given cursor.
     *
     * @param VoiceEntity $cursor The cursor entity.
     * @return Traversable<int,VoiceEntity> Returns a traversable 
     * collection of entities.
     */
    public function endingBefore(VoiceEntity $cursor): Traversable;
}
