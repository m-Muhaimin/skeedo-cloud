<?php

declare(strict_types=1);

namespace Billing\Domain\Events;

use Affiliate\Application\Listeners\TrackConversion;
use Easy\EventDispatcher\Attributes\Listener;

#[Listener(TrackConversion::class)]
class OrderFulfilledEvent extends AbstractOrderEvent {}
