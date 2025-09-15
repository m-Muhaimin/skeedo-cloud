<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Assistants;

use Assistant\Application\Commands\CreateAssistantCommand;
use Assistant\Domain\Entities\AssistantEntity;
use Assistant\Domain\ValueObjects\Status;
use Easy\Http\Message\RequestMethod;
use Easy\Http\Message\StatusCode;
use Easy\Router\Attributes\Route;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\FilesystemException;
use League\Flysystem\Visibility;
use Presentation\Resources\Admin\Api\AssistantResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\ValidationException;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Nonstandard\Uuid;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use Shared\Infrastructure\FileSystem\CdnInterface;

#[Route(path: '/', method: RequestMethod::POST)]
class CreateAssistantRequestHandler extends AssistantsApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher,
        private CdnInterface $cdn
    ) {}

    /**
     * @throws ValidationException
     * @throws UnableToWriteFile
     * @throws FilesystemException
     * @throws NoHandlerFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);
        $payload = (object) $request->getParsedBody();

        $cmd = new CreateAssistantCommand(
            name: $payload->name
        );

        if (property_exists($payload, 'expertise')) {
            $cmd->setExpertise($payload->expertise ?: null);
        }

        if (property_exists($payload, 'description')) {
            $cmd->setDescription($payload->description ?: null);
        }

        if (property_exists($payload, 'instructions')) {
            $cmd->setInstructions($payload->instructions ?: null);
        }

        if (property_exists($payload, 'avatar')) {
            $url = $this->getAvatarUrl($payload->avatar);
            $cmd->setAvatar($url);
        }

        if (property_exists($payload, 'status')) {
            $cmd->setStatus((int) $payload->status);
        }

        if (property_exists($payload, 'model')) {
            $cmd->setModel($payload->model ?: null);
        }

        /** @var AssistantEntity */
        $assistant = $this->dispatcher->dispatch($cmd);

        return new JsonResponse(
            new AssistantResource($assistant),
            StatusCode::CREATED
        );
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'name' => 'required|string',
            'expertise' => 'string',
            'description' => 'string',
            'instructions' => 'string',
            'model' => 'string',
            'status' => 'integer|in:' . implode(",", array_map(
                fn(Status $type) => $type->value,
                Status::cases()
            ))
        ]);
    }

    private function getAvatarUrl(string $avatar): ?string
    {
        // check if its a valid url
        if (filter_var($avatar, FILTER_VALIDATE_URL)) {
            return $avatar;
        }

        // Decode the Base64 string
        $fileData = base64_decode($avatar);

        if (!$fileData) {
            return null;
        }

        // Determine the file type from the binary data
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $fileData);
        finfo_close($finfo);

        $mimeTypeToExtension = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/webp' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
        ];

        if (!array_key_exists($mimeType, $mimeTypeToExtension)) {
            return null;
        }

        $ext = $mimeTypeToExtension[$mimeType];

        $name = Uuid::uuid4()->toString() . '.' . $ext;
        $this->cdn->write("/" . $name, $fileData, [
            // Always make it public even though the pre-signed secure URLs option is enabled.
            'visibility' => Visibility::PUBLIC
        ]);

        $url = $this->cdn->getUrl($name);

        // Remove query string from URL if present
        return strstr($url, '?', true) ?: $url;
    }
}
