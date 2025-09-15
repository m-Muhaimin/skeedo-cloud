<?php

declare(strict_types=1);

namespace Stat\Domain\Entities;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Shared\Domain\ValueObjects\Id;
use Stat\Domain\ValueObjects\Metric;

#[ORM\Entity]
#[ORM\Table(name: 'stat')]
#[ORM\HasLifecycleCallbacks]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: "discr", type: Types::STRING)]
#[ORM\DiscriminatorMap([
    'usage' => UsageStatEntity::class,
    'signup' => SignupStatEntity::class,
    'subscription' => SubscriptionStatEntity::class,
    'order' => OrderStatEntity::class,
])]
#[ORM\Index(columns: ['date'])]
#[ORM\Index(columns: ['country_code'])]
abstract class AbstractStatEntity
{
    /** A unique numeric identifier of the entity. */
    #[ORM\Embedded(class: Id::class, columnPrefix: false)]
    private Id $id;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, name: 'date')]
    private DateTimeInterface $date;

    #[ORM\Embedded(class: Metric::class, columnPrefix: false)]
    private Metric $metric;

    public function __construct()
    {
        $this->id = new Id();

        $this->date = new DateTimeImmutable();
        $this->metric = new Metric(0);
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getMetric(): Metric
    {
        return $this->metric;
    }

    public function incrementMetric(Metric $metric = new Metric(1)): void
    {
        $this->metric = new Metric($this->metric->value + $metric->value);
    }

    public function getDate(): DateTimeInterface
    {
        return $this->date;
    }
}
