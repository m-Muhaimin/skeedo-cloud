<?php

namespace Ai\Domain\Entities;

use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\RequestParams;
use Ai\Domain\ValueObjects\Visibility;
use Billing\Domain\ValueObjects\CreditCount;
use Doctrine\ORM\Mapping as ORM;
use File\Domain\Entities\ImageFileEntity;
use Override;
use Traversable;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[ORM\Entity]
class ImageEntity extends AbstractLibraryItemEntity
{
    #[ORM\ManyToOne(targetEntity: ImageFileEntity::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(onDelete: 'CASCADE', name: 'output_file_id')]
    private ?ImageFileEntity $outputFile = null;

    public function __construct(
        WorkspaceEntity $workspace,
        UserEntity $user,

        Model $model,
        ?RequestParams $request = null,
        ?CreditCount $cost = null,
        ?Visibility $visibility = null,
    ) {
        parent::__construct(
            $workspace,
            $user,
            $model,
            null,
            $request,
            $cost,
            $visibility
        );
    }

    public function setOutputFile(ImageFileEntity $outputFile): void
    {
        $this->outputFile = $outputFile;
    }

    public function getOutputFile(): ?ImageFileEntity
    {
        return $this->outputFile;
    }

    #[Override]
    public function getFiles(): Traversable
    {
        yield $this->outputFile;
    }
}
