<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin;

use Affiliate\Application\Commands\ReadPayoutCommand;
use Affiliate\Domain\Entities\PayoutEntity;
use Affiliate\Domain\Exceptions\PayoutNotFoundException;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Resources\Admin\Api\PayoutResource;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;

#[Route(path: '/affiliates/payouts/[uuid:id]', method: RequestMethod::GET)]
class PayoutRequestHandler extends AbstractAdminViewRequestHandler implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    /**
     * @throws NoHandlerFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');

        $cmd = new ReadPayoutCommand($id);

        try {
            /** @var PayoutEntity */
            $payout = $this->dispatcher->dispatch($cmd);
        } catch (PayoutNotFoundException) {
            return new RedirectResponse('/admin/affiliates/payouts');
        }

        return new ViewResponse(
            '/templates/admin/affiliates/payout.twig',
            ['payout' => new PayoutResource($payout, ['affiliate', 'affiliate.user'])]
        );
    }
}
