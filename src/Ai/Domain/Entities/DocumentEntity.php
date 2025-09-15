<?php

declare(strict_types=1);

namespace Ai\Domain\Entities;

use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\RequestParams;
use Ai\Domain\ValueObjects\State;
use Ai\Domain\ValueObjects\Title;
use Ai\Domain\ValueObjects\Visibility;
use Billing\Domain\ValueObjects\CreditCount;
use Doctrine\ORM\Mapping as ORM;
use Preset\Domain\Entities\PresetEntity;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[ORM\Entity]
class DocumentEntity extends AbstractLibraryItemEntity
{
    #[ORM\Embedded(class: Content::class, columnPrefix: false)]
    private Content $content;

    #[ORM\ManyToOne(targetEntity: PresetEntity::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?PresetEntity $preset = null;

    public function __construct(
        WorkspaceEntity $workspace,
        UserEntity $user,
        Model $model,

        Title $title,
        ?PresetEntity $preset = null,

        ?RequestParams $requestParams = null,
        ?CreditCount $cost = null,
        ?Visibility $visibility = null,
    ) {
        parent::__construct(
            $workspace,
            $user,
            $model,
            $title,
            $requestParams,
            $cost,
            $visibility
        );

        $this->content = new Content();
        $this->preset = $preset;
        $this->state = State::COMPLETED;
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    public function setContent(Content $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getPreset(): ?PresetEntity
    {
        return $this->preset;
    }
}
