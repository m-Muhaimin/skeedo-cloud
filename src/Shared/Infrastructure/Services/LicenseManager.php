<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Services;

use Easy\Container\Attributes\Inject;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Option\Application\Commands\SaveOptionCommand;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Ramsey\Uuid\Nonstandard\Uuid;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Throwable;

class LicenseManager
{
    private const JWT_ALGORITHM = 'HS256';
    private const TOKEN_EXPIRY = 86400;

    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private Dispatcher $dispatcher,

        #[Inject('config.dirs.root')]
        private string $rootDir,

        #[Inject('license')]
        private ?string $license = null,

        #[Inject('option.token')]
        private ?string $token = null,
    ) {}

    public function verify(): void
    {
        if ($this->isTokenValid()) {
            return;
        }

        $this->refresh();
    }

    public function refresh(): void
    {
        $licenseKey = $this->getLicense();
        $this->validateLicense($licenseKey);
        $this->saveToken($licenseKey);
    }

    public function activate(string $licenseKey): void
    {
        $licenseKey = trim($licenseKey);
        $this->validateLicense($licenseKey);
        $this->saveLicense($licenseKey);
        $this->saveToken($licenseKey);
    }

    private function isTokenValid(): bool
    {
        if ($this->token) {
            try {
                $key = new Key(env('JWT_TOKEN'), self::JWT_ALGORITHM);
                JWT::decode($this->token, $key);

                return true;
            } catch (Throwable $th) {
                // Token is invalid, proceed to remote validation
            }
        }

        return false;
    }

    private function getLicense(): string
    {
        if ($this->license) {
            return trim($this->license);
        }

        throw new Exception('License key not found.');
    }

    private function validateLicense(string $license): void
    {
        $license = trim($license);
        $req = $this->requestFactory
            ->createRequest('GET', 'https://api.aikeedo.com/licenses/' . $license);

        $resp = $this->client->sendRequest($req);

        if (
            $resp->getStatusCode() < 200
            || $resp->getStatusCode() >= 300
        ) {
            $content = $resp->getBody()->getContents();
            $data = json_decode($content);

            throw new Exception($data->message ?? 'Invalid license key.');
        }
    }

    private function saveLicense(string $license): void
    {
        file_put_contents($this->rootDir . '/LICENSE', $license);
    }

    private function saveToken(string $license): void
    {
        $payload = [
            'sub' => $license,
            'iat' => time(),
            'jti' => Uuid::uuid4()->toString(),
            'exp' => time() + self::TOKEN_EXPIRY,
        ];

        $jwt = JWT::encode($payload, env('JWT_TOKEN'), self::JWT_ALGORITHM);

        $cmd = new SaveOptionCommand('token', $jwt);
        $this->dispatcher->dispatch($cmd);
    }
}
