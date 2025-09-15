<?php

declare(strict_types=1);

namespace Ai\Domain\ValueObjects;

use JsonSerializable;
use Override;

enum Visibility: int implements JsonSerializable
{
    /** Item is only visible to the owner */
    case PRIVATE = 0;

    /** Item is visible to the owner and the workspace members */
    case WORKSPACE = 1;

    /** Item is visible to everyone */
    case PUBLIC = 2;

    #[Override]
    public function jsonSerialize(): int
    {
        return $this->value;
    }
}
