<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Middleware;
use Easy\Router\Attributes\Route;
use Presentation\Middlewares\ViewMiddleware;
use Presentation\Response\ViewResponse;
use Presentation\Response\RedirectResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[Middleware(ViewMiddleware::class)]
#[Route(path: '/policies/[privacy|refund|terms:policy]', method: RequestMethod::GET)]
class PolicyViewRequestHandler extends AbstractRequestHandler implements
    RequestHandlerInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $policy = $request->getAttribute('policy');

        if ($policy === 'terms') {
            $policy = 'tos';
        }

        $content = $this->container->get('option.policies.' . $policy);

        // Check if content is a valid URL and redirect if it is
        if ($this->isValidUrl($content)) {
            return new RedirectResponse($content);
        }

        return new ViewResponse(
            'templates/policy.twig',
            [
                'policy' => $policy
            ]
        );
    }

    /**
     * Check if the given content is a valid URL
     */
    private function isValidUrl(string $content): bool
    {
        // Trim whitespace and check if it looks like a URL
        $trimmedContent = trim($content);

        // Check if it starts with http:// or https://
        if (preg_match('/^https?:\/\/.+/', $trimmedContent)) {
            // Validate the URL format
            return filter_var($trimmedContent, FILTER_VALIDATE_URL) !== false;
        }

        return false;
    }
}
