<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Assistants;

use Assistant\Application\Commands\ListAssistantsCommand;
use Assistant\Domain\Entities\AssistantEntity;
use Assistant\Domain\Exceptions\AssistantNotFoundException;
use Assistant\Domain\ValueObjects\Status;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Override;
use Presentation\Resources\Api\AssistantResource;
use Presentation\Resources\ListResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\ValidationException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use Traversable;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/', method: RequestMethod::GET)]
class ListAssistantsRequestHandler extends AssistantApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    /**
     * @throws ValidationException
     * @throws NoHandlerFoundException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var WorkspaceEntity */
        $ws = $request->getAttribute(WorkspaceEntity::class);

        $params = (object) $request->getQueryParams();

        $cmd = new ListAssistantsCommand();
        $cmd->status = Status::from(1);

        $config = $ws->getSubscription()?->getPlan()->getConfig();
        if ($config && $config->assistants !== null && !isset($params->all)) {
            $cmd->setIds(...$config->assistants);
        }

        if (property_exists($params, 'query') && $params->query) {
            $cmd->query = $params->query;
        }

        if (property_exists($params, 'sort') && $params->sort) {
            $sort = explode(':', $params->sort);
            $orderBy = $sort[0];
            $dir = $sort[1] ?? 'asc';
            $cmd->setOrderBy($orderBy, $dir);
        }

        if (
            property_exists($params, 'starting_after')
            && $params->starting_after
        ) {
            $cmd->setCursor(
                $params->starting_after,
                'starting_after'
            );
        } elseif (
            property_exists($params, 'ending_before')
            && $params->ending_before
        ) {
            $cmd->setCursor(
                $params->ending_before,
                'ending_before'
            );
        }

        if (property_exists($params, 'limit')) {
            $cmd->setLimit((int) $params->limit);
        }

        try {
            /** @var Traversable<int,AssistantEntity> $assistants */
            $assistants = $this->dispatcher->dispatch($cmd);
        } catch (AssistantNotFoundException $th) {
            throw new ValidationException(
                'Invalid cursor',
                property_exists($params, 'starting_after')
                    ? 'starting_after'
                    : 'ending_before',
                previous: $th
            );
        }

        $res = new ListResource();
        foreach ($assistants as $assistant) {
            $r = new AssistantResource($assistant, $ws->getSubscription());
            $res->pushData($r);
        }

        return new JsonResponse($res);
    }
}
