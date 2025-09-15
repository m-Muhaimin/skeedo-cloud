<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\DocumentReader\Readers;

use Ai\Infrastructure\Services\DocumentReader\DocumentReaderInterface;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Override;

class XmlDocumentReader implements DocumentReaderInterface
{
    private const SUPPORTED_IDENTIFIERS = [
        'application/xml',
        'text/xml',
        'xml'
    ];

    #[Override]
    public function supports(string $identifier): bool
    {
        $identifier = strtolower(trim($identifier));
        return in_array($identifier, self::SUPPORTED_IDENTIFIERS, true)
            || stripos($identifier, '<?xml') === 0;
    }

    #[Override]
    public function read(string $contents, ?int $max = null): string
    {
        $dom = new DOMDocument();
        $dom->loadXML($contents, LIBXML_NOERROR | LIBXML_NOWARNING);

        $xpath = new DOMXPath($dom);

        // Get all elements
        $elements = $xpath->query('//*');
        $structuredContent = [];
        $length = 0;

        foreach ($elements as $element) {
            // Get only direct text content (excluding child elements)
            $directTextContent = '';
            foreach ($element->childNodes as $child) {
                if ($child->nodeType === XML_TEXT_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                    $directTextContent .= $child->nodeValue;
                }
            }

            $directTextContent = trim($directTextContent);
            if (!$directTextContent) {
                continue;
            }

            $path = $this->getXmlPath($element);

            // Check if adding this element would exceed max length
            $line = "{$path}: {$directTextContent}";
            $delta = mb_strlen($line);

            if ($max !== null && $length + $delta > $max) {
                break;
            }

            $structuredContent[] = $line;
            $length += $delta;

            // Add attributes if present
            if ($element->hasAttributes()) {
                foreach ($element->attributes as $attr) {
                    $line = "{$path}/@{$attr->name}: {$attr->value}";
                    $delta = mb_strlen($line);

                    if ($max !== null && $length + $delta > $max) {
                        break;
                    }

                    $structuredContent[] = $line;
                    $length += $delta;
                }
            }
        }

        return implode("\n", $structuredContent);
    }

    private function getXmlPath(DOMNode $node): string
    {
        $path = '';
        while ($node && $node->nodeType === XML_ELEMENT_NODE) {
            $position = 1;
            $previousSibling = $node->previousSibling;

            while ($previousSibling) {
                if ($previousSibling->nodeName === $node->nodeName) {
                    $position++;
                }
                $previousSibling = $previousSibling->previousSibling;
            }

            $path = '/' . $node->nodeName . '[' . $position . ']' . $path;
            $node = $node->parentNode;
        }
        return $path;
    }
}
