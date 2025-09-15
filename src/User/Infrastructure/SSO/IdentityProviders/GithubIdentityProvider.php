<?php

declare(strict_types=1);

namespace User\Infrastructure\SSO\IdentityProviders;

use Easy\Container\Attributes\Inject;
use InvalidArgumentException;
use Override;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use User\Domain\Entities\UserEntity;
use User\Infrastructure\SSO\IdentityProviderInterface;
use User\Infrastructure\SSO\IdentityProviderHelper;

class GithubIdentityProvider implements IdentityProviderInterface
{
    private array $scopes = [
        'user:email',
    ];

    public function __construct(
        private IdentityProviderHelper $helper,
        private ClientInterface $client,
        private RequestFactoryInterface $factory,

        #[Inject('option.github.client_id')]
        private ?string $clientId = null,

        #[Inject('option.github.client_secret')]
        private ?string $clientSecret = null,
    ) {}

    #[Override]
    public function getName(): string
    {
        return 'Github';
    }

    #[Override]
    public function getIconSrc(): string
    {
        return 'assets/icons/github.svg';
    }

    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     */
    #[Override]
    public function getAuthUrl(): UriInterface
    {
        return $this->helper->generateAuthUrl(
            'github',
            "https://github.com/login/oauth/authorize",
            $this->clientId,
            implode(' ', $this->scopes),
        );
    }

    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     * @throws ClientExceptionInterface
     * @throws RuntimeException
     * @throws NoHandlerFoundException
     */
    #[Override]
    public function getUser(string $code, array $params = []): UserEntity
    {
        $token = $this->helper->exchangeCode(
            'github',
            'https://github.com/login/oauth/access_token',
            $this->clientId,
            $this->clientSecret,
            $code
        );

        $info = $this->getUserByToken($token);

        $fullName = explode(' ', $info->name);
        $firstName = reset($fullName);
        $lastName = implode(' ', array_slice($fullName, 1));

        if (trim($lastName) === '') {
            $lastName = $info->login;
        }

        return $this->helper->findOrCreateUser(
            $info->email,
            $firstName,
            $lastName,
            $params,
            $info->verified
        );
    }

    /**
     * @param string $token
     * @return object{
     *  email: string,
     *  name: string,
     *  login: string,
     *  verified: boolean
     * }
     * @throws InvalidArgumentException
     * @throws ClientExceptionInterface
     * @throws RuntimeException
     */
    private function getUserByToken(string $token): object
    {
        // Get user info
        $req = $this->factory
            ->createRequest('GET', 'https://api.github.com/user')
            ->withHeader('Authorization', 'token ' . $token)
            ->withHeader('Accept', 'application/vnd.github.v3+json');

        $res = $this->client->sendRequest($req);
        $info = json_decode($res->getBody()->getContents());

        // Get user emails
        $req = $this->factory
            ->createRequest('GET', 'https://api.github.com/user/emails')
            ->withHeader('Authorization', 'token ' . $token)
            ->withHeader('Accept', 'application/vnd.github.v3+json');

        $res = $this->client->sendRequest($req);
        $emails = json_decode($res->getBody()->getContents());

        // Find primary email
        $emails = array_filter($emails, fn($email) => $email->primary);

        // Return user info
        $email = reset($emails);
        return (object)[
            'email' => $email->email,
            'verified' => $email->verified,
            'name' => $info->name,
            'login' => $info->login,
        ];
    }
}
