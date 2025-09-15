<?php

declare(strict_types=1);

namespace Presentation\EventStream;

use Ai\Domain\Entities\MessageEntity;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Ai\Domain\ValueObjects\Call;
use Ai\Domain\ValueObjects\Chunk;
use Ai\Domain\ValueObjects\ReasoningToken;
use Ai\Domain\ValueObjects\Token;
use Generator;
use JsonSerializable;
use Presentation\Resources\Api\MessageResource;
use Stringable;
use Throwable;

class Streamer
{
    private bool $isOpened = false;

    /** @return void  */
    public function open(): void
    {
        if (connection_aborted()) {
            exit;
        }

        if ($this->isOpened) {
            return;
        }

        $this->isOpened = true;

        if (ob_get_level()) {
            ob_end_clean();
        }

        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    }

    /**
     * @param string $event 
     * @param null|string|Stringable|array|JsonSerializable $data 
     * @param null|string $id 
     * @return void 
     */
    public function sendEvent(
        string $event,
        null|string|Stringable|array|JsonSerializable $data = null,
        ?string $id = null,
    ): void {
        echo "event: " . $event . PHP_EOL;

        if (is_string($data) || $data instanceof Stringable) {
            echo "data: " . $data . PHP_EOL;
        } else if (is_array($data) || $data instanceof JsonSerializable) {
            echo "data: " . json_encode($data) . PHP_EOL;
        }

        echo "id: " . ($id ?: microtime(true)) . PHP_EOL . PHP_EOL;
        flush();
    }

    /** @return void  */
    public function close(): void
    {
        if (!$this->isOpened) {
            return;
        }

        $this->isOpened = false;
    }

    /**
     * @param Generator<int,Chunk|MessageEntity> $generator
     */
    public function stream(
        Generator $generator
    ): void {
        $this->open();

        try {
            foreach ($generator as $item) {
                if ($item instanceof MessageEntity) {
                    $message = new MessageResource($item, ['conversation']);
                    $this->sendEvent('message', $message);
                    continue;
                }

                $event = match (true) {
                    $item->data instanceof Token => 'token',
                    $item->data instanceof Call => 'call',
                    $item->data instanceof ReasoningToken => 'reasoning-token',
                    default => 'ping',
                };

                $this->sendEvent($event, $item);
            }
        } catch (InsufficientCreditsException $th) {
            $this->sendEvent('error', 'Insufficient credits');
        } catch (Throwable $th) {
            $this->sendEvent('error', $th->getMessage());
        }


        $this->close();
    }
}
