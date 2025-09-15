<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Currency\RateProviders\CurrencyApi;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\RequestHandlers\Admin\AbstractAdminViewRequestHandler;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Loader\FilesystemLoader;

#[Route(path: '/settings/rate-providers/currencyapi', method: RequestMethod::GET)]
class SettingsRequestHandler extends AbstractAdminViewRequestHandler implements
    RequestHandlerInterface
{
    public function __construct(FilesystemLoader $loader,)
    {
        $loader->addPath(__DIR__, "currencyapi");
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new ViewResponse(
            '@currencyapi/settings.twig'
        );
    }
}
