<?php

declare(strict_types=1);

namespace User\Application\CommandHandlers;

use Affiliate\Domain\Exceptions\AffiliateNotFoundException;
use Affiliate\Domain\Repositories\AffiliateRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use User\Application\Commands\CreateUserCommand;
use User\Domain\Entities\UserEntity;
use User\Domain\Events\UserCreatedEvent;
use User\Domain\Exceptions\EmailTakenException;
use User\Domain\Repositories\UserRepositoryInterface;
use User\Infrastructure\Services\EmailChecker;

class CreateUserCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $repo,
        private AffiliateRepositoryInterface $affiliateRepo,
        private EventDispatcherInterface $dispatcher,
        private EmailChecker $service
    ) {}

    /**
     * @throws EmailTakenException
     */
    public function handle(CreateUserCommand $cmd): UserEntity
    {
        if (!$this->service->isValid($cmd->email)) {
            throw new EmailTakenException($cmd->email);
        }

        $user = new UserEntity(
            email: $cmd->email,
            firstName: $cmd->firstName,
            lastName: $cmd->lastName
        );

        if ($cmd->password) {
            $user->setPassword($cmd->password);
        }

        if ($cmd->phoneNumber) {
            $user->setPhoneNumber($cmd->phoneNumber);
        }

        if ($cmd->language) {
            $user->setLanguage($cmd->language);
        }

        if ($cmd->ip) {
            $user->setIp($cmd->ip);
        }

        if ($cmd->countryCode) {
            $user->setCountryCode($cmd->countryCode);
        }

        if ($cmd->cityName) {
            $user->setCityName($cmd->cityName);
        }

        if ($cmd->role) {
            $user->setRole($cmd->role);
        }

        if ($cmd->status) {
            $user->setStatus($cmd->status);
        }

        if ($cmd->workspaceCap) {
            $user->setWorkspaceCap($cmd->workspaceCap);
        }

        // Find the affiliate
        if ($cmd->refCode) {
            try {
                $affiliate = $this->affiliateRepo->ofCode($cmd->refCode);
                $user->setReferredBy($affiliate->getUser());
            } catch (AffiliateNotFoundException $th) {
                //throw $th;
            }
        }

        if (
            $cmd->isEmailVerified !== null
            && $cmd->isEmailVerified->value
        ) {
            $token = $user->getEmailVerificationToken();
            $user->verifyEmail($token);
        }

        $this->repo->add($user);

        // Dispatch the user created event
        $event = new UserCreatedEvent($user);
        $this->dispatcher->dispatch($event);

        return $user;
    }
}
