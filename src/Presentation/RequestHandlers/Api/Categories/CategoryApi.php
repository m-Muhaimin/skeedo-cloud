<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Categories;

use Easy\Router\Attributes\Path;
use Presentation\RequestHandlers\Api\Api;

#[Path('/categories')]
abstract class CategoryApi extends Api
{
}
