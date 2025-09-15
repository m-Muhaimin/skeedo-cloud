<?php

declare(strict_types=1);

namespace Cron\Domain\Events;

use Cron\Infrastructure\Listeners\CalculateMRR;
use Cron\Infrastructure\Listeners\EndCancelledSubscriptions;
use Cron\Infrastructure\Listeners\EndFailedGenerations;
use Cron\Infrastructure\Listeners\RenewSubscriptions;
use Cron\Infrastructure\Listeners\SaveLastRun;
use Easy\EventDispatcher\Attributes\Listener;
use Easy\EventDispatcher\Priority;

#[Listener(RenewSubscriptions::class)]
#[Listener(EndCancelledSubscriptions::class)]
#[Listener(CalculateMRR::class)]
#[Listener(EndFailedGenerations::class)]
#[Listener(SaveLastRun::class, Priority::LOW)]
class CronEvent {}
