<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\DocumentReader\Readers;

use Ai\Infrastructure\Exceptions\UnreadableDocumentException;
use Ai\Infrastructure\Services\DocumentReader\DocumentReaderInterface;
use Override;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class YamlDocumentReader implements DocumentReaderInterface
{
    private const SUPPORTED_IDENTIFIERS = [
        'application/yaml',
        'application/x-yaml',
        'text/yaml',
        'yaml',
        'yml'
    ];

    #[Override]
    public function supports(string $identifier): bool
    {
        $identifier = strtolower(trim($identifier));
        return in_array($identifier, self::SUPPORTED_IDENTIFIERS, true)
            // Check for YAML front matter
            || preg_match('/^---(\s|$)/', $identifier);
    }

    #[Override]
    public function read(string $contents, ?int $max = null): string
    {
        try {
            $data = Yaml::parse($contents);
            if (!is_array($data)) {
                return (string) $data;
            }

            return $this->flattenWithContext($data, $max);
        } catch (ParseException $e) {
            throw new UnreadableDocumentException(
                message: 'Unable to parse YAML content: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    private function flattenWithContext(array $data, ?int $max = null, string $path = ''): string
    {
        $result = [];
        $length = 0;

        foreach ($data as $key => $value) {
            $currentPath = $path ? "$path.$key" : $key;

            if (is_array($value)) {
                $nestedContent = $this->flattenWithContext($value, $max, $currentPath);
                $delta = mb_strlen($nestedContent);

                if ($max !== null && $length + $delta > $max) {
                    break;
                }

                $result[] = $nestedContent;
                $length += $delta;
            } else {
                $line = "$currentPath: " . $this->formatValue($value);
                $delta = mb_strlen($line);

                if ($max !== null && $length + $delta > $max) {
                    break;
                }

                $result[] = $line;
                $length += $delta;
            }
        }

        return implode("\n", $result);
    }

    private function formatValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'null';
        }

        return (string) $value;
    }
}
