<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Workspaces;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Resources\Api\UsageStatResource;
use Presentation\Resources\ListResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Stat\Application\Commands\ListStatsCommand;
use Stat\Domain\Entities\UsageStatEntity;
use Stat\Domain\Exceptions\StatNotFoundException;
use Stat\Domain\ValueObjects\StatType;

#[Route(path: '/[uuid:wid]/logs/usage', method: RequestMethod::GET)]
class ListUsageLogs extends WorkspaceApi implements RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = (object) $request->getQueryParams();

        $cmd = new ListStatsCommand(StatType::USAGE);
        $cmd->setWorkspace($request->getAttribute("wid"));

        if (property_exists($params, 'starting_after') && $params->starting_after) {
            $cmd->setCursor(
                $params->starting_after,
                'starting_after'
            );
        } elseif (property_exists($params, 'ending_before') && $params->ending_before) {
            $cmd->setCursor(
                $params->ending_before,
                'ending_before'
            );
        }

        if (property_exists($params, 'limit')) {
            $cmd->setLimit((int) $params->limit);
        }

        try {
            /** @var Iterator<int,UsageStatEntity> $stats */
            $stats = $this->dispatcher->dispatch($cmd);
        } catch (StatNotFoundException $th) {
            throw new ValidationException(
                'Invalid cursor',
                property_exists($params, 'starting_after')
                    ? 'starting_after'
                    : 'ending_before',
                previous: $th
            );
        }

        $res = new ListResource();
        foreach ($stats as $stat) {
            $res->pushData(new UsageStatResource($stat));
        }

        return new JsonResponse($res);
    }
}
