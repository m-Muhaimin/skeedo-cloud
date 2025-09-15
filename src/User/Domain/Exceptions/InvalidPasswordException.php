<?php

declare(strict_types=1);

namespace User\Domain\Exceptions;

use Exception;
use Throwable;
use User\Domain\Entities\UserEntity;
use User\Domain\ValueObjects\Password;

class InvalidPasswordException extends Exception
{
    public const TYPE_INCORRECT = 1;
    public const TYPE_SAME_AS_OLD = 2;

    public function __construct(
        public readonly UserEntity $user,
        public readonly Password $password,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            $this->getMessageByCode($code),
            $code,
            $previous
        );
    }

    private function getMessageByCode(int $code): string
    {
        switch ($code) {
            case self::TYPE_INCORRECT:
                return sprintf(
                    "Password is incorrect for user <%s>!",
                    $this->user->getEmail()->value
                );
            case self::TYPE_SAME_AS_OLD:
                return sprintf(
                    "New password is the same as old for user <%s>!",
                    $this->user->getEmail()->value
                );
            default:
                return "Password is invalid!";
        }
    }
}
