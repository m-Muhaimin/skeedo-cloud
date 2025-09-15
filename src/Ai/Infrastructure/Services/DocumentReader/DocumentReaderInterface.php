<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\DocumentReader;

interface DocumentReaderInterface
{
    /**
     * Check if this reader supports the given identifier
     * (content type or extension)
     *
     * @param string $identifier Content type (e.g., 'application/pdf')
     * or file extension (e.g., 'pdf')
     */
    public function supports(string $identifier): bool;

    public function read(string $contents, ?int $max = null): string;
}
