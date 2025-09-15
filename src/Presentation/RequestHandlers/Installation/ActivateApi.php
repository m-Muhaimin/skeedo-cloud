<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Installation;

use Doctrine\ORM\EntityManagerInterface;
use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Menv\Env;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\UnprocessableEntityException;
use Presentation\Response\EmptyResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

#[Route(path: '/activate', method: RequestMethod::POST)]
class ActivateApi extends InstallationApi implements
    RequestHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,

        #[Inject('config.dirs.root')]
        private string $rootDir
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->activate($request);
        } catch (Throwable $th) {
            throw new UnprocessableEntityException($th->getMessage());
        }

        return new EmptyResponse();
    }

    private function activate(ServerRequestInterface $request): void
    {
        $payload = $request->getParsedBody();

        file_put_contents(
            $this->rootDir . '/LICENSE',
            $payload->license
        );

        // Drop bkp tables
        $sm = $this->em->getConnection()->createSchemaManager();

        $tables = $sm->listTableNames();
        foreach ($tables as $table) {
            if (str_ends_with($table, '_bkp')) {
                $sm->dropTable($table);
            }
        }

        $menv = new Env($this->rootDir . '/.env');
        $menv
            ->set('ENVIRONMENT', 'prod')
            ->set('DEBUG', false)
            ->save();
    }
}
