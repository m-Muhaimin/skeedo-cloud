<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Options;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Option\Application\Commands\SaveOptionCommand;
use Presentation\Exceptions\UnprocessableEntityException;
use Presentation\Response\EmptyResponse;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;

#[Route(path: '/gcp', method: RequestMethod::POST)]
class SaveGCPOptionsRequestHandler extends OptionsApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher,
    ) {
    }

    /**
     * @throws RuntimeException
     * @throws UnprocessableEntityException
     * @throws NoHandlerFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);

        /** @var UploadedFileInterface */
        $file = $request->getUploadedFiles()['file'];

        $content = $file->getStream()->getContents();
        $payload = json_decode($content);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new UnprocessableEntityException('Invalid JSON');
        }

        $keys = [
            'type',
            'project_id',
            'private_key_id',
            'private_key',
            'client_email',
            'client_id',
            'auth_uri',
            'token_uri',
            'auth_provider_x509_cert_url',
            'client_x509_cert_url',
            'universe_domain'
        ];

        foreach ($keys as $key) {
            if (!isset($payload->$key)) {
                throw new UnprocessableEntityException(
                    "Invalid JSON: missing key '$key'"
                );
            }
        }

        $cmd = new SaveOptionCommand('gcp', json_encode([
            'credentials' => $payload
        ]));
        $this->dispatcher->dispatch($cmd);

        return new EmptyResponse();
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'file' => 'required|uploaded_file',
        ]);
    }
}
