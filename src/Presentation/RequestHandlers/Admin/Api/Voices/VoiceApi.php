<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Voices;

use Easy\Router\Attributes\Path;
use Presentation\RequestHandlers\Admin\Api\AdminApi;

#[Path('/voices')]
abstract class VoiceApi extends AdminApi
{
}
