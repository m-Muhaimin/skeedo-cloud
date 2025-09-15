<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Navigation;

enum IconType: string
{
    case SVG = 'svg'; // SVG icon (source)
    case INCLUDE = 'include'; // Icon from an include file (twig file)
    case SRC = 'src'; // Icon from a source path
    case TI = 'ti'; // Tabler icon (name)
}
