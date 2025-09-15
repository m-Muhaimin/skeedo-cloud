<?php

declare(strict_types=1);

namespace Option\Infrastructure\Repositories\DoctrineOrm;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use DomainException;
use InvalidArgumentException;
use Option\Domain\Entities\OptionEntity;
use Option\Domain\Exceptions\KeyTakenException;
use Option\Domain\Exceptions\OptionNotFoundException;
use Option\Domain\Repositories\OptionRepositoryInterface;
use Option\Domain\ValueObjects\Key;
use Override;
use RuntimeException;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\Repositories\DoctrineOrm\AbstractRepository;

class OptionRepository extends AbstractRepository implements
    OptionRepositoryInterface
{
    private const ENTITY_CLASS = OptionEntity::class;
    private const ALIAS = 'option';

    /**
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, self::ENTITY_CLASS, self::ALIAS);
    }

    #[Override]
    public function add(OptionEntity $option): self
    {
        try {
            $this->ofKey($option->getKey());
            throw new KeyTakenException($option->getKey());
        } catch (OptionNotFoundException $th) {
            // Do nothing, the key is not taken
        }

        $this->em->persist($option);
        $this->em->flush();
        return $this;
    }

    #[Override]
    public function remove(OptionEntity $option): self
    {
        $this->em->remove($option);
        return $this;
    }

    #[Override]
    public function ofId(Id $id): OptionEntity
    {
        $object = $this->em->find(self::ENTITY_CLASS, $id);

        if ($object instanceof OptionEntity) {
            return $object;
        }

        throw new OptionNotFoundException($id);
    }

    /**
     * @throws DomainException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    #[Override]
    public function ofKey(Key $key): OptionEntity
    {
        try {
            $object = $this->query()
                ->where(self::ALIAS . '.key.value = :key')
                ->setParameter(':key', $key->value)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            throw new OptionNotFoundException($key);
        } catch (NonUniqueResultException $e) {
            throw new DomainException('More than one result found');
        }

        return $object;
    }
}
