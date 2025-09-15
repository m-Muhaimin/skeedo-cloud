<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin;

use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Override;
use Presentation\Response\JsonResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[Route(path: '/api-[docs|specs:type]', method: RequestMethod::GET)]
class ApiDocsView extends AbstractAdminViewRequestHandler implements
    RequestHandlerInterface
{
    public function __construct(
        #[Inject('config.dirs.root')]
        private string $rootDir
    ) {
    }

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $type = $request->getAttribute('type');

        if ($type == 'specs') {
            $json = json_decode(file_get_contents($this->rootDir . '/docs/admin-openapi.json'));

            $uri = $request->getUri();
            $uri = $uri->withPath('/admin/api');

            $json->servers[0] = (object) [
                'url' => (string) $uri,
                'description' => 'Production server'
            ];

            return new JsonResponse($json);
        }

        return new ViewResponse(
            '/templates/app/api.twig',
            [
                'specs' => '/admin/api-specs'
            ]
        );
    }
}
