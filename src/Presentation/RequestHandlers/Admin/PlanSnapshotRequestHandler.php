<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin;

use Billing\Application\Commands\ReadPlanSnapshotCommand;
use Billing\Domain\Entities\PlanSnapshotEntity;
use Billing\Domain\Exceptions\PlanSnapshotNotFoundException;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Resources\Admin\Api\PlanSnapshotResource;
use Presentation\Resources\Admin\Api\WorkspaceResource;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Route(path: '/plan-snapshots/[uuid:id]', method: RequestMethod::GET)]
class PlanSnapshotRequestHandler extends AbstractAdminViewRequestHandler implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');

        $cmd = new ReadPlanSnapshotCommand($id);

        try {
            /** @var PlanSnapshotEntity */
            $snapshot = $this->dispatcher->dispatch($cmd);
        } catch (PlanSnapshotNotFoundException $th) {
            return new RedirectResponse('/admin/plan-snapshots');
        }

        return new ViewResponse(
            '/templates/admin/plan-snapshot.twig',
            ['plan_snapshot' => new PlanSnapshotResource($snapshot)]
        );
    }
}
