<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\StabilityAi;

use Ai\Domain\Entities\ImageEntity;
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
use Shared\Infrastructure\FileSystem\CdnInterface;
use Shared\Infrastructure\Services\ModelRegistry;
use Traversable;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

class ImageGeneratorService implements ImageServiceInterface
{
    private array $models = [
        'sd-ultra',
        'sd-core',
        'sd3-large',
        'sd3-large-turbo',
        'sd3-medium',
        'stable-diffusion-xl-1024-v1-0',
        'stable-diffusion-v1-6',
    ];

    public function __construct(
        private Client $client,
        private CostCalculator $calc,
        private CdnInterface $cdn,
        private ModelRegistry $registry,
        private EntityManagerInterface $em,

        #[Inject('option.features.imagine.is_enabled')]
        private bool $isToolEnabled = false,

        #[Inject('version')]
        private string $version = '1.0.0',

        #[Inject('option.stabilityai.api_key')]
        private ?string $apiKey = null,
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

        try {
            if (in_array($model->value, ['sd-ultra', 'sd-core'])) {
                return $this->ultra($workspace, $user, $model, $params);
            }

            if (in_array($model->value, ['sd3-large', 'sd3-large-turbo', 'sd3-medium'])) {
                return $this->sd3($workspace, $user, $model, $params);
            }

            return $this->legacy($workspace, $user, $model, $params);
        } finally {
            $workspace->unallocate($estimate);
        }
    }

    private function ultra(
        WorkspaceEntity $workspace,
        UserEntity $user,
        Model $model,
        ?array $params = null
    ): ImageEntity {
        $data = [
            'prompt' => $params['prompt']
        ];

        if (array_key_exists('negative_prompt', $params)) {
            $data['negative_prompt'] = $params['negative_prompt'];
        }

        if (array_key_exists('aspect_ratio', $params)) {
            $data['aspect_ratio'] = $params['aspect_ratio'];
        }

        if (array_key_exists('style', $params)) {
            $data['style_preset'] = $params['style'];
        }

        $resp = $this->client->sendRequest(
            'POST',
            '/v2beta/stable-image/generate/' . substr($model->value, 3),
            $data,
            headers: [
                'Content-Type' => 'multipart/form-data',
            ]
        );

        $body = json_decode($resp->getBody()->getContents());
        $content = base64_decode($body->image);

        $cost = $this->calc->calculate(1, $model);

        $file = $this->saveImage($content, $workspace, $user);

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

    private function sd3(
        WorkspaceEntity $workspace,
        UserEntity $user,
        Model $model,
        ?array $params = null
    ): ImageEntity {
        $data = [
            'prompt' => $params['prompt'],
            'model' => $model->value,
        ];

        if (array_key_exists('aspect_ratio', $params)) {
            $data['aspect_ratio'] = $params['aspect_ratio'];
        }

        if (array_key_exists('negative_prompt', $params)) {
            $data['negative_prompt'] = $params['negative_prompt'];
        }

        $resp = $this->client->sendRequest(
            'POST',
            '/v2beta/stable-image/generate/sd3',
            $data,
            headers: [
                'Content-Type' => 'multipart/form-data',
            ]
        );

        $body = json_decode($resp->getBody()->getContents());
        $content = base64_decode($body->image);
        $cost = $this->calc->calculate(1, $model);

        $file = $this->saveImage($content, $workspace, $user);

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

    private function legacy(
        WorkspaceEntity $workspace,
        UserEntity $user,
        Model $model,
        ?array $params = null
    ): ImageEntity {
        $data = [
            'text_prompts' => [
                [
                    'text' => $params['prompt'],
                    'weight' => 1
                ]
            ]
        ];

        if (array_key_exists('size', $params)) {
            $dimensions = explode('x', $params['size'], 2);

            if (count($dimensions) === 2) {
                $data['width'] = $dimensions[0];
                $data['height'] = $dimensions[1];
            }
        }

        foreach (['sampler', 'clip_guidance_preset', 'style'] as $key) {
            if (array_key_exists($key, $params)) {
                $data[$key] = $params[$key];
            }
        }

        if (array_key_exists('negative_prompt', $params)) {
            $data['text_prompts'][] = [
                'text' => $params['negative_prompt'],
                'weight' => -1
            ];
        }

        $resp = $this->client->sendRequest(
            'POST',
            '/v1/generation/' . $model->value . '/text-to-image',
            $data
        );

        $body = json_decode($resp->getBody()->getContents());
        $artifact = $body->artifacts[0];
        $content = base64_decode($artifact->base64);

        $cost = $this->calc->calculate(1, $model);

        $file = $this->saveImage($content, $workspace, $user);

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

    /**
     * Save image content to CDN and return ImageFileEntity.
     */
    private function saveImage(
        string $content,
        WorkspaceEntity $workspace,
        UserEntity $user
    ): ImageFileEntity {
        $name = $this->cdn->generatePath('png', $workspace, $user);
        $this->cdn->write($name, $content);

        $img = imagecreatefromstring($content);
        $width = imagesx($img);
        $height = imagesy($img);

        return new ImageFileEntity(
            new Storage($this->cdn->getAdapterLookupKey()),
            new ObjectKey($name),
            new Url($this->cdn->getUrl($name)),
            new Size(strlen($content)),
            new Width($width),
            new Height($height),
            BlurhashGenerator::generateBlurHash($img, $width, $height),
        );
    }
}
