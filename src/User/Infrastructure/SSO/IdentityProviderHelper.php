<?php

declare(strict_types=1);

namespace User\Infrastructure\SSO;

use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Http\Message\StatusCode;
use InvalidArgumentException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use User\Application\Commands\CreateUserCommand;
use User\Application\Commands\ReadUserCommand;
use User\Domain\Entities\UserEntity;
use User\Domain\Exceptions\UserNotFoundException;
use User\Domain\ValueObjects\Email;
use User\Infrastructure\SSO\Exceptions\InvalidCodeException;

class IdentityProviderHelper
{
    public function __construct(
        private Dispatcher $dispatcher,
        private ClientInterface $client,
        private UriFactoryInterface $uriFactory,
        private RequestFactoryInterface $factory,

        #[Inject('option.site.domain')]
        private string $domain = 'localhost',

        #[Inject('option.site.is_secure')]
        private bool $isSecure = true,

        #[Inject('option.site.idp_email_status')]
        private ?string $policy = null,
    ) {}

    /**
     * Generates the authentication URL for the specified identity provider.
     *
     * @param string $provider The identity provider name.
     * @param string $authUrl The authentication URL.
     * @param string $clientId The client ID.
     * @param string $scope The requested scope.
     * @return UriInterface The generated authentication URL.
     * @throws InvalidArgumentException If the input parameters are invalid.
     */
    public function generateAuthUrl(
        string $provider,
        string $authUrl,
        string $clientId,
        string $scope,
    ): UriInterface {
        $uri = $this->uriFactory->createUri($authUrl);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $this->generateRedirectUri($provider),
            'response_type' => 'code',
            'scope' => $scope,
        ]);

        return $uri->withQuery($query);
    }

    /**
     * Exchanges the authorization code for an access token from the identity 
     * provider.
     *
     * @param string $provider The name of the identity provider.
     * @param string $tokenUrl The URL to exchange the code for an access token.
     * @param string $clientId The client ID for the application.
     * @param string $clientSecret The client secret for the application.
     * @param string $code The authorization code received from the identity 
     * provider.
     * @param RequestMethod $method The HTTP request method to use for the token 
     * exchange (default: POST).
     * @return string The access token obtained from the identity provider.
     * @throws InvalidArgumentException If the input parameters are invalid.
     * @throws ClientExceptionInterface If there is an error sending the HTTP 
     * request.
     * @throws InvalidCodeException If the authorization code is invalid.
     * @throws RuntimeException If there is an error during the token exchange 
     * process.
     */
    public function exchangeCode(
        string $provider,
        string $tokenUrl,
        string $clientId,
        string $clientSecret,
        string $code,
        RequestMethod $method = RequestMethod::POST,
    ): string {
        $req = $this->factory->createRequest(
            $method->value,
            $tokenUrl
        );

        $uri = $req->getUri()->withQuery(http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->generateRedirectUri($provider),
        ]));

        $req = $req->withUri($uri);
        $res = $this->client->sendRequest(
            $req->withHeader('Accept', 'application/json')
        );

        if ($res->getStatusCode() !== StatusCode::OK->value) {
            throw new InvalidCodeException($code);
        }

        $data = json_decode($res->getBody()->getContents());

        if (!isset($data->access_token)) {
            throw new InvalidCodeException($code);
        }

        return $data->access_token;
    }

    /**
     * Find or create a user based on the provided email, first name, and last 
     * name.
     *
     * @param string $email The email of the user.
     * @param string $firstName The first name of the user.
     * @param string $lastName The last name of the user.
     * @param array $params Additional parameters for the command.
     * @param bool|null $isVerified Whether the email is verified.
     * @return UserEntity The found or created user entity.
     * @throws NoHandlerFoundException If no handler is found for the command.
     */
    public function findOrCreateUser(
        string $email,
        string $firstName,
        string $lastName,
        array $params = [],
        ?bool $isVerified = null,
    ): UserEntity {
        try {
            $cmd = new ReadUserCommand(new Email($email));
            $user = $this->dispatcher->dispatch($cmd);
            return $user;
        } catch (UserNotFoundException) {
            // Do nothing here, we'll create a new user
        }

        $cmd = new CreateUserCommand(
            $email,
            $firstName,
            $lastName
        );

        if (isset($params['ip']) && is_string($params['ip'])) {
            $cmd->setIp($params['ip']);
        }

        if (isset($params['country_code']) && is_string($params['country_code'])) {
            $cmd->setCountryCode($params['country_code']);
        }

        if (isset($params['city_name']) && is_string($params['city_name'])) {
            $cmd->setCityName($params['city_name']);
        }

        if (isset($params['ref'])) {
            $cmd->setRefCode($params['ref']);
        }

        if ($this->policy === 'verified') {
            $isVerified = true;
        } else if ($this->policy === 'auto') {
            $isVerified = $isVerified ?? false;
        } else {
            $isVerified = false;
        }

        if ($isVerified) {
            $cmd->setIsEmailVerified(true);
        }

        /** @var UserEntity $user */
        $user =  $this->dispatcher->dispatch($cmd);

        return $user;
    }

    /**
     * Generates the redirect URI for the specified identity provider.
     *
     * @param string $provider The identity provider.
     * @return string The generated redirect URI.
     */
    private function generateRedirectUri(string $provider): string
    {
        return ($this->isSecure ? 'https' : 'http')
            . '://' . $this->domain
            . '/auth/' . $provider;
    }
}
