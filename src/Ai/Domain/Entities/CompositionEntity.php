<?php

namespace Ai\Domain\Entities;

use Ai\Domain\ValueObjects\CompositionDetails;
use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\RequestParams;
use Ai\Domain\ValueObjects\State;
use Ai\Domain\ValueObjects\Title;
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
class CompositionEntity extends AbstractLibraryItemEntity
{
    #[ORM\Embedded(class: Content::class, columnPrefix: false)]
    private Content $details;

    #[ORM\ManyToOne(targetEntity: FileEntity::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(onDelete: 'CASCADE', name: 'output_file_id')]
    private FileEntity $outputFile;

    #[ORM\ManyToOne(targetEntity: FileEntity::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(onDelete: 'CASCADE', name: 'cover_image_file_id')]
    private FileEntity $coverImage;

    public function __construct(
        WorkspaceEntity $workspace,
        UserEntity $user,
        Model $model,
        Title $title,
        CompositionDetails $details,
        FileEntity $outputFile,
        ?ImageFileEntity $coverImage = null,

        ?RequestParams $request = null,
        ?CreditCount $cost = null,
        ?Visibility $visibility = null,
    ) {
        parent::__construct(
            $workspace,
            $user,
            $model,
            $title,
            $request,
            $cost,
            $visibility
        );

        $this->details = new Content(json_encode($details));
        $this->outputFile = $outputFile;
        $this->coverImage = $coverImage;
        $this->state = State::COMPLETED;
    }

    public function getOutputFile(): FileEntity
    {
        return $this->outputFile;
    }

    public function getCoverImage(): ?ImageFileEntity
    {
        return $this->coverImage;
    }

    public function getLyrics(): ?string
    {
        /** @var CompositionDetails $details */
        $details = json_decode($this->details->value);
        return $details->lyrics;
    }

    public function getTags(): ?string
    {
        /** @var CompositionDetails $details */
        $details = json_decode($this->details->value);
        return $details->tags;
    }

    #[Override]
    public function getFiles(): Traversable
    {
        yield $this->outputFile;
        yield $this->coverImage;
    }
}
