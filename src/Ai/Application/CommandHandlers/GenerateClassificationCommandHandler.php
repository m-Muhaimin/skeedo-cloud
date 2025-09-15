<?php

declare(strict_types=1);

namespace Ai\Application\CommandHandlers;

use Ai\Application\Commands\GenerateClassificationCommand;
use Ai\Domain\Classification\ClassificationServiceInterface;
use Ai\Domain\Entities\ClassificationEntity;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Ai\Domain\Repositories\LibraryItemRepositoryInterface;
use Ai\Domain\Services\AiServiceFactoryInterface;
use Ai\Domain\Title\TitleServiceInterface;
use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\RequestParams;
use Billing\Domain\Events\CreditUsageEvent;
use Billing\Domain\ValueObjects\CreditCount;
use Easy\Container\Attributes\Inject;
use Psr\EventDispatcher\EventDispatcherInterface;
use User\Domain\Entities\UserEntity;
use User\Domain\Repositories\UserRepositoryInterface;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Repositories\WorkspaceRepositoryInterface;

class GenerateClassificationCommandHandler
{
    public function __construct(
        private AiServiceFactoryInterface $factory,
        private WorkspaceRepositoryInterface $wsRepo,
        private UserRepositoryInterface $userRepo,
        private LibraryItemRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher,

        #[Inject('option.billing.negative_balance_enabled')]
        private bool $negativeBalance = false,
    ) {}

    public function handle(
        GenerateClassificationCommand $cmd
    ): ClassificationEntity {
        $ws = $cmd->workspace instanceof WorkspaceEntity
            ? $cmd->workspace
            : $this->wsRepo->ofId($cmd->workspace);

        $user = $cmd->user instanceof UserEntity
            ? $cmd->user
            : $this->userRepo->ofId($cmd->user);

        if (
            !is_null($ws->getTotalCreditCount()->value)
            && (float) $ws->getTotalCreditCount()->value <= 0
        ) {
            throw new InsufficientCreditsException();
        }

        $service = $this->factory->create(
            ClassificationServiceInterface::class,
            $cmd->model
        );

        $resp = $service->generateClassification(
            $cmd->model,
            $cmd->input
        );

        $service = $this->factory->create(
            TitleServiceInterface::class,
            $ws->getSubscription()
                ? $ws->getSubscription()->getPlan()->getConfig()->titler->model
                : new Model('gpt-3.5-turbo')
        );

        $content = new Content($cmd->input);
        $titleResp = $service->generateTitle(
            $content,
            $ws->getSubscription()
                ? $ws->getSubscription()->getPlan()->getConfig()->titler->model
                : new Model('gpt-3.5-turbo')
        );

        /** @var CreditCount */
        $cost = $resp->cost;
        $cost = new CreditCount($cost->value + $titleResp->cost->value);

        $entity = new ClassificationEntity(
            $ws,
            $user,
            $titleResp->title,
            $resp->classification,
            $cmd->model,
            RequestParams::fromArray([
                'input' => $cmd->input,
            ]),
            $cost
        );

        $this->repo->add($entity);

        // Deduct credit from workspace
        $ws->deductCredit($cost, $this->negativeBalance);

        // Dispatch event
        $event = new CreditUsageEvent($ws, $cost);
        $this->dispatcher->dispatch($event);

        return $entity;
    }
}
