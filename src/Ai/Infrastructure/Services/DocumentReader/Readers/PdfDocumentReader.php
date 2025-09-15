<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\DocumentReader\Readers;

use Ai\Infrastructure\Exceptions\UnreadableDocumentException;
use Ai\Infrastructure\Services\DocumentReader\DocumentReaderInterface;
use Override;
use Smalot\PdfParser\Parser;
use Throwable;

class PdfDocumentReader implements DocumentReaderInterface
{
    private const SUPPORTED_TYPES = [
        'application/pdf',
        'pdf'
    ];

    #[Override]
    public function supports(string $identifier): bool
    {
        return in_array(strtolower(trim($identifier)), self::SUPPORTED_TYPES, true)
            || str_starts_with($identifier, '%PDF-');
    }

    #[Override]
    public function read(string $contents, ?int $max = null): string
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseContent($contents);
            $text = $pdf->getText();
        } catch (Throwable $th) {
            throw new UnreadableDocumentException(
                message: $th->getMessage(),
                previous: $th
            );
        }

        return is_null($max) ? $text : mb_substr($text, 0, $max);
    }
}
