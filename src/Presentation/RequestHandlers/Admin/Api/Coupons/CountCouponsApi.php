<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Coupons;

use Billing\Application\Commands\CountCouponsCommand;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Easy\Router\Priority;
use Presentation\Resources\CountResource;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use ValueError;
use TypeError;

#[Route(path: '/count', method: RequestMethod::GET, priority: Priority::HIGH)]
class CountCouponsApi extends CouponApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    /**
     * @throws ValueError
     * @throws TypeError
     * @throws NoHandlerFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $cmd = new CountCouponsCommand();
        $params = (object) $request->getQueryParams();

        if (property_exists($params, 'status')) {
            $cmd->setStatus((int)$params->status);
        }

        if (property_exists($params, 'billing_cycle') && $params->billing_cycle) {
            $cmd->setBillingCycle($params->billing_cycle);
        }

        if (property_exists($params, 'discount_type') && $params->discount_type) {
            $cmd->setDiscountType($params->discount_type);
        }

        if (property_exists($params, 'plan') && $params->plan) {
            $cmd->setPlan($params->plan);
        }

        if (property_exists($params, 'query') && $params->query) {
            $cmd->query = $params->query;
        }

        $count = $this->dispatcher->dispatch($cmd);
        return new JsonResponse(new CountResource($count));
    }
}
