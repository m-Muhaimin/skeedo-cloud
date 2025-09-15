<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\OpenAi;

use Ai\Domain\Exceptions\ApiException;
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

            if ($data === '[DONE]') {
                break;
            }

            /** @var object{error?: object{message: string|array<int, string>, type: string, code: string}} $response */
            $response = json_decode($data, flags: JSON_THROW_ON_ERROR);

            if (isset($response->error)) {
                throw new ApiException($response->error->message);
            }

            yield $response;
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
