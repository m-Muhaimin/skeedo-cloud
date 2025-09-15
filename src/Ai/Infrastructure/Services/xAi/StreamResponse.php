<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\xAi;

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
     * @return Generator<object{
     *   id: string,
     *   object: 'chat.completion.chunk',
     *   created: int,
     *   model: string,
     *   choices: array<array-key, object{
     *     index: int,
     *     delta: object{
     *       content: string,
     *       role: string
     *     }
     *   }>,
     *   usage: object{
     *     prompt_tokens: int,
     *     completion_tokens: int,
     *     total_tokens: int
     *   },
     *   system_fingerprint: string
     * }>
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

            $response = json_decode($data, flags: JSON_THROW_ON_ERROR);
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
