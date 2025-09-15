<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Atributes;

use Attribute;

/**
 * The built-in aspect attribute.
 * 
 * Use this attribute to mark a class, method, or property as a built-in aspect.
 * Useful to distinguish between custom pugged in and built-in aspects.
 * 
 * @internal Can not be used by plugins.
 */
#[Attribute]
class BuiltInAspect
{
}
