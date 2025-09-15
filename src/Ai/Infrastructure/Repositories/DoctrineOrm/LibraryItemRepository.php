<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Repositories\DoctrineOrm;

use Ai\Domain\Entities\AbstractLibraryItemEntity;
use Ai\Domain\Entities\ClassificationEntity;
use Ai\Domain\Entities\CodeDocumentEntity;
use Ai\Domain\Entities\CompositionEntity;
use Ai\Domain\Entities\ConversationEntity;
use Ai\Domain\Entities\DocumentEntity;
use Ai\Domain\Entities\ImageEntity;
use Ai\Domain\Entities\IsolatedVoiceEntity;
use Ai\Domain\Entities\MemoryEntity;
use Ai\Domain\Entities\SpeechEntity;
use Ai\Domain\Entities\TranscriptionEntity;
use Ai\Domain\Entities\VideoEntity;
use Ai\Domain\Exceptions\LibraryItemNotFoundException;
use Ai\Domain\Repositories\LibraryItemRepositoryInterface;
use Ai\Domain\ValueObjects\ItemType;
use Ai\Domain\ValueObjects\Model;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;
use Ai\Domain\ValueObjects\SortParameter;
use Ai\Domain\ValueObjects\State;
use Ai\Domain\ValueObjects\Visibility;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use Iterator;
use Override;
use RuntimeException;
use Shared\Infrastructure\Repositories\DoctrineOrm\AbstractRepository;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

