<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Luma;

use Ai\Domain\Entities\ImageEntity;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\Image\ImageServiceInterface;
use Ai\Domain\ValueObjects\RequestParams;
use Ai\Domain\ValueObjects\State;
use Ai\Infrastructure\Services\CostCalculator;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Easy\Container\Attributes\Inject;
use File\Domain\Entities\ImageFileEntity;
use File\Domain\ValueObjects\Height;
use File\Domain\ValueObjects\ObjectKey;
use File\Domain\ValueObjects\Size;
use File\Domain\ValueObjects\Storage;
use File\Domain\ValueObjects\Url;
use File\Domain\ValueObjects\Width;
use File\Infrastructure\BlurhashGenerator;
use League\Flysystem\Visibility;
use Override;
use Shared\Infrastructure\FileSystem\CdnInterface;
use Traversable;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

class ImageService implements ImageServiceInterface
{
    private array $models = [
        'luma/photon-1',
        'luma/photon-flash-1'
    ];

    public function __construct(
        private Client $client,
        private CostCalculator $calc,
        private CdnInterface $cdn,
        private EntityManagerInterface $em,

        #[Inject('option.features.imagine.is_enabled')]
        private bool $isToolEnabled = false,
    ) {
        if (!$this->isToolEnabled) {
            $this->models = [];
        }
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

    public function generateImage(
        WorkspaceEntity $workspace,
        UserEntity $user,
        Model $model,
        ?array $params = null
    ): ImageEntity {
        if (!$params || !array_key_exists('prompt', $params)) {
            throw new DomainException('Missing parameter: prompt');
        }

        $estimate = $this->calc->estimate($model);
        if (!$workspace->hasSufficientCredit($estimate)) {
            throw new InsufficientCreditsException();
        }

        $workspace->allocate($estimate);
        $this->em->flush(); // Save the workspace with the allocated credits

        $body = [
            'generation_type' => 'image',
            'prompt' => $params['prompt'],
            'sync' => true,
            'aspect_ratio' => '16:9', // default
            'model' => preg_replace('/^luma\//', '', $model->value),
        ];

        // aspect ratio
        $allowed = ['1:1', '16:9', '9:16', '4:3', '3:4', '21:9', '9:21'];
        if (
            isset($params['aspect_ratio'])
            && in_array($params['aspect_ratio'], $allowed)
        ) {
            $body['aspect_ratio'] = $params['aspect_ratio'];
        }

        if (isset($params['images'])) {
            $image = $params['images'][0];
            $filename = $image->getClientFilename();
            $extension = pathinfo($filename, PATHINFO_EXTENSION);

            $key = $this->cdn->generatePath($extension, $workspace, $user);
            $this->cdn->write($key, $image->getStream()->getContents(), [
                // Always make it public even though the pre-signed secure 
                // URLs option is enabled.
                'visibility' => Visibility::PUBLIC
            ]);

            $url = $this->cdn->getUrl($key);
            $body['modify_image_ref'] = [
                'url' => $url,
                'weight' => 1
            ];
        }

        try {
            $resp = $this->client->sendRequest('POST', '/dream-machine/v1/generations/image', $body);
        } finally {
            $workspace->unallocate($estimate);
        }

        $contents = $resp->getBody()->getContents();
        $contents = json_decode($contents);

        $url = $contents->assets->image;

        $resp = $this->client->sendRequest('GET', $url);
        $content = $resp->getBody()->getContents();

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
            RequestParams::fromArray((array) $contents->request),
            $cost
        );

        $entity->setOutputFile($file);
        $entity->setState(State::COMPLETED);

        return $entity;
    }
}
