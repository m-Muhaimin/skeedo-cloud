<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Clipdrop;

use Ai\Domain\Entities\ImageEntity;
use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\Exceptions\DomainException;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Ai\Domain\Exceptions\ModelNotSupportedException;
use Ai\Domain\Image\ImageServiceInterface;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\RequestParams;
use Ai\Domain\ValueObjects\State;
use Ai\Infrastructure\Services\CostCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Easy\Container\Attributes\Inject;
use File\Domain\Entities\ImageFileEntity;
use File\Domain\ValueObjects\Height;
use File\Domain\ValueObjects\ObjectKey;
use File\Domain\ValueObjects\Size;
use File\Domain\ValueObjects\Storage;
use File\Domain\ValueObjects\Url;
use File\Domain\ValueObjects\Width;
use File\Infrastructure\BlurhashGenerator;
use Override;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Ramsey\Uuid\Nonstandard\Uuid;
use Shared\Infrastructure\FileSystem\CdnInterface;
use Shared\Infrastructure\Services\ModelRegistry;
use Traversable;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

class ImageGeneratorService implements ImageServiceInterface
{
    private const BASE_URL = "https://clipdrop-api.co";

    private array $models = [
        // This is an internal identifier for Clipdrop model. 
        // Currently there is not any official model name.
        'clipdrop',
    ];

    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $factory,
        private StreamFactoryInterface $streamFactory,
        private CostCalculator $calc,
        private CdnInterface $cdn,
        private ModelRegistry $registry,
        private EntityManagerInterface $em,

        #[Inject('option.features.imagine.is_enabled')]
        private bool $isToolEnabled = false,

        #[Inject('option.clipdrop.api_key')]
        private ?string $apiKey = null
    ) {
        $models = [];

        if ($isToolEnabled) {
            foreach ($this->registry['directory'] as $service) {
                foreach ($service['models'] as $model) {
                    if (
                        $model['type'] === 'image'
                        && ($model['enabled'] ?? false)
                        && in_array($model['key'], $this->models)
                    ) {
                        $models[] = $model['key'];
                    }
                }
            }
        }

        $this->models = $models;
    }

    #[Override]
    public function supportsModel(Model $model): bool
    {
        return in_array($model->value, $this->models);
    }

    #[Override]
    public function getSupportedModels(): Traversable
    {
        foreach ($this->models as $model) {
            yield new Model($model);
        }
    }

    #[Override]
    public function generateImage(
        WorkspaceEntity $workspace,
        UserEntity $user,
        Model $model,
        ?array $params = null
    ): ImageEntity {
        if (!$this->supportsModel($model)) {
            throw new ModelNotSupportedException(
                self::class,
                $model
            );
        }

        if (!$params || !array_key_exists('prompt', $params)) {
            throw new DomainException('Missing parameter: prompt');
        }

        $estimate = $this->calc->estimate($model);
        if (!$workspace->hasSufficientCredit($estimate)) {
            throw new InsufficientCreditsException();
        }

        $workspace->allocate($estimate);
        $this->em->flush(); // Save the workspace with the allocated credits

        $boundary = Uuid::uuid4()->toString();

        $stream = $this->streamFactory->createStream(
            "--" . $boundary . "\r\n" .
                "Content-Disposition: form-data; name=\"prompt\"" . "\r\n" .
                "\r\n" .
                $params['prompt'] . "\r\n" .
                "--" . $boundary . "--"
        );

        $request = $this->factory
            ->createRequest('POST', self::BASE_URL . '/text-to-image/v1')
            ->withHeader('X-Api-Key', $this->apiKey)
            ->withHeader('Content-Type', "multipart/form-data; boundary=\"{$boundary}\"")
            ->withBody($stream);

        try {
            $resp = $this->client->sendRequest($request);
        } finally {
            $workspace->unallocate($estimate);
        }

        $content = $resp->getBody()->getContents();
        if ($resp->getStatusCode() !== 200) {
            $content = json_decode($content);

            throw new ApiException(
                'Failed to generate image: ' . ($content->error ?? '')
            );
        }

        $cost = $this->calc->calculate(1, $model);

        // Save image to CDN
        $name = $this->cdn->generatePath('png', $workspace, $user);
        $this->cdn->write($name, $content);

        $img = imagecreatefromstring($content);
        $width = imagesx($img);
        $height = imagesy($img);

        $file = new ImageFileEntity(
            new Storage($this->cdn->getAdapterLookupKey()),
            new ObjectKey($name),
            new Url($this->cdn->getUrl($name)),
            new Size(strlen($content)),
            new Width($width),
            new Height($height),
            BlurhashGenerator::generateBlurHash($img, $width, $height),
        );

        $entity = new ImageEntity(
            $workspace,
            $user,
            $model,
            RequestParams::fromArray($params),
            $cost
        );

        $entity->setOutputFile($file);
        $entity->setState(State::COMPLETED);

        return $entity;
    }
}
