<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Installation;

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

#[Route(path: '/env', method: RequestMethod::POST)]
class EnvApi extends InstallationApi implements
    RequestHandlerInterface
{
    public function __construct(
        #[Inject('config.dirs.root')]
        private string $rootDir
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->setupEnvironment($request->getParsedBody());
        } catch (Throwable $th) {
            $path = $this->rootDir . '/.env';

            if (file_exists($path)) {
                unlink($path);
            }

            throw new UnprocessableEntityException($th->getMessage());
        }

        return new EmptyResponse();
    }

    private function setupEnvironment(object $payload)
    {
        // Save env file
        $path = $this->rootDir . '/.env';
        if (!file_exists($path)) {
            // Create a new .env file
            copy($this->rootDir . '/.env.example', $path);
        }

        $menv = new Env($path);
        $menv
            ->set('ENVIRONMENT', 'install') // Will be updated to 'production' after installation
            ->set('DEBUG', true) // Will be updated to false after installation
            ->set('JWT_TOKEN', bin2hex(random_bytes(16)))
            ->set('DB_DRIVER', 'mysql')
            ->set('DB_USER', $payload->db->user)
            ->set('DB_PASSWORD', $payload->db->password)
            ->set('DB_HOST', $payload->db->host)
            ->set('DB_PORT', $payload->db->port)
            ->set('DB_NAME', $payload->db->name)
            ->save();
    }
}
