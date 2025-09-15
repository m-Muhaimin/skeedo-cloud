<?php

declare(strict_types=1);

namespace Ai\Domain\Composition;

use Ai\Domain\ValueObjects\CompositionDetails;
use Ai\Domain\ValueObjects\Title;
use Billing\Domain\ValueObjects\CreditCount;
use GdImage;
use Psr\Http\Message\StreamInterface;

class CompositionResponse
{
    public function __construct(
        public readonly StreamInterface $audioContent,
        public readonly CreditCount $cost,
        public readonly ?GdImage $image,
        public readonly ?Title $title,
        public readonly ?CompositionDetails $details,
    ) {}
}
