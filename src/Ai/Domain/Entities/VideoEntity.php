<?php

namespace Ai\Domain\Entities;

use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\RequestParams;
use Ai\Domain\ValueObjects\Visibility;
use Billing\Domain\ValueObjects\CreditCount;
use Doctrine\ORM\Mapping as ORM;
use File\Domain\Entities\FileEntity;
use File\Domain\Entities\ImageFileEntity;
use Override;
use Traversable;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[ORM\Entity]
class VideoEntity extends AbstractLibraryItemEntity
{
    #[ORM\ManyToOne(targetEntity: FileEntity::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(onDelete: 'CASCADE', name: 'output_file_id')]
    private ?FileEntity $outputFile = null;

    #[ORM\ManyToOne(targetEntity: ImageFileEntity::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(onDelete: 'CASCADE', name: 'cover_image_file_id')]
    private ?ImageFileEntity $coverImage = null;

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

    public function setOutputFile(FileEntity $outputFile): void
    {
        $this->outputFile = $outputFile;
    }

    public function getOutputFile(): ?FileEntity
    {
        return $this->outputFile;
    }

    public function setCoverImage(ImageFileEntity $coverImage): void
    {
        $this->coverImage = $coverImage;
    }

    public function getCoverImage(): ?ImageFileEntity
    {
        return $this->coverImage;
    }

    #[Override]
    public function getFiles(): Traversable
    {
        yield from [$this->outputFile, $this->coverImage];
    }
}
