<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers;

use Affiliate\Application\Commands\TrackAffiliateCommand;
use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Middleware;
use Easy\Router\Attributes\Route;
use Presentation\Cookies\ReferralCookie;
use Presentation\Middlewares\ViewMiddleware;
use Presentation\Response\RedirectResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Throwable;

#[Middleware(ViewMiddleware::class)]
#[Route(path: '/r/[*:code]', method: RequestMethod::GET)]
class ReferralClickRequestHandler extends AbstractRequestHandler implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,

        #[Inject('option.affiliates.is_enabled')]
        private bool $isEnabled = false
    ) {}

    public function handle(
        ServerRequestInterface $request
    ): ResponseInterface {
        if (!$this->isEnabled) {
            return new RedirectResponse('/');
        }

        $code = $request->getAttribute('code');

        try {
            $cmd = new TrackAffiliateCommand($code, 'click');
            $this->dispatcher->dispatch($cmd);
        } catch (Throwable $th) {
            //throw $th;
        }

        $resp = new RedirectResponse('/');

        $cookie = new ReferralCookie($code);

        return $resp
            ->withHeader('Set-Cookie', $cookie->toHeaderValue());
    }
}
