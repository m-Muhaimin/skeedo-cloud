<?php

declare(strict_types=1);

namespace Stat\Domain\Entities;

use Doctrine\ORM\Mapping as ORM;
use Stat\Domain\ValueObjects\Metric;
use Workspace\Domain\Entities\WorkspaceEntity;

#[ORM\Entity]
class UsageStatEntity extends AbstractStatEntity
{
    #[ORM\ManyToOne(targetEntity: WorkspaceEntity::class, inversedBy: 'stats')]
    #[ORM\JoinColumn(onDelete: 'SET NULL', nullable: true)]
    private ?WorkspaceEntity $workspace = null;

    public function __construct(
        WorkspaceEntity $workspace,
        Metric $usage
    ) {
        parent::__construct();

        $this->workspace = $workspace;
        $this->incrementMetric(
            $usage
        );
    }

    public function getWorkspace(): ?WorkspaceEntity
    {
        return $this->workspace;
    }
}
