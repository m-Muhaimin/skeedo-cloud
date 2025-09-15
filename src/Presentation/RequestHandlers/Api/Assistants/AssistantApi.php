<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Assistants;

use Easy\Router\Attributes\Path;
use Presentation\RequestHandlers\Api\Api;

#[Path('/assistants')]
abstract class AssistantApi extends Api
{
}
