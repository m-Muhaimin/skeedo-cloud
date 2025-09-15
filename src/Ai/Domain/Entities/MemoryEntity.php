<?php

declare(strict_types=1);

namespace Ai\Domain\Entities;

use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\State;
use Ai\Domain\ValueObjects\Visibility;
use Billing\Domain\ValueObjects\CreditCount;
use Doctrine\ORM\Mapping as ORM;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[ORM\Entity]
class MemoryEntity extends AbstractLibraryItemEntity
{
    #[ORM\Embedded(class: Content::class, columnPrefix: false)]
    private Content $content;

    public function __construct(
        WorkspaceEntity $workspace,
        UserEntity $user,
        Content $content,
        ?CreditCount $cost = null,
        ?Visibility $visibility = null,
    ) {
        parent::__construct(
            $workspace,
            $user,
            new Model(),
        );

        $this->content = $content;
        $this->cost = $cost;
        $this->visibility = $visibility;

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
}
