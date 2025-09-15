<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin;

use Billing\Application\Commands\ReadCouponCommand;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Billing\Domain\Exceptions\CouponNotFoundException;
use Presentation\Resources\Admin\Api\CouponResource;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Route(path: '/coupons/[uuid:id]', method: RequestMethod::GET)]
#[Route(path: '/coupons/new', method: RequestMethod::GET)]
class CouponView extends AbstractAdminViewRequestHandler implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');

        $data = [];

        if ($id) {
            $cmd = new ReadCouponCommand($id);

            try {
                $coupon = $this->dispatcher->dispatch($cmd);
            } catch (CouponNotFoundException $th) {
                return new RedirectResponse('/admin/coupons');
            }

            $data['coupon'] = new CouponResource($coupon);
        }

        return new ViewResponse(
            '/templates/admin/coupon.twig',
            $data
        );
    }
}
