<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Account;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Path;
use Easy\Router\Attributes\Route;
use Presentation\RequestHandlers\Api\Api;
use Presentation\Resources\Api\UserResource;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Domain\Entities\UserEntity;

#[Path('/account')]
#[Route(path: '/', method: RequestMethod::GET)]
class AccountApi extends Api implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        return new JsonResponse(new UserResource($user, ['workspace']));
    }
}
