<?php

declare(strict_types=1);

namespace User\Application\Commands;

use Affiliate\Domain\ValueObjects\Code;
use Shared\Domain\ValueObjects\CityName;
use Shared\Domain\ValueObjects\CountryCode;
use Shared\Domain\ValueObjects\Ip;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use User\Application\CommandHandlers\CreateUserCommandHandler;
use User\Domain\ValueObjects\Email;
use User\Domain\ValueObjects\FirstName;
use User\Domain\ValueObjects\IsEmailVerified;
use User\Domain\ValueObjects\Language;
use User\Domain\ValueObjects\LastName;
use User\Domain\ValueObjects\Password;
use User\Domain\ValueObjects\PhoneNumber;
use User\Domain\ValueObjects\Role;
use User\Domain\ValueObjects\Status;
use User\Domain\ValueObjects\WorkspaceCap;

#[Handler(CreateUserCommandHandler::class)]
class CreateUserCommand
{
    public Email $email;
    public FirstName $firstName;
    public LastName $lastName;
    public ?Password $password = null;
    public ?PhoneNumber $phoneNumber = null;
    public ?Language $language = null;
    public ?Ip $ip = null;
    public ?CountryCode $countryCode = null;
    public ?CityName $cityName = null;

    public ?Role $role = null;
    public ?Status $status = null;
    public ?Code $refCode = null;
    public ?WorkspaceCap $workspaceCap = null;
    public ?IsEmailVerified $isEmailVerified = null;

    public function __construct(
        string $email,
        string $firstName,
        string $lastName
    ) {
        $this->email = new Email($email);
        $this->firstName = new FirstName($firstName);
        $this->lastName = new LastName($lastName);
    }

    public function setPassword(?string $password): self
    {
        $this->password = new Password($password);
        return $this;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = new PhoneNumber($phoneNumber);
        return $this;
    }

    public function setLanguage(string $language): self
    {
        $this->language = new Language($language);
        return $this;
    }

    public function setIp(?string $ip): self
    {
        $this->ip = new Ip($ip);
        return $this;
    }

    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = CountryCode::from($countryCode);
        return $this;
    }

    public function setCityName(?string $cityName): self
    {
        $this->cityName = new CityName($cityName);
        return $this;
    }

    public function setRole(int $role): self
    {
        $this->role = Role::from($role);
        return $this;
    }

    public function setStatus(int $status): self
    {
        $this->status = Status::from($status);
        return $this;
    }

    public function setRefCode(?string $refCode): self
    {
        $this->refCode = new Code($refCode);
        return $this;
    }

    public function setWorkspaceCap(?int $value): self
    {
        $this->workspaceCap = new WorkspaceCap($value);
        return $this;
    }

    public function setIsEmailVerified(bool $isEmailVerified): self
    {
        $this->isEmailVerified = new IsEmailVerified($isEmailVerified);
        return $this;
    }
}
