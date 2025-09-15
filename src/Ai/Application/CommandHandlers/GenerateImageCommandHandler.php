<?php

declare(strict_types=1);

namespace Ai\Application\CommandHandlers;

use Ai\Application\Commands\GenerateImageCommand;
use Ai\Domain\Entities\ImageEntity;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Ai\Domain\Exceptions\ModelNotAccessibleException;
use Ai\Domain\Image\ImageServiceInterface;
use Ai\Domain\Repositories\LibraryItemRepositoryInterface;
use Ai\Domain\Services\AiServiceFactoryInterface;
use Ai\Domain\Title\TitleServiceInterface;
use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Model;
use Billing\Domain\Events\CreditUsageEvent;
use Easy\Container\Attributes\Inject;
use Psr\EventDispatcher\EventDispatcherInterface;
use User\Domain\Entities\UserEntity;
use User\Domain\Repositories\UserRepositoryInterface;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Repositories\WorkspaceRepositoryInterface;

class GenerateImageCommandHandler
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

    public function handle(GenerateImageCommand $cmd): ImageEntity
    {
        ini_set('max_execution_time', '0');

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

        $sub = $ws->getSubscription();
        $models = $sub ? $sub->getPlan()->getConfig()->models : [];

        if (!isset($models[$cmd->model->value]) || !$models[$cmd->model->value]) {
            throw new ModelNotAccessibleException($cmd->model);
        }

        $service = $this->factory->create(
            ImageServiceInterface::class,
            $cmd->model
        );

        $entity = $service->generateImage(
            $ws,
            $user,
            $cmd->model,
            $cmd->params
        );

        if (
            is_null($entity->getTitle()->value)
            && isset($cmd->params['prompt'])
        ) {
            $service = $this->factory->create(
                TitleServiceInterface::class,
                $ws->getSubscription()
                    ? $ws->getSubscription()->getPlan()->getConfig()->titler->model
                    : new Model('gpt-3.5-turbo')
            );

            $content = new Content($cmd->params['prompt']);
            $titleResp = $service->generateTitle(
                $content,
                $ws->getSubscription()
                    ? $ws->getSubscription()->getPlan()->getConfig()->titler->model
                    : new Model('gpt-3.5-turbo')
            );

            $entity->setTitle($titleResp->title);
            $entity->addCost($titleResp->cost);
        }

        $this->repo->add($entity);

        if ($entity->getCost()->value > 0) {
            // Deduct credit from workspace
            $ws->deductCredit($entity->getCost(), $this->negativeBalance);

            // Dispatch event
            $event = new CreditUsageEvent($ws, $entity->getCost());
            $this->dispatcher->dispatch($event);
        }

        return $entity;
    }
}
