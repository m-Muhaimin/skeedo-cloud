<?php

declare(strict_types=1);

namespace Ai\Domain\Title;

use Ai\Domain\Services\AiServiceInterface;
use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Model;

interface TitleServiceInterface extends AiServiceInterface
{
    public function generateTitle(
        Content $content,
        Model $model
    ): GenerateTitleResponse;
}
