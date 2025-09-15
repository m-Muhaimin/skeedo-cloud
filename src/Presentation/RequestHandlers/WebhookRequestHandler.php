<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers;

use Billing\Infrastructure\Payments\Exceptions\WebhookException;
use Billing\Infrastructure\Payments\PaymentGatewayFactoryInterface;
use Easy\Http\Message\RequestMethod;
use Easy\Http\Message\StatusCode;
use Easy\Router\Attributes\Middleware;
use Easy\Router\Attributes\Route;
use Presentation\Middlewares\ExceptionMiddleware;
use Presentation\Response\EmptyResponse;
use Presentation\Response\JsonResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[Middleware(ExceptionMiddleware::class)]
#[Route(path: '/webhooks/[:gateway]', method: RequestMethod::POST)]
class WebhookRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private PaymentGatewayFactoryInterface $factory,
        private ContainerInterface $container
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $gateway = $this->factory->create($request->getAttribute('gateway'));

        // Handle the webhook request...
        $handler = $gateway->getWebhookHandler();

        if (is_string($handler)) {
            $handler = $this->container->get($handler);
        }

        try {
            $handler->handle($request);
        } catch (WebhookException $th) {
            return new JsonResponse(['error' => $th->getMessage()], StatusCode::BAD_REQUEST);
        }

        return new EmptyResponse();
    }
}
