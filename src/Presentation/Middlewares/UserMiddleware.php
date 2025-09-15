<?php

declare(strict_types=1);

namespace Presentation\Middlewares;

use DomainException;
use Easy\Container\Attributes\Inject;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use InvalidArgumentException;
use Presentation\Cookies\UserCookie;
use Presentation\Jwt\UserJwt;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use UnexpectedValueException;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use User\Application\Commands\ReadUserCommand;
use User\Domain\Entities\UserEntity;
use User\Domain\Exceptions\UserNotFoundException;
use User\Domain\ValueObjects\ApiKey;
use Workspace\Application\Commands\ReadWorkspaceCommand;
use Workspace\Domain\Entities\WorkspaceEntity;

class UserMiddleware implements MiddlewareInterface
{
    /**
     * @param Dispatcher $dispatcher 
     * @return void 
     */
    public function __construct(
        private Dispatcher $dispatcher,

        #[Inject('option.features.api.is_enabled')]
        private ?bool $isApiEnabled = null,

        #[Inject('option.features.admin_api.is_enabled')]
        private ?bool $isAdminApiEnabled = null,
    ) {}

    /**
     * @param ServerRequestInterface $request 
     * @param RequestHandlerInterface $handler 
     * @return ResponseInterface 
     * @throws InvalidArgumentException 
     * @throws UnexpectedValueException 
     * @throws NoHandlerFoundException 
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $user = $this->getUser($request);
        $apiUser = $this->getApiUser($request);
        $workspace = $this->getWorkspace($request, $user);

        $path = $request->getUri()->getPath();

        if ($apiUser) {
            if (str_starts_with($path, '/admin/api/')) {
                if ($this->isAdminApiEnabled) {
                    $user = $apiUser;
                }
            } elseif (str_starts_with($path, '/api/')) {
                if ($this->isApiEnabled) {
                    $user = $apiUser;
                }
            }
        }

        if ($user) {
            $request = $request->withAttribute(UserEntity::class, $user);
        }

        if ($workspace) {
            $request = $request->withAttribute(
                WorkspaceEntity::class,
                $workspace
            );
        }

        return $handler->handle($request);
    }

    private function getApiUser(ServerRequestInterface $request): ?UserEntity
    {
        $apiKey = $this->getTokenFromApiHeader($request);

        if (!$apiKey) {
            return null;
        }

        try {
            $cmd = new ReadUserCommand(new ApiKey($apiKey));

            /** @var UserEntity $user */
            $user = $this->dispatcher->dispatch($cmd);
        } catch (\Throwable $th) {
            return null;
        }

        return $user;
    }

    /**
     * @param ServerRequestInterface $request 
     * @return null|UserEntity 
     * @throws InvalidArgumentException 
     * @throws UnexpectedValueException 
     * @throws NoHandlerFoundException 
     */
    private function getUser(ServerRequestInterface $request): ?UserEntity
    {
        $token =
            $this->getTokenFromAuthorizationHeader($request)
            ?? $this->getTokenFromQueryParam($request)
            ?? $this->getTokenFromCookie($request);

        if (!$token) {
            return null;
        }

        try {
            $jwt = UserJwt::createFromJwtString($token);
        } catch (
            SignatureInvalidException
            | BeforeValidException
            | ExpiredException
            | DomainException
            | UserNotFoundException) {
            return null;
        }

        $user = $jwt->user;
        $user->touch();
        return $user;
    }

    /**
     * @param ServerRequestInterface $request 
     * @return null|string 
     */
    private function getTokenFromAuthorizationHeader(
        ServerRequestInterface $request
    ): ?string {
        $header = $request->getHeaderLine('Authorization');

        if (!$header) {
            return null;
        }

        $token = null;

        // Check if header has Bearer token
        if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
            $token = $matches[1];
        }

        return $token;
    }

    private function getTokenFromApiHeader(
        ServerRequestInterface $request
    ): ?string {
        $header = $request->getHeaderLine('X-Api-Key');

        if (!$header) {
            return null;
        }

        return $header;
    }

    /**
     * @param ServerRequestInterface $request 
     * @return null|string 
     */
    private function getTokenFromQueryParam(
        ServerRequestInterface $request
    ): ?string {
        $params = $request->getQueryParams();

        return $params['jwt'] ?? null;
    }

    /**
     * @param ServerRequestInterface $request 
     * @return null|string 
     */
    private function getTokenFromCookie(
        ServerRequestInterface $request
    ): ?string {
        $path = $request->getUri()->getPath();

        if (
            str_starts_with($path, '/admin/api/') === false
            && str_starts_with($path, '/api/') === false
        ) {
            $cookie = UserCookie::createFromRequest($request);
            return $cookie ? $cookie->getValue() : null;
        }

        return null;
    }

    private function getWorkspace(
        ServerRequestInterface $req,
        ?UserEntity $user = null
    ): ?WorkspaceEntity {
        $header = $req->getHeaderLine('X-Workspace-Id');

        if ($header) {
            try {
                $cmd = new ReadWorkspaceCommand($header);
                return $this->dispatcher->dispatch($cmd);
            } catch (\Throwable $th) {
                // Do nothing here 
            }
        }

        if ($user) {
            return $user->getCurrentWorkspace();
        }

        return null;
    }
}
