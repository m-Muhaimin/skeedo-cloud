<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin;

use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\Migrations\MigrationManager;

#[Route(
    path: '/update',
    method: RequestMethod::GET
)]
class UpdateView extends AbstractAdminViewRequestHandler implements
    RequestHandlerInterface
{
    public function __construct(
        private MigrationManager $migrationManager,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->migrationManager->run();

        return new ViewResponse(
            '/templates/admin/update.twig'
        );
    }
}