class LibraryItemRepository extends AbstractRepository implements
    LibraryItemRepositoryInterface
{
    private const ENTITY_CLASS = AbstractLibraryItemEntity::class;
    private const ALIAS = 'library_item';

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
    public function add(
        AbstractLibraryItemEntity $item
    ): LibraryItemRepositoryInterface {
        $this->em->persist($item);
        return $this;
    }

    #[Override]
    public function remove(
        AbstractLibraryItemEntity $item
    ): LibraryItemRepositoryInterface {
        $this->em->remove($item);
        return $this;
    }

    #[Override]
    public function ofId(Id $id): AbstractLibraryItemEntity
    {
        $object = $this->em->find(self::ENTITY_CLASS, $id);

        if ($object instanceof AbstractLibraryItemEntity) {
            return $object;
        }

        throw new LibraryItemNotFoundException($id);
    }

    #[Override]
    public function filterByUser(
        Id|UserEntity $user,
        Visibility|Id|WorkspaceEntity $visibility = Visibility::PRIVATE
    ): static {
        $id = $user instanceof UserEntity
            ? $user->getId()
            : $user;

        return $this->filter(static function (
            QueryBuilder $qb
        ) use ($id, $visibility) {
            if (
                $visibility instanceof WorkspaceEntity
                || $visibility instanceof Id
            ) {
                $wsid = $visibility instanceof WorkspaceEntity
                    ? $visibility->getId()
                    : $visibility;

                $qb->andWhere(
                    $qb->expr()
                        ->orX(
                            self::ALIAS . '.user = :user',
                            $qb->expr()->andX(
                                self::ALIAS . '.visibility = :visibility',
                                self::ALIAS . '.workspace = :workspace'
                            )
                        )
                )
                    ->setParameter(
                        ':user',
                        $id->getValue()->getBytes(),
                        Types::STRING
                    )
                    ->setParameter(
                        ':visibility',
                        Visibility::WORKSPACE->value,
                        Types::STRING
                    )
                    ->setParameter(
                        ':workspace',
                        $wsid->getValue()->getBytes(),
                        Types::STRING
                    );

                return;
            }

            $qb->andWhere(self::ALIAS . '.user = :user')
                ->setParameter(
                    ':user',
                    $id->getValue()->getBytes(),
                    Types::STRING
                );
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
    public function filterByState(State ...$states): static
    {
        return $this->filter(static function (QueryBuilder $qb) use ($states) {
            $values = array_map(fn(State $state) => $state->value, $states);

            $qb->andWhere(self::ALIAS . '.state IN (:states)')
                ->setParameter(':states', $values, Types::SIMPLE_ARRAY);
        });
    }

    #[Override]
    public function filterByType(ItemType $type): static
    {
        return $this->filter(static function (QueryBuilder $qb) use ($type) {
            match ($type) {
                ItemType::DOCUMENT => $qb->resetDQLPart('from')
                    ->from(DocumentEntity::class, self::ALIAS),

                ItemType::CODE_DOCUMENT => $qb->resetDQLPart('from')
                    ->from(CodeDocumentEntity::class, self::ALIAS),

                ItemType::IMAGE => $qb->resetDQLPart('from')
                    ->from(ImageEntity::class, self::ALIAS),

                ItemType::VIDEO => $qb->resetDQLPart('from')
                    ->from(VideoEntity::class, self::ALIAS),

                ItemType::TRANSCRIPTION => $qb->resetDQLPart('from')
                    ->from(TranscriptionEntity::class, self::ALIAS),

                ItemType::SPEECH => $qb->resetDQLPart('from')
                    ->from(SpeechEntity::class, self::ALIAS),

                ItemType::CONVERSATION => $qb->resetDQLPart('from')
                    ->from(ConversationEntity::class, self::ALIAS),

                ItemType::ISOLATED_VOICE => $qb->resetDQLPart('from')
                    ->from(IsolatedVoiceEntity::class, self::ALIAS),

                ItemType::CLASSIFICATION => $qb->resetDQLPart('from')
                    ->from(ClassificationEntity::class, self::ALIAS),

                ItemType::COMPOSITION => $qb->resetDQLPart('from')
                    ->from(CompositionEntity::class, self::ALIAS),

                ItemType::MEMORY => $qb->resetDQLPart('from')
                    ->from(MemoryEntity::class, self::ALIAS),

                default => null,
            };

            $qb->andWhere(self::ALIAS . ' INSTANCE OF :type')
                ->setParameter(':type', $type->value, Types::STRING);
        });
    }

    #[Override]
    public function filterByModel(Model $model): static
    {
        return $this->filter(static function (QueryBuilder $qb) use ($model) {
            $qb->andWhere(self::ALIAS . '.model.value = :model')
                ->setParameter(
                    ':model',
                    $model->value,
                    Types::STRING
                );
        });
    }

    #[Override]
    public function createdAfter(DateTimeInterface $date): static
    {
        return $this->filter(static function (QueryBuilder $qb) use ($date) {
            $qb->andWhere(self::ALIAS . '.createdAt >= :date')
                ->setParameter(':date', $date, Types::DATETIME_MUTABLE);
        });
    }

    #[Override]
    public function createdBefore(DateTimeInterface $date): static
    {
        return $this->filter(static function (QueryBuilder $qb) use ($date) {
            $qb->andWhere(self::ALIAS . '.createdAt <= :date')
                ->setParameter(':date', $date, Types::DATETIME_MUTABLE);
        });
    }

    #[Override]
    public function search(string $terms): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($terms) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        self::ALIAS . '.title.value LIKE :search'
                    )
                )->setParameter('search', '%' . $terms . '%');
            }
        );
    }

    #[Override]
    public function sort(
        SortDirection $dir,
        ?SortParameter $sortParameter = null
    ): static {
        $cloned = $this->doSort($dir, $this->getSortKey($sortParameter));
        $cloned->sortParameter = $sortParameter;

        return $cloned;
    }

    #[Override]
    public function startingAfter(
        AbstractLibraryItemEntity $cursor
    ): Iterator {
        return $this->doStartingAfter(
            $cursor->getId(),
            $this->getCompareValue($cursor)
        );
    }

    #[Override]
    public function endingBefore(
        AbstractLibraryItemEntity $cursor
    ): Iterator {
        return $this->doEndingBefore(
            $cursor->getId(),
            $this->getCompareValue($cursor)
        );
    }

    private function getSortKey(?SortParameter $param): ?string
    {
        return match ($param) {
            SortParameter::ID => 'id.value',
            SortParameter::CREATED_AT => 'createdAt',
            SortParameter::UPDATED_AT => 'updatedAt',
            SortParameter::TITLE => 'title.value',
            default => null,
        };
    }

    /**
     * @template T of AbstractLibraryItemEntity
     * 
     * @param T $cursor
     * @return null|string|DateTimeInterface
     */
    private function getCompareValue(
        AbstractLibraryItemEntity $cursor
    ): null|string|DateTimeInterface {
        return match ($this->sortParameter) {
            SortParameter::ID => $cursor->getId()->getValue()->getBytes(),
            SortParameter::CREATED_AT => $cursor->getCreatedAt(),
            SortParameter::UPDATED_AT => $cursor->getUpdatedAt(),
            SortParameter::TITLE => method_exists($cursor, 'getTitle') ? $cursor->getTitle()->value : null,
            default => null
        };
    }
}
