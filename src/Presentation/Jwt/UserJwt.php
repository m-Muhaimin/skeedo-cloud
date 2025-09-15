<?php

declare(strict_types=1);

namespace Presentation\Jwt;

use Application;
use DateTime;
use DateTimeInterface;
use DomainException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use Firebase\JWT\SignatureInvalidException;
use Ramsey\Uuid\Uuid;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use UnexpectedValueException;
use User\Application\Commands\ReadUserCommand;
use User\Domain\Entities\UserEntity;
use User\Domain\ValueObjects\Role;

class UserJwt
{
    /**
     * @param string $jwt
     * @return UserJwt
     * @throws InvalidArgumentException
     * @throws DomainException
     * @throws UnexpectedValueException
     * @throws SignatureInvalidException
     * @throws BeforeValidException
     * @throws ExpiredException
     * @throws NoHandlerFoundException
     * @throws UserNotFoundException
     */
    public static function createFromJwtString(string $jwt): UserJwt
    {
        $key = new Key(env('JWT_TOKEN'), 'HS256');
        $payload = JWT::decode($jwt, $key);

        $exp = null;
        if (isset($payload->exp)) {
            $exp = new DateTime('@' . $payload->exp);
        }

        $dispatcher = Application::make(Dispatcher::class);

        // uuid is for backwards compatibility
        $cmd = new ReadUserCommand($payload->sub ?? $payload->uuid);

        /** @var UserEntity $user */
        $user = $dispatcher->dispatch($cmd);

        return new UserJwt($user, $exp);
    }

    /**
     * @param UserEntity $user 
     * @param null|DateTimeInterface $expiresAt 
     * @return void 
     */
    public function __construct(
        public readonly UserEntity $user,
        private ?DateTimeInterface $expiresAt = null
    ) {}

    /**
     * @return string 
     * @throws DomainException 
     */
    public function getJwtString(): string
    {
        $u = $this->user;

        $payload = [
            'sub' => (string) $u->getId()->getValue(),
            'iat' => time(),
            'jti' => Uuid::uuid4()->toString(),
            'is_admin' => $u->getRole() === Role::ADMIN,
            'email' => $u->getEmail()->value,
            'first_name' => $u->getFirstName()->value,
            'last_name' => $u->getLastName()->value,
            'avatar' => "https://www.gravatar.com/avatar/"
                . md5($u->getEmail()->value) . "?d=blank",
        ];

        if ($this->expiresAt) {
            $payload['exp'] = $this->expiresAt->getTimestamp();
        }

        return JWT::encode($payload, env('JWT_TOKEN'), 'HS256');
    }
}
