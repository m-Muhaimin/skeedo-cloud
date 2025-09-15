<?php

declare(strict_types=1);

namespace Presentation\Http\Message;

use Closure;
use Override;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class CallbackStream implements StreamInterface
{
    private bool $called = false;
    private array $args = [];
    private ?Closure $callback = null;

    /**
     * @param Closure $callback 
     * @param array $args 
     * @return void 
     */
    public function __construct(
        Closure $callback,
        mixed ...$args
    ) {
        $this->callback = $callback;
        $this->args = $args;
    }

    #[Override]
    public function __toString()
    {
        return $this->getContents();
    }

    #[Override]
    public function close(): void
    {
        // There is nothing to do here.
    }

    #[Override]
    public function detach()
    {
        $callback = $this->callback;
        $this->callback = null;
        return $callback;
    }

    #[Override]
    public function getSize(): ?int
    {
        return null;
    }

    #[Override]
    public function tell(): int
    {
        return 0;
    }

    #[Override]
    public function eof(): bool
    {
        return $this->called;
    }

    #[Override]
    public function isSeekable(): bool
    {
        return false;
    }

    #[Override]
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        throw new RuntimeException('Cannot seek a callback stream');
    }

    #[Override]
    public function rewind(): void
    {
        throw new RuntimeException('Cannot seek a callback stream');
    }

    #[Override]
    public function isWritable(): bool
    {
        return false;
    }

    #[Override]
    public function write(string $string): int
    {
        throw new RuntimeException('Cannot write to a callback stream');
    }

    #[Override]
    public function isReadable(): bool
    {
        return true;
    }

    #[Override]
    public function read(int $length): string
    {
        return $this->getContents();
    }

    #[Override]
    public function getContents(): string
    {
        if ($this->called || !$this->callback) {
            return '';
        }

        $this->called = true;
        $content = call_user_func($this->callback, ...$this->args);
        return is_string($content) ? $content : '';
    }

    #[Override]
    public function getMetadata(?string $key = null)
    {
        return null;
    }
}
