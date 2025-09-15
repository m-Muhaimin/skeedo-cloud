<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\DocumentReader\Readers;

use Ai\Infrastructure\Exceptions\UnreadableDocumentException;
use Ai\Infrastructure\Services\DocumentReader\DocumentReaderInterface;
use Override;

class CsvDocumentReader implements DocumentReaderInterface
{
    private const SUPPORTED_IDENTIFIERS = [
        'text/csv',
        'csv'
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
        try {
            $handle = fopen('php://memory', 'r+');
            fwrite($handle, $contents);
            rewind($handle);

            $delimiter = $this->detectDelimiter($handle);
            rewind($handle);

            // Read headers
            $headers = fgetcsv($handle, 0, $delimiter);
            if (!$headers) {
                throw new UnreadableDocumentException('Unable to read CSV headers');
            }

            $result = [];
            $length = 0;

            // Process each row
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowData = [];
                foreach ($headers as $index => $header) {
                    if (!isset($row[$index])) {
                        continue;
                    }

                    $value = trim($row[$index]);
                    if ($value === '') {
                        continue;
                    }

                    $rowData[] = "{$header}: {$value}";
                }

                if (!empty($rowData)) {
                    $line = implode(', ', $rowData);
                    $delta = mb_strlen($line);

                    if ($max !== null && $length + $delta > $max) {
                        break;
                    }

                    $result[] = $line;
                    $length += $delta;
                }
            }

            fclose($handle);
            return implode("\n", $result);
        } catch (\Throwable $e) {
            if (isset($handle)) {
                fclose($handle);
            }
            throw new UnreadableDocumentException(
                message: 'Unable to parse CSV content: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    private function detectDelimiter($handle): string
    {
        $delimiters = [',', ';', '\t', '|'];
        $data = fgets($handle);
        if (!$data) {
            return ','; // default to comma
        }

        $results = [];
        foreach ($delimiters as $delimiter) {
            $results[$delimiter] = substr_count($data, $delimiter);
        }

        return array_search(max($results), $results) ?: ',';
    }
}
