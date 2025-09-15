<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Coupons;

use Billing\Application\Commands\ListCouponsCommand;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Iterator;
use Billing\Domain\Entities\CouponEntity;
use Billing\Domain\Exceptions\PlanNotFoundException;
use Presentation\Resources\Admin\Api\CouponResource;
use Presentation\Resources\ListResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\ValidationException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use ValueError;
use TypeError;

#[Route(path: '/', method: RequestMethod::GET)]
class ListCouponsApi extends CouponApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    /**
     * @throws ValueError
     * @throws TypeError
     * @throws ValidationException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $cmd = new ListCouponsCommand();
        $params = (object) $request->getQueryParams();

        if (property_exists($params, 'status')) {
            $cmd->setStatus((int) $params->status);
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

        if (property_exists($params, 'sort') && $params->sort) {
            $sort = explode(':', $params->sort);
            $orderBy = $sort[0];
            $dir = $sort[1] ?? 'asc';
            $cmd->setOrderBy($orderBy, $dir);
        }

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
            /** @var Iterator<int,CouponEntity> $coupons */
            $coupons = $this->dispatcher->dispatch($cmd);
        } catch (PlanNotFoundException $th) {
            throw new ValidationException(
                'Invalid cursor',
                property_exists($params, 'starting_after')
                    ? 'starting_after'
                    : 'ending_before',
                previous: $th
            );
        }

        $res = new ListResource();
        foreach ($coupons as $coupon) {
            $res->pushData(new CouponResource($coupon));
        }

        return new JsonResponse($res);
    }
}
