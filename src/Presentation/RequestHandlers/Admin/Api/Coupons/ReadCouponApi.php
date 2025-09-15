<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Coupons;

use Billing\Application\Commands\ReadCouponCommand;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Billing\Domain\Exceptions\CouponNotFoundException;
use Presentation\Exceptions\NotFoundException;
use Presentation\Resources\Admin\Api\CouponResource;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Route(path: '/[uuid:id]', method: RequestMethod::GET)]
class ReadCouponApi extends CouponApi implements RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    /**
     * @throws NotFoundException
     */
    public function handle(
        ServerRequestInterface $request
    ): ResponseInterface {
        $id = $request->getAttribute('id');

        $cmd = new ReadCouponCommand($id);

        try {
            $coupon = $this->dispatcher->dispatch($cmd);
        } catch (CouponNotFoundException $th) {
            throw new NotFoundException(
                param: 'id',
                previous: $th
            );
        }

        return new JsonResponse(new CouponResource($coupon));
    }
}
