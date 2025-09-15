<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\DocumentReader\Readers;

use Ai\Infrastructure\Exceptions\UnreadableDocumentException;
use Ai\Infrastructure\Services\DocumentReader\DocumentReaderInterface;
use Override;

class PlainTextDocumentReader implements DocumentReaderInterface
{
    #[Override]
    public function supports(string $identifier): bool
    {
        // Accept any identifier, but actual check is in read()
        return true;
    }

    #[Override]
    public function read(string $contents, ?int $max = null): string
    {
        if (!$this->isPlainText($contents)) {
            throw new UnreadableDocumentException('Content is not plain text');
        }
        return is_null($max) ? $contents : mb_substr($contents, 0, $max);
    }

    private function isPlainText(string $content): bool
    {
        return mb_check_encoding($content, 'UTF-8')
            && preg_match('//u', $content)
            && !preg_match('/[^\P{C}\n\r\t]/u', $content);
    }
}
