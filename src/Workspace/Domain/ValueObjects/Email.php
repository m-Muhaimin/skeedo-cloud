<?php

declare(strict_types=1);

namespace Workspace\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Shared\Domain\ValueObjects\Email as BaseEmail;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Email extends BaseEmail
{
    #[ORM\Column(type: Types::STRING, name: "email")]
    public readonly string $value; // @phpstan-ignore-line
}
