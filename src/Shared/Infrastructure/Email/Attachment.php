<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Email;

class Attachment
{
    public function __construct(
        public readonly string $content,
        public readonly string $name,
        public readonly ?string $contentType = null,
    ) {}
}
