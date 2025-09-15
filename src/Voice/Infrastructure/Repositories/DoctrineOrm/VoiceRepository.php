<?php

declare(strict_types=1);

namespace Voice\Infrastructure\Repositories\DoctrineOrm;

use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\Visibility;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Override;
use Shared\Infrastructure\Repositories\DoctrineOrm\AbstractRepository;
use Traversable;
use User\Domain\Entities\UserEntity;
use Voice\Domain\Entities\VoiceEntity;
use Voice\Domain\Exceptions\VoiceNotFoundException;
use Voice\Domain\ValueObjects\Provider;
use Voice\Domain\ValueObjects\Gender;
use Voice\Domain\ValueObjects\Accent;
use Voice\Domain\ValueObjects\Age;
use Voice\Domain\ValueObjects\LanguageCode;
use Voice\Domain\ValueObjects\SortParameter;
use Voice\Domain\ValueObjects\Status;
use Voice\Domain\ValueObjects\Tone;
use Voice\Domain\ValueObjects\UseCase;
use Voice\Domain\VoiceRepositoyInterface;
use Workspace\Domain\Entities\WorkspaceEntity;

class VoiceRepository extends AbstractRepository implements
    VoiceRepositoyInterface
{
    private const ENTITY_CLASS = VoiceEntity::class;
    private const ALIAS = 'voice';
    private ?SortParameter $sortParameter = null;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, self::ENTITY_CLASS, self::ALIAS);
    }

    #[Override]
    public function add(VoiceEntity $voice): static
    {
        $this->em->persist($voice);
        return $this;
    }

    #[Override]
    public function remove(VoiceEntity $voice): static
    {
        $this->em->remove($voice);
        return $this;
    }

    #[Override]
    public function ofId(Id $id): VoiceEntity
    {
        $object = $this->em->find(self::ENTITY_CLASS, $id);

        if ($object instanceof VoiceEntity) {
            return $object;
        }

        throw new VoiceNotFoundException($id);
    }

    #[Override]
    public function filterByUser(Id|UserEntity $user): static
    {
        $id = $user instanceof UserEntity
            ? $user->getId()
            : $user;

        return $this->filter(static function (QueryBuilder $qb) use ($id) {
            $qb->andWhere(self::ALIAS . '.user = :user')
                ->setParameter(':user', $id->getValue()->getBytes(), Types::STRING);
        });
    }

    #[Override]
    public function filterByWorkspace(Id|WorkspaceEntity $workspace): static
    {
        $id = $workspace instanceof WorkspaceEntity
            ? $workspace->getId()
            : $workspace;

        return $this->filter(static function (QueryBuilder $qb) use ($id) {
            $qb->andWhere(self::ALIAS . '.workspace = :workspace')
                ->setParameter(
                    ':workspace',
                    $id->getValue()->getBytes(),
                    Types::STRING
                );
        });
    }

    #[Override]
    public function filterByAccess(
        Id|UserEntity $user,
        Id|WorkspaceEntity $workspace
    ): static {
        return $this->filter(function (QueryBuilder $qb) use ($user, $workspace) {
            $conditions = $qb->expr()->orX();

            $userId = $user instanceof UserEntity ? $user->getId() : $user;
            $wsId = $workspace instanceof WorkspaceEntity ? $workspace->getId() : $workspace;

            // User owned
            $conditions->add($qb->expr()->andX(
                self::ALIAS . '.user = :user',
                self::ALIAS . '.workspace = :workspace',
                self::ALIAS . '.visibility = :private_visibility'
            ));
            $qb->setParameter(':private_visibility', Visibility::PRIVATE->value, Types::STRING);

            // Workspace shared access
            $conditions->add($qb->expr()->andX(
                self::ALIAS . '.workspace = :workspace',
                self::ALIAS . '.visibility = :workspace_visibility'
            ));
            $qb->setParameter(':workspace_visibility', Visibility::WORKSPACE->value, Types::STRING);

            // Public access
            $conditions->add(self::ALIAS . '.visibility = :public_visibility');
            $qb->setParameter(':public_visibility', Visibility::PUBLIC->value, Types::STRING);

            $qb->setParameter(':user', $userId->getValue()->getBytes(), Types::STRING)
                ->setParameter(':workspace', $wsId->getValue()->getBytes(), Types::STRING);

            $qb->andWhere($conditions);
        });
    }

    #[Override]
    public function filterByStatus(Status $status): static
    {
        return $this->filter(static function (
            QueryBuilder $qb
        ) use ($status) {
            $qb->andWhere(self::ALIAS . '.status = :status')
                ->setParameter(':status', $status->value, Types::INTEGER);
        });
    }

    #[Override]
    public function filterByProvider(Provider $provider): static
    {
        return $this->filter(static function (
            QueryBuilder $qb
        ) use ($provider) {
            $qb->andWhere(self::ALIAS . '.provider.value = :provider')
                ->setParameter(':provider', $provider->value, Types::STRING);
        });
    }

    #[Override]
    public function filterByTone(Tone $tone): static
    {
        return $this->filter(static function (
            QueryBuilder $qb
        ) use ($tone) {
            $qb->andWhere(self::ALIAS . '.tone LIKE :tone')
                ->setParameter(
                    ':tone',
                    '%' . $tone->value . '%',
                    Types::STRING
                );
        });
    }

    #[Override]
    public function filterByUseCase(UseCase $useCase): static
    {
        return $this->filter(static function (
            QueryBuilder $qb
        ) use ($useCase) {
            $qb->andWhere(self::ALIAS . '.useCase LIKE :useCase')
                ->setParameter(
                    ':useCase',
                    '%' . $useCase->value . '%',
                    Types::STRING
                );
        });
    }

    #[Override]
    public function filterByGender(Gender $gender): static
    {
        return $this->filter(static function (
            QueryBuilder $qb
        ) use ($gender) {
            $qb->andWhere(self::ALIAS . '.gender = :gender')
                ->setParameter(':gender', $gender->value, Types::STRING);
        });
    }

    #[Override]
    public function filterByAccent(Accent $accent): static
    {
        return $this->filter(static function (
            QueryBuilder $qb
        ) use ($accent) {
            $qb->andWhere(self::ALIAS . '.accent = :accent')
                ->setParameter(':accent', $accent->value, Types::STRING);
        });
    }

    #[Override]
    public function filterByAge(Age $age): static
    {
        return $this->filter(static function (
            QueryBuilder $qb
        ) use ($age) {
            $qb->andWhere(self::ALIAS . '.age = :age')
                ->setParameter(':age', $age->value, Types::STRING);
        });
    }

    #[Override]
    public function filterByLanguage(string|LanguageCode $language): static
    {
        $locales = [];
        if ($language instanceof LanguageCode) {
            $locales[] = $language->value;
        } else {
            $locales = explode(',', $language);
        }

        // Trim and remove empty values
        $locales = array_filter(array_map('trim', $locales));

        if (empty($locales)) {
            return $this;
        }

        return $this->filter(static function (
            QueryBuilder $qb
        ) use ($locales) {
            $conditions = [];

            for ($i = 0; $i < count($locales); $i++) {
                $conditions[] = self::ALIAS . '.supportedLanguages LIKE :language' . $i;
                $qb->setParameter(
                    ':language' . $i,
                    '%' . $locales[$i] . '%',
                    Types::STRING
                );
            }

            $qb->andWhere(
                $qb->expr()->orX(...$conditions)
            );
        });
    }

    #[Override]
    public function filterByModel(Model ...$models): static
    {
        return $this->filter(static function (
            QueryBuilder $qb
        ) use ($models) {
            $conditions = [];

            for ($i = 0; $i < count($models); $i++) {
                $conditions[] = self::ALIAS . '.model.value = :model' . $i;
                $qb->setParameter(
                    ':model' . $i,
                    $models[$i]->value,
                    Types::STRING
                );
            }

            $qb->andWhere(
                $qb->expr()->orX(...$conditions)
            );
        });
    }

    #[Override]
    public function search(string $query): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($query) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        self::ALIAS . '.name.value LIKE :search'
                    )
                )->setParameter('search', '%' . $query . '%');
            }
        );
    }

    #[Override]
    public function sort(
        SortDirection $dir,
        ?SortParameter $param = null
    ): static {
        $cloned = $this->doSort($dir, $this->getSortKey($param));
        $cloned->sortParameter = $param;

        return $cloned;
    }

    #[Override]
    public function startingAfter(VoiceEntity $cursor): Traversable
    {
        return $this->doStartingAfter(
            $cursor->getId(),
            $this->getCompareValue($cursor)
        );
    }

    #[Override]
    public function endingBefore(VoiceEntity $cursor): Traversable
    {
        return $this->doEndingBefore(
            $cursor->getId(),
            $this->getCompareValue($cursor)
        );
    }

    /**
     * Returns the sort key based on the given SortParameter.
     *
     * @param SortParameter $param The sort parameter.
     * @return null|string The sort key or null if the sort parameter is not 
     * recognized.
     */
    private function getSortKey(
        ?SortParameter $param
    ): ?string {
        return match ($param) {
            SortParameter::ID => 'id.value',
            SortParameter::NAME => 'name.value',
            SortParameter::CREATED_AT => 'createdAt',
            SortParameter::UPDATED_AT => 'updatedAt',
            SortParameter::POSITION => 'position.value',
            default => null
        };
    }

    /**
     * Returns the compare value based on the current sort parameter 
     * and the given VoiceEntity.
     *
     * @param VoiceEntity $cursor The category entity to compare.
     * @return null|string|DateTimeInterface The compare value or null if the 
     * sort parameter is not recognized.
     */
    private function getCompareValue(
        VoiceEntity $cursor
    ): null|string|DateTimeInterface {
        return match ($this->sortParameter) {
            SortParameter::ID => $cursor->getId()->getValue()->getBytes(),
            SortParameter::NAME => $cursor->getName()->value,
            SortParameter::CREATED_AT => $cursor->getCreatedAt(),
            SortParameter::UPDATED_AT => $cursor->getUpdatedAt(),
            SortParameter::POSITION => $cursor->getPosition()->value,
            default => null
        };
    }
}
