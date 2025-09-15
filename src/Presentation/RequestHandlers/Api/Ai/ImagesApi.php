<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Ai;

use Ai\Application\Commands\GenerateImageCommand;
use Ai\Domain\Entities\ImageEntity;
use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Easy\Http\Message\RequestMethod;
use Easy\Http\Message\StatusCode;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\HttpException;
use Presentation\Exceptions\UnprocessableEntityException;
use Presentation\Resources\Api\ImageResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/images', method: RequestMethod::POST)]
class ImagesApi extends AiServicesApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        /** @var WorkspaceEntity */
        $ws = $request->getAttribute(WorkspaceEntity::class);

        $this->validateRequest($request);
        $payload = (object) $request->getParsedBody();

        $cmd = new GenerateImageCommand(
            $ws,
            $user,
            $payload->model,
        );

        foreach ($payload as $key => $value) {
            $cmd->param($key, $value);
        }

        foreach ($request->getUploadedFiles()['images'] ?? [] as $image) {
            $cmd->image($image);
        }

        try {
            /** @var ImageEntity */
            $image = $this->dispatcher->dispatch($cmd);
        } catch (InsufficientCreditsException $th) {
            throw new HttpException(
                'Insufficient credits',
                StatusCode::FORBIDDEN
            );
        } catch (ApiException $th) {
            throw new UnprocessableEntityException(
                $th->getMessage(),
                previous: $th
            );
        }

        return new JsonResponse(
            new ImageResource($image)
        );
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'model' => 'required|string',
        ]);
    }
}
