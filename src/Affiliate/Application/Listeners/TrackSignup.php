<?php

declare(strict_types=1);

namespace Affiliate\Application\Listeners;

use Affiliate\Application\Commands\TrackAffiliateCommand;
use Easy\Container\Attributes\Inject;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Throwable;
use User\Domain\Events\UserCreatedEvent;

class TrackSignup
{
    public function __construct(
        private Dispatcher $dispatcher,

        #[Inject('option.affiliates.is_enabled')]
        private bool $isEnabled = false,
    ) {}

    /**
     * Handle the UserCreatedEvent to track affiliate referrals.
     *
     * This method is invoked when a new user is created. It checks if the affiliate
     * tracking is enabled and if the user was referred by an affiliate. If both
     * conditions are met, it dispatches a TrackAffiliateCommand to record the referral.
     *
     * @param UserCreatedEvent $event The event containing the newly created user.
     * @return void
     */
    public function __invoke(UserCreatedEvent $event): void
    {
        if (!$this->isEnabled) {
            return;
        }

        $user = $event->user;

        if (!$user->getReferredBy()) {
            return;
        }

        try {
            $cmd = new TrackAffiliateCommand(
                $user->getReferredBy()->getAffiliate()->getCode(),
                'referral'
            );

            $this->dispatcher->dispatch($cmd);
        } catch (Throwable $th) {
            // Silent fail
        }
    }
}
