<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Options;

use Easy\Http\Message\RequestMethod;
use Presentation\RequestHandlers\Admin\Api\AdminApi;
use Easy\Router\Attributes\Route;
use Exception;
use Presentation\Exceptions\UnprocessableEntityException;
use Presentation\Response\EmptyResponse;
use Presentation\Validation\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\Services\LicenseManager;

#[Route('/license', method: RequestMethod::POST)]
class LicenseApi extends AdminApi implements RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private LicenseManager $lm,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);
        $payload = (object) $request->getParsedBody();
        $key = $payload->key;

        try {
            $this->lm->activate($key);
        } catch (Exception $e) {
            throw new UnprocessableEntityException(
                $e->getMessage(),
                previous: $e
            );
        }

        return new EmptyResponse();
    }

    private function validateRequest(ServerRequestInterface $request): void
    {
        $this->validator->validateRequest($request, [
            'key' => 'required|string|max:255'
        ]);
    }
}
