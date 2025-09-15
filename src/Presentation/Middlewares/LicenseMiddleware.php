<?php

declare(strict_types=1);

namespace Presentation\Middlewares;

use Easy\Container\Attributes\Inject;
use Exception;
use Presentation\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\Services\LicenseManager;

class LicenseMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LicenseManager $lm
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $path = $request->getUri()->getPath();

        if (strpos($path, '/admin/license') !== false) {
            return $handler->handle($request);
        }
        try {
            $this->lm->verify();
        } catch (Exception $e) {
            return new RedirectResponse('/admin/license');
        }

        return $handler->handle($request);
    }
}
