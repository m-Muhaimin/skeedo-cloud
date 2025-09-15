<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App;

use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Override;
use Presentation\Exceptions\NotFoundException;
use Presentation\Response\JsonResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[Route(path: '/api-[docs|specs:type]', method: RequestMethod::GET)]
class ApiDocsView extends AppView implements
    RequestHandlerInterface
{
    public function __construct(
        #[Inject('config.dirs.root')]
        private string $rootDir,

        #[Inject('option.features.api.is_enabled')]
        private ?bool $isApiEnabled = null,
    ) {
    }

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isApiEnabled) {
            throw new NotFoundException();
        }

        $type = $request->getAttribute('type');

        if ($type == 'specs') {
            $json = json_decode(file_get_contents($this->rootDir . '/docs/openapi.json'));

            $uri = $request->getUri();
            $uri = $uri->withPath('/api');

            $json->servers[0] = (object) [
                'url' => (string) $uri,
                'description' => 'Production server'
            ];

            return new JsonResponse($json);
        }

        return new ViewResponse(
            '/templates/app/api.twig',
            [
                'specs' => '/app/api-specs'
            ]
        );
    }
}
