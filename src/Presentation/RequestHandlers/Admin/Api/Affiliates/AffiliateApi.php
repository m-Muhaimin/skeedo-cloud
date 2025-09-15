<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Affiliates;

use Easy\Router\Attributes\Path;
use Presentation\RequestHandlers\Admin\Api\AdminApi;

#[Path('/affiliates')]
abstract class AffiliateApi extends AdminApi {}
