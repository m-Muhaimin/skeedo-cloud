<?php

namespace Ai\Domain\Entities;

use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\RequestParams;
use Ai\Domain\ValueObjects\State;
use Ai\Domain\ValueObjects\Title;
use Ai\Domain\ValueObjects\Visibility;
use Billing\Domain\ValueObjects\CreditCount;
use Doctrine\ORM\Mapping as ORM;
use File\Domain\Entities\FileEntity;
use Override;
use Traversable;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[ORM\Entity]
class IsolatedVoiceEntity extends AbstractLibraryItemEntity
{
    #[ORM\ManyToOne(targetEntity: FileEntity::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(onDelete: 'CASCADE', name: 'input_file_id')]
    private FileEntity $inputFile;

    #[ORM\ManyToOne(targetEntity: FileEntity::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(onDelete: 'CASCADE', name: 'output_file_id')]
    private FileEntity $outputFile;

    public function __construct(
        WorkspaceEntity $workspace,
        UserEntity $user,
        Model $model,
        FileEntity $inputFile,
        FileEntity $outputFile,
        Title $title,

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

        $this->inputFile = $inputFile;
        $this->outputFile = $outputFile;
        $this->state = State::COMPLETED;
    }

    public function getInputFile(): FileEntity
    {
        return $this->inputFile;
    }

    public function getOutputFile(): FileEntity
    {
        return $this->outputFile;
    }

    #[Override]
    public function getFiles(): Traversable
    {
        yield $this->inputFile;
        yield $this->outputFile;
    }
}
