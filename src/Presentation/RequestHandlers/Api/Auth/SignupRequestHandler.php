<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Auth;

use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Http\Message\StatusCode;
use Easy\Router\Attributes\Middleware;
use Easy\Router\Attributes\Route;
use Presentation\Response\Api\Auth\AuthResponse;
use Presentation\Exceptions\HttpException;
use Presentation\Exceptions\NotFoundException;
use Presentation\Middlewares\CaptchaMiddleware;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Application\Commands\CreateUserCommand;
use User\Domain\Entities\UserEntity;
use User\Domain\Exceptions\EmailTakenException;

#[Middleware(CaptchaMiddleware::class)]
#[Route(path: '/signup', method: RequestMethod::POST)]
class SignupRequestHandler extends AuthApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher,

        #[Inject('option.site.user_accounts_enabled')]
        private bool $userAccountsEnabled = true,

        #[Inject('option.site.user_signup_enabled')]
        private bool $userSignupEnabled = true,

        #[Inject('option.site.phone_requirement_policy')]
        private string $phoneRequirementPolicy = 'none',
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);
        $payload = (object) $request->getParsedBody();

        $cmd = new CreateUserCommand(
            $payload->email,
            $payload->first_name,
            $payload->last_name
        );

        $cmd->setPassword($payload->password);

        if (
            property_exists($payload, 'phone_number')
            && $payload->phone_number !== ''
        ) {
            $cmd->setPhoneNumber($payload->phone_number);
        }

        if (property_exists($payload, 'locale')) {
            $cmd->setLanguage($payload->locale);
        }

        if (property_exists($payload, 'ip')) {
            $cmd->setIp($payload->ip);
        }

        if (property_exists($payload, 'country_code')) {
            $cmd->setCountryCode($payload->country_code);
        }

        if (property_exists($payload, 'city_name')) {
            $cmd->setCityName($payload->city_name);
        }

        if (property_exists($payload, 'ref')) {
            $cmd->setRefCode($payload->ref);
        }

        try {
            /** @var UserEntity $user */
            $user = $this->dispatcher->dispatch($cmd);
        } catch (EmailTakenException $th) {
            throw new HttpException(
                statusCode: StatusCode::CONFLICT,
                param: 'email',
                previous: $th
            );
        }

        return new AuthResponse($user);
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        if (!$this->userAccountsEnabled || !$this->userSignupEnabled) {
            throw new NotFoundException();
        }

        $rules = [
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email',
            'password' => 'required|string',
            'locale' => 'string|max:5',
            'ip' => 'ip',
            'country_code' => 'string|max:2',
            'city_name' => 'string|max:150',
            'ref' => 'string'
        ];

        if ($this->phoneRequirementPolicy === 'relaxed') {
            $rules['phone_number'] = 'string|max:30';
        } elseif ($this->phoneRequirementPolicy === 'strict') {
            $rules['phone_number'] = 'required|string|max:30';
        }

        $this->validator->validateRequest($req, $rules);
    }
}
