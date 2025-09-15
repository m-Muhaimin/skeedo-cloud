<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Installation;

use Doctrine\DBAL\DriverManager;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\UnprocessableEntityException;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

#[Route(path: '/database', method: RequestMethod::POST)]
class DatabaseApi extends InstallationApi implements
    RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            return $this->checkCredentials($request);
        } catch (Throwable $th) {
            throw new UnprocessableEntityException($th->getMessage());
        }
    }

    private function checkCredentials(
        ServerRequestInterface $request
    ): ResponseInterface {
        $payload = $request->getParsedBody();

        $conn = DriverManager::getConnection([
            'dbname' => $payload->name,
            'user' => $payload->user,
            'password' =>  $payload->password,
            'host' => $payload->host,
            'port' => $payload->port,
            'driver' => $payload->driver,
        ]);

        $sm = $conn->createSchemaManager();
        $tables = $sm->listTableNames();

        $migrate = false;
        $user = null;

        if (in_array('user', $tables)) {
            $firstUser = $conn->fetchAssociative('SELECT * FROM user WHERE role = 1 LIMIT 1');
            // Check if user table exists and has data

            if ($firstUser !== false) {
                $migrate = true;

                $user = [
                    'email' => $firstUser['email'],
                    'first_name' => $firstUser['first_name'],
                    'last_name' => $firstUser['last_name'],
                ];
            }
        }


        return new JsonResponse(
            [
                'migrate' => $migrate,
                'has_data' => count($tables) > 0,
                'user' => $user,
            ]
        );
    }
}
