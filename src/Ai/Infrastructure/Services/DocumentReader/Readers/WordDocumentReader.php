<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\DocumentReader\Readers;

use Ai\Infrastructure\Exceptions\UnreadableDocumentException;
use Ai\Infrastructure\Services\DocumentReader\DocumentReaderInterface;
use Override;
use PhpOffice\PhpWord\Element\AbstractElement;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use Throwable;

class WordDocumentReader implements DocumentReaderInterface
{
    private const SUPPORTED_IDENTIFIERS = [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.oasis.opendocument.text',
        'doc',
        'docx',
        'odt'
    ];

    #[Override]
    public function supports(string $identifier): bool
    {
        $identifier = strtolower(trim($identifier));
        return in_array($identifier, self::SUPPORTED_IDENTIFIERS, true);
    }

    #[Override]
    public function read(string $contents, ?int $max = null): string
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'PHPWord');
        file_put_contents($temporaryFile, $contents);

        try {
            $doc = IOFactory::load($temporaryFile);
            $fullText = '';
            foreach ($doc->getSections() as $section) {
                $fullText .= $this->extractTextFromDocxNode($section);
            }
        } catch (Throwable $th) {
            throw new UnreadableDocumentException(
                message: $th->getMessage(),
                previous: $th
            );
        } finally {
            unlink($temporaryFile);
        }

        return is_null($max) ? $fullText : mb_substr($fullText, 0, $max);
    }

    private function extractTextFromDocxNode(Section|AbstractElement $section): string
    {
        $text = '';
        if (method_exists($section, 'getElements')) {
            foreach ($section->getElements() as $childSection) {
                $text = $this->concatenate($text, $this->extractTextFromDocxNode($childSection));
            }
        } elseif (method_exists($section, 'getText')) {
            $text = $this->concatenate($text, $this->toString($section->getText()));
        }

        return $text;
    }

    private function concatenate(string $text1, string $text2): string
    {
        if ($text1 === '') {
            return $text2;
        }

        if (str_ends_with($text1, ' ')) {
            return $text1 . $text2;
        }

        if (str_starts_with($text2, ' ')) {
            return $text1 . $text2;
        }

        return $text1 . ' ' . $text2;
    }

    /**
     * @param  array<string>|string|null  $text
     */
    private function toString(array|null|string $text): string
    {
        if ($text === null) {
            return '';
        }

        if (is_array($text)) {
            return implode(' ', $text);
        }

        return $text;
    }
}
