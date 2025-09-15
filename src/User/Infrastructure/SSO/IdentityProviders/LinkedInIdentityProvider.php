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

class LinkedInIdentityProvider implements IdentityProviderInterface
{
    private array $scopes = [
        'openid',
        'profile',
        'email',
    ];

    public function __construct(
        private IdentityProviderHelper $helper,
        private ClientInterface $client,
        private RequestFactoryInterface $factory,

        #[Inject('option.linkedin.client_id')]
        private ?string $clientId = null,

        #[Inject('option.linkedin.client_secret')]
        private ?string $clientSecret = null,
    ) {}

    #[Override]
    public function getName(): string
    {
        return 'LinkedIn';
    }

    #[Override]
    public function getIconSrc(): string
    {
        return 'assets/icons/linkedin.svg';
    }

    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     */
    #[Override]
    public function getAuthUrl(): UriInterface
    {
        return $this->helper->generateAuthUrl(
            'linkedin',
            "https://www.linkedin.com/oauth/v2/authorization",
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
            'linkedin',
            'https://www.linkedin.com/oauth/v2/accessToken',
            $this->clientId,
            $this->clientSecret,
            $code
        );
        $info = $this->getUserByToken($token);

        return $this->helper->findOrCreateUser(
            $info->email,
            $info->given_name,
            $info->family_name,
            $params,
            $info->email_verified
        );
    }

    /**
     * @param string $token
     * @return object{
     *  sub: string,
     *  name: string,
     *  given_name: string,
     *  family_name: string,
     *  picture: string,
     *  locale: string,
     *  email: string,
     *  email_verified: boolean
     * }
     * @throws InvalidArgumentException
     * @throws ClientExceptionInterface
     * @throws RuntimeException
     */
    private function getUserByToken(string $token): object
    {
        $req = $this->factory->createRequest(
            'GET',
            'https://api.linkedin.com/v2/userinfo'
        );

        $res = $this->client->sendRequest(
            $req->withHeader('Authorization', 'Bearer ' . $token)
        );

        return json_decode($res->getBody()->getContents());
    }
}
