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

class GoogleIdentityProvider implements IdentityProviderInterface
{
    private array $scopes = [
        'openid',
        'email',
        'profile',
    ];

    public function __construct(
        private IdentityProviderHelper $helper,
        private ClientInterface $client,
        private RequestFactoryInterface $factory,

        #[Inject('option.google.client_id')]
        private ?string $clientId = null,

        #[Inject('option.google.client_secret')]
        private ?string $clientSecret = null,
    ) {}

    #[Override]
    public function getName(): string
    {
        return 'Google';
    }

    #[Override]
    public function getIconSrc(): string
    {
        return 'assets/icons/google.svg';
    }

    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     */
    #[Override]
    public function getAuthUrl(): UriInterface
    {
        return $this->helper->generateAuthUrl(
            'google',
            "https://accounts.google.com/o/oauth2/v2/auth",
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
            'google',
            'https://oauth2.googleapis.com/token',
            $this->clientId,
            $this->clientSecret,
            $code
        );
        $info = $this->getUserByToken($token);

        return $this->helper->findOrCreateUser(
            $info->email,
            $info->given_name,
            $info->family_name ?? '',
            $params,
            $info->email_verified ?? null
        );
    }

    /**
     * @param string $token
     * @return object{
     *  family_name:string,
     *  sub:string,
     *  picture:string,
     *  locale:string,
     *  email_verified:boolean,
     *  given_name:string,
     *  email:string,
     *  hd:string,
     *  name:string
     * }
     * @throws InvalidArgumentException
     * @throws ClientExceptionInterface
     * @throws RuntimeException
     */
    private function getUserByToken(string $token): object
    {
        $req = $this->factory->createRequest(
            'GET',
            'https://www.googleapis.com/oauth2/v3/userinfo'
        );

        $res = $this->client->sendRequest(
            $req->withHeader('Authorization', 'Bearer ' . $token)
        );

        return json_decode($res->getBody()->getContents());
    }
}
