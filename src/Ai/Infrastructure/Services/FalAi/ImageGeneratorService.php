<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\FalAi;

use Ai\Domain\Entities\ImageEntity;
use Ai\Domain\Exceptions\DomainException;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Ai\Domain\Image\ImageServiceInterface;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\RequestParams;
use Ai\Infrastructure\Services\CostCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Easy\Container\Attributes\Inject;
use League\Flysystem\Visibility;
use Override;
use Shared\Infrastructure\FileSystem\CdnInterface;
use Shared\Infrastructure\Services\ModelRegistry;
use Throwable;
use Traversable;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

class ImageGeneratorService implements ImageServiceInterface
{
    private ?array $models = null;

    public function __construct(
        private Client $client,
        private Helper $helper,
        private CostCalculator $calc,
        private CdnInterface $cdn,
        private ModelRegistry $registry,
        private EntityManagerInterface $em,

        #[Inject('option.features.is_safety_enabled')]
        private bool $checkSafety = true,

        #[Inject('option.features.imagine.is_enabled')]
        private bool $isToolEnabled = false,

        #[Inject('option.falai.api_key')]
        private ?string $apiKey = null,
    ) {}

    #[Override]
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

        $card = $this->models[$model->value];
        $endpoint = $card['config']['endpoint'] ?? $model->value;

        $entity = new ImageEntity(
            $workspace,
            $user,
            $model,
            RequestParams::fromArray($params),
        );

        $data = [
            'prompt' =>  $params['prompt'],
            'sync_mode' => true,
            'num_images' => 1
        ];

        if ($model->value === 'flux-pro') {
            // Default is 2. 1 is the most strict. 6 is the least strict.
            $data['safety_tolerance'] = $this->checkSafety ? 2 : 6;
        } else if ($model->value === 'flux/schnell' || $model->value === 'flux/dev') {
            $data['enable_safety_checker'] = $this->checkSafety ? true : false;
        }

        // negative prompt
        if (
            isset($params['negative_prompt'])
            && ($card['config']['negative_prompt'] ?? false)
        ) {
            $data['negative_prompt'] = $params['negative_prompt'];
        }

        foreach ($card['config']['params'] ?? [] as $p) {
            if (!isset($params[$p['key']])) {
                continue;
            }

            $allowed = array_map(fn($o) => $o['value'], $p['options'] ?? []);
            if (!in_array($params[$p['key']], $allowed)) {
                continue;
            }

            $val = $params[$p['key']];

            if ($val === 'true') {
                $val = true;
            } else if ($val === 'false') {
                $val = false;
            }

            $data[$p['key']] = $val;
        }

        if (isset($params['images']) && isset($card['config']['images'])) {
            $i = 0;
            $limit = $card['config']['images']['limit'] ?? 1;

            /** @var UploadedFileInterface $image */
            foreach ($params['images'] as $image) {
                $filename = $image->getClientFilename();
                $extension = pathinfo($filename, PATHINFO_EXTENSION);

                $key = $this->cdn->generatePath($extension, $workspace, $user);
                $this->cdn->write($key, $image->getStream()->getContents(), [
                    // Always make it public even though the pre-signed secure 
                    // URLs option is enabled.
                    'visibility' => Visibility::PUBLIC
                ]);

                $url = $this->cdn->getUrl($key);

                if ($model->value === 'fal-ai/nano-banana') {
                    if (!isset($data['image_urls'])) {
                        $data['image_urls'] = [];
                    }

                    $data['image_urls'][] = $url;
                } else {
                    if ($i == 0) {
                        $data['image_url'] = $url;
                    } else if ($i + 1 == $limit) {
                        $data['tail_image_url'] = $url;
                    }
                }

                $i++;
                if ($i >= $limit) {
                    break;
                }
            }
        }

        if (
            isset($card['config']['images']['endpoint'])
            && (
                isset($data['image_url'])
                || isset($data['tail_image_url'])
                || isset($data['image_urls'])
            )
        ) {
            $endpoint = $card['config']['images']['endpoint'];
        }

        try {
            $resp = $this->client->sendRequest(
                'POST',
                $endpoint,
                $data,
                ['fal_webhook' => $this->helper->getCallBackUrl($entity)]
            );
        } catch (Throwable $th) {
            $workspace->unallocate($estimate);
            throw $th;
        }

        $content = json_decode($resp->getBody()->getContents());

        $entity->addMeta('reserved_credit', $estimate->value);
        $entity->addMeta('falai_id', $content->request_id);
        $entity->addMeta('falai_response_url', $content->response_url);
        return $entity;
    }

    #[Override]
    public function supportsModel(Model $model): bool
    {
        $this->parseDirectory();
        return array_key_exists($model->value, $this->models);
    }

    #[Override]
    public function getSupportedModels(): Traversable
    {
        $this->parseDirectory();

        foreach ($this->models as $key => $model) {
            yield new Model($key);
        }
    }

    private function parseDirectory(): void
    {
        if ($this->models !== null) {
            return;
        }

        if (!$this->isToolEnabled) {
            $this->models = [];
            return;
        }

        $services = array_filter($this->registry['directory'], fn($service) => $service['key'] === 'falai');

        if (count($services) === 0) {
            $this->models = [];
            return;
        }

        $service = array_values($services)[0];
        $models = array_filter($service['models'], fn($model) => $model['type'] === 'image');

        $this->models = array_reduce($models, function ($carry, $model) {
            $carry[$model['key']] = $model;
            return $carry;
        }, []);
    }
}
