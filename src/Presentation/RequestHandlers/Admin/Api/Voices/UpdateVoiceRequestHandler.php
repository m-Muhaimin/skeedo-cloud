<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Voices;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\NotFoundException;
use Presentation\Resources\Admin\Api\VoiceResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Voice\Application\Commands\UpdateVoiceCommand;
use Voice\Domain\Entities\VoiceEntity;
use Voice\Domain\Exceptions\VoiceNotFoundException;
use Voice\Domain\ValueObjects\Accent;
use Voice\Domain\ValueObjects\Age;
use Voice\Domain\ValueObjects\Gender;
use Voice\Domain\ValueObjects\Status;

#[Route(path: '/[uuid:id]', method: RequestMethod::PATCH)]
#[Route(path: '/[uuid:id]', method: RequestMethod::POST)]
class UpdateVoiceRequestHandler extends VoiceApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);
        $payload = (object) $request->getParsedBody();

        $cmd = new UpdateVoiceCommand($request->getAttribute('id'));

        if (property_exists($payload, 'status')) {
            $cmd->setStatus((int) $payload->status);
        }

        if (property_exists($payload, 'name')) {
            $cmd->setName($payload->name);
        }

        if (property_exists($payload, 'sample_url')) {
            $cmd->setSampleUrl($payload->sample_url ?: null);
        }

        if (property_exists($payload, 'tones')) {
            $cmd->setTones(...$payload->tones);
        }

        if (property_exists($payload, 'use_cases')) {
            $cmd->setUseCases(...$payload->use_cases);
        }

        if (property_exists($payload, 'gender')) {
            $cmd->setGender($payload->gender ?: null);
        }

        if (property_exists($payload, 'accent')) {
            $cmd->setAccent($payload->accent ?: null);
        }

        if (property_exists($payload, 'age')) {
            $cmd->setAge($payload->age ?: null);
        }

        if (property_exists($payload, 'before')) {
            $cmd->setBefore($payload->before ?: null);
        }

        if (property_exists($payload, 'after')) {
            $cmd->setAfter($payload->after ?: null);
        }

        try {
            /** @var VoiceEntity */
            $voice = $this->dispatcher->dispatch($cmd);
        } catch (VoiceNotFoundException $th) {
            throw new NotFoundException(
                param: 'id',
                previous: $th
            );
        }

        return new JsonResponse(new VoiceResource($voice));
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'status' => 'integer|in:' . implode(",", array_map(
                fn(Status $type) => $type->value,
                Status::cases()
            )),
            'name' => 'string',
            'sample_url' => 'nullable|string',
            'tones' => 'nullable|array',
            'tones.*' => 'string',
            'use_cases' => 'nullable|array',
            'use_cases.*' => 'string',
            'gender' => 'string|in:' . implode(",", array_map(
                fn(Gender $type) => $type->value,
                Gender::cases()
            )),
            'accent' => 'string|in:' . implode(",", array_map(
                fn(Accent $type) => $type->value,
                Accent::cases()
            )),
            'age' => 'string|in:' . implode(",", array_map(
                fn(Age $type) => $type->value,
                Age::cases()
            )),
            'before' => 'nullable|uuid',
            'after' => 'nullable|uuid'
        ]);
    }
}
