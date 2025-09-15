<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Assistants;

use Ai\Infrastructure\Exceptions\UnreadableDocumentException;
use Assistant\Application\Commands\CreateDataUnitCommand;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Override;
use Presentation\Validation\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Dataset\Domain\Entities\AbstractDataUnitEntity;
use Dataset\Domain\Entities\FileUnitEntity;
use Dataset\Domain\Entities\LinkUnitEntity;
use Easy\Http\Message\StatusCode;
use Presentation\Exceptions\HttpException;
use Presentation\Resources\Admin\Api\FileUnitResource;
use Presentation\Resources\Admin\Api\LinkUnitResource;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\UploadedFileInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Route(path: '/[uuid:id]/dataset', method: RequestMethod::POST)]
class CreateDataUnitApi extends AssistantsApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);

        /** @var object{url?:string} */
        $payload = (object)$request->getParsedBody();

        $id = $request->getAttribute('id');
        $cmd = new CreateDataUnitCommand($id);

        /** @var UploadedFileInterface */
        $file = $request->getUploadedFiles()['file'] ?? null;

        if ($file) {
            $cmd->file = $file;
        }

        if (property_exists($payload, 'url')) {
            $cmd->setUrl($payload->url);
        }

        try {
            /** @var AbstractDataUnitEntity */
            $res = $this->dispatcher->dispatch($cmd);
        } catch (UnreadableDocumentException $th) {
            throw new HttpException(
                message: $th->getMessage(),
                statusCode: StatusCode::UNPROCESSABLE_ENTITY,
                previous: $th
            );
        }

        match (true) {
            $res instanceof FileUnitEntity => $resource = new FileUnitResource($res),
            $res instanceof LinkUnitEntity => $resource = new LinkUnitResource($res),
            default => $resource = [],
        };

        return new JsonResponse($resource);
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'file' => 'sometimes|uploaded_file',
            'url' => 'sometimes|url',
        ]);
    }
}
