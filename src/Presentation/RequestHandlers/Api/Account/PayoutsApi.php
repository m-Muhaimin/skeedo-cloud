<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Account;

use Affiliate\Application\Commands\PayoutCommand;
use Affiliate\Domain\Exceptions\InsufficientBalanceException;
use Easy\Http\Message\RequestMethod;
use Easy\Http\Message\StatusCode;
use Easy\Router\Attributes\Path;
use Easy\Router\Attributes\Route;
use Override;
use Presentation\Exceptions\HttpException;
use Presentation\Resources\Api\UserResource;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;

#[Path('/payouts')]
#[Route(path: '/', method: RequestMethod::PUT)]
#[Route(path: '/', method: RequestMethod::POST)]
class PayoutsApi extends AffiliateApi implements RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        $cmd = new PayoutCommand($user);

        try {
            $this->dispatcher->dispatch($cmd);
        } catch (InsufficientBalanceException $th) {
            throw new HttpException(
                message: 'Insufficient balance',
                statusCode: StatusCode::UNPROCESSABLE_ENTITY
            );
        }

        return new JsonResponse(new UserResource($user));
    }
}
