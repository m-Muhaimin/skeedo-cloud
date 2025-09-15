<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\PlanSnapshots;

use Billing\Application\Commands\ResyncPlanSnapshotCommand;
use Billing\Domain\Exceptions\PlanSnapshotNotFoundException;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\NotFoundException;
use Presentation\Resources\Admin\Api\PlanSnapshotResource;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Route(path: '/[uuid:id]/resync', method: RequestMethod::POST)]
class ResyncRequestHandler extends SnapshotApi implements RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');

        $cmd = new ResyncPlanSnapshotCommand($id);

        try {
            $snapshot = $this->dispatcher->dispatch($cmd);
        } catch (PlanSnapshotNotFoundException $th) {
            throw new NotFoundException(
                param: 'id',
                previous: $th
            );
        }

        return new JsonResponse(new PlanSnapshotResource($snapshot));
    }
}
