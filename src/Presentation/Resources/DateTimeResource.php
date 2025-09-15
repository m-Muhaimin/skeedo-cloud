<?php

declare(strict_types=1);

namespace Presentation\Resources;

use DateTimeInterface;
use JsonSerializable;

class DateTimeResource implements JsonSerializable
{
    /**
     * @param null|DateTimeInterface $dateTime
     * @return void
     */
    public function __construct(
        private ?DateTimeInterface $dateTime
    ) {
    }

    /** @inheritDoc */
    public function jsonSerialize(): ?int
    {
        return $this->dateTime ? $this->dateTime->getTimestamp() : null;
    }
}
