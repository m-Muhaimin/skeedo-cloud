<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin;

use Billing\Infrastructure\Currency\RateProviderCollectionInterface;
use Billing\Infrastructure\Payments\PaymentGatewayFactoryInterface;
use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Resources\CurrencyResource;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Domain\ValueObjects\CurrencyCode;
use Shared\Infrastructure\FileSystem\CdnAdapterCollectionInterface;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Intl\Exception\MissingResourceException;

#[Route(path: '/settings', method: RequestMethod::GET)]
#[Route(
    path: '/settings/[general|models|logo|billing|payments|credits|rate-providers|affiliates|openai|cohere|anthropic|xai|ollama|elevenlabs|speechify|falai|luma|stabilityai|gcp|azure|clipdrop|aimlapi|onesignal|serper|searchapi|mail|smtp|policies|accounts|public-details|recaptcha|appearance|pwa|storage|cdn:name]?',
    method: RequestMethod::GET
)]
#[Route(
    path: '/settings/[identity-providers:group]/[google|linkedin|facebook|github:name]?',
    method: RequestMethod::GET
)]
#[Route(
    path: '/settings/[script-tags:group]/[google-analytics|google-tag-manager|intercom|custom:name]?',
    method: RequestMethod::GET
)]
#[Route(
    path: '/settings/[features:group]/[writer|chat|voiceover|video|imagine|composer|rest-api:name]?',
    method: RequestMethod::GET
)]
class SettingsRequestHandler extends AbstractAdminViewRequestHandler implements
    RequestHandlerInterface
{
    public function __construct(
        private PaymentGatewayFactoryInterface $factory,
        private CdnAdapterCollectionInterface $cdnAdapters,
        private RateProviderCollectionInterface $rateProviders,

        #[Inject('config.dirs.webroot')]
        private string $webroot,
    ) {}

    /**
     * @throws MissingResourceException 
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $name = $request->getAttribute('name');
        $group = $request->getAttribute('group');
        if (!$name) {
            $name = 'index';
        }

        if ($group) {
            $name = $group . '/' . $name;
        }

        $data = [];
        $currencies = array_filter(
            Currencies::getNames(),
            fn($code) => CurrencyCode::tryFrom($code) !== null,
            ARRAY_FILTER_USE_KEY
        );

        $data['currencies'] = array_map(
            fn($code) => new CurrencyResource($code),
            array_keys($currencies)
        );

        $data['payment_gateways'] = $this->factory;
        $data['cdn_adapters'] = $this->cdnAdapters;
        $data['rate_providers'] = $this->rateProviders;

        $path = $this->webroot . '/app.webmanifest';
        $data['pwa'] = json_decode(
            file_exists($path) ? file_get_contents($path) : '{}'
        );

        return new ViewResponse(
            '/templates/admin/settings/' . $name . '.twig',
            $data
        );
    }
}
