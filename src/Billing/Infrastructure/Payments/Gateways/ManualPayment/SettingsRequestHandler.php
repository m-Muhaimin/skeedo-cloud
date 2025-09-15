<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments\Gateways\ManualPayment;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\RequestHandlers\Admin\AbstractAdminViewRequestHandler;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Domain\ValueObjects\CurrencyCode;
use Symfony\Component\Intl\Currencies;
use Twig\Loader\FilesystemLoader;

#[Route(path: '/settings/payments/manual-payment', method: RequestMethod::GET)]
class SettingsRequestHandler extends AbstractAdminViewRequestHandler implements
    RequestHandlerInterface
{
    public function __construct(FilesystemLoader $loader,)
    {
        $loader->addPath(__DIR__, "manualpayment");
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = [];
        $data['currencies'] = array_filter(
            Currencies::getNames(),
            fn($code) => CurrencyCode::tryFrom($code) !== null,
            ARRAY_FILTER_USE_KEY
        );

        return new ViewResponse(
            '@manualpayment/settings.twig',
            $data
        );
    }
}
