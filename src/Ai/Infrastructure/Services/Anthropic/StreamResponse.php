<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Anthropic;

use Generator;
use IteratorAggregate;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class StreamResponse implements IteratorAggregate
{
    public function __construct(
        private readonly ResponseInterface $response,
    ) {}

    /**
     * @inheritDoc
     * @return Generator<object>
     * @throws RuntimeException
     */
    #[Override]
    public function getIterator(): Generator
    {
        while (!$this->response->getBody()->eof()) {
            $line = $this->readLine($this->response->getBody());

            if (!str_starts_with($line, 'data:')) {
                continue;
            }

            $data = trim(substr($line, strlen('data:')));

            yield json_decode($data, flags: JSON_THROW_ON_ERROR);
        }
    }

    /**
     * Read a line from the stream.
     */
    private function readLine(StreamInterface $stream): string
    {
        $buffer = '';

        while (!$stream->eof()) {
            if ('' === ($byte = $stream->read(1))) {
                return $buffer;
            }
            $buffer .= $byte;
            if ($byte === "\n") {
                break;
            }
        }

        return $buffer;
    }
}
