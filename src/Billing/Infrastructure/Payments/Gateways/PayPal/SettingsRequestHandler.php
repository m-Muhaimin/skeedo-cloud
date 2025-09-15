<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments\Gateways\PayPal;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\RequestHandlers\Admin\AbstractAdminViewRequestHandler;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Intl\Currencies;
use Twig\Loader\FilesystemLoader;

#[Route(path: '/settings/payments/paypal', method: RequestMethod::GET)]
class SettingsRequestHandler extends AbstractAdminViewRequestHandler implements
    RequestHandlerInterface
{
    public function __construct(FilesystemLoader $loader,)
    {
        $loader->addPath(__DIR__, "paypal");
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $currencies = Currencies::getNames();

        $supported = [
            "AUD", "BRL", "CAD", "CNY", "CZK", "DKK", "EUR", "HKD", "HUF",
            "ILS", "JPY", "MYR", "MXN", "TWD", "NZD", "NOK", "PHP", "PLN",
            "GBP", "SGD", "SEK", "CHF", "THB", "USD"
        ];

        $currencies = array_filter(
            $currencies,
            fn ($key) => in_array($key, $supported),
            ARRAY_FILTER_USE_KEY
        );

        return new ViewResponse(
            '@paypal/settings.twig',
            [
                'currencies' => $currencies,
            ]
        );
    }
}
