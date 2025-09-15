<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\DocumentReader\Readers;

use Ai\Infrastructure\Services\DocumentReader\DocumentReaderInterface;
use DOMDocument;
use DOMXPath;
use Override;

class HtmlDocumentReader implements DocumentReaderInterface
{
    private const SUPPORTED_IDENTIFIERS = [
        'text/html',
        'html',
        'xhtml',
        'htm'
    ];

    #[Override]
    public function supports(string $identifier): bool
    {
        $identifier = strtolower(trim($identifier));
        return in_array($identifier, self::SUPPORTED_IDENTIFIERS, true)
            || stripos($identifier, '<!doctype html') === 0
            || stripos($identifier, '<html') === 0;
    }

    #[Override]
    public function read(string $contents, ?int $max = null): string
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($contents, LIBXML_NOERROR | LIBXML_NOWARNING);

        $xpath = new DOMXPath($dom);

        // Extract all text nodes
        $textNodes = $xpath->query("//text()[normalize-space()]");
        $textContent = "";
        $length = 0;
        foreach ($textNodes as $node) {
            $value = trim($node->nodeValue);
            $delta = mb_strlen($value);

            if ($max !== null && $length + $delta > $max) {
                break;
            }

            $textContent .= $value . " ";
            $length += $delta;
        }

        // Extract JSON data from script tags
        $scriptNodes = $xpath->query("//script[@type='application/json']");
        $jsonContent = [];
        foreach ($scriptNodes as $node) {
            $delta = mb_strlen($node->nodeValue);
            if ($max !== null && $length + $delta > $max) {
                break;
            }

            $jsonContent[] = json_decode($node->nodeValue, true);
            $length += $delta;
        }

        // Combine text and JSON data into a meaningful format
        $formattedContent = trim($textContent) . "\n\n";
        if (!empty($jsonContent)) {
            $formattedContent .= json_encode($jsonContent) . "\n";
        }

        return $formattedContent;
    }
}
