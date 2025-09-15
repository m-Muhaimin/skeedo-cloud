<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Users;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use Presentation\Response\JsonResponse;
use Presentation\Resources\CountResource;
use User\Application\Commands\CountUsersCommand;

#[Route(path: '/count', method: RequestMethod::GET)]
class CountUsersRequestHandler extends UserApi implements
    RequestHandlerInterface
{
    /**
     * @param Dispatcher $dispatcher
     * @return void
     */
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws NoHandlerFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $cmd = new CountUsersCommand();
        $params = (object) $request->getQueryParams();

        if (property_exists($params, 'status')) {
            $cmd->setStatus((int) $params->status);
        }

        if (property_exists($params, 'role')) {
            $cmd->setRole((int) $params->role);
        }

        if (property_exists($params, 'country_code')) {
            $cmd->setCountryCode($params->country_code);
        }

        if (property_exists($params, 'is_email_verified')) {
            $cmd->setIsEmailVerified((bool) $params->is_email_verified);
        }

        if (property_exists($params, 'ref')) {
            $cmd->setRef($params->ref);
        }

        if (property_exists($params, 'created_at')) {
            $parts = explode(':', $params->created_at, 2);
            $cmd->setAfter($parts[0]);

            if (count($parts) > 1) {
                $cmd->setBefore($parts[1]);
            }
        }

        if (property_exists($params, 'query') && $params->query) {
            $cmd->query = $params->query;
        }

        $count = $this->dispatcher->dispatch($cmd);
        return new JsonResponse(new CountResource($count));
    }
}
