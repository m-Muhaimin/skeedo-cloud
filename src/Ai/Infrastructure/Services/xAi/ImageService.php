<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\xAi;

use Ai\Domain\Entities\ImageEntity;
use Ai\Domain\Exceptions\DomainException;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Ai\Domain\Exceptions\ModelNotSupportedException;
use Ai\Domain\Image\ImageServiceInterface;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\RequestParams;
use Ai\Domain\ValueObjects\State;
use Ai\Infrastructure\Services\AbstractBaseService;
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
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

class ImageService extends AbstractBaseService implements ImageServiceInterface
{
    public function __construct(
        private Client $client,
        private CostCalculator $calc,
        private CdnInterface $cdn,
        private ModelRegistry $registry,
        private EntityManagerInterface $em,

        #[Inject('option.features.imagine.is_enabled')]
        private bool $isToolEnabled = false,
    ) {
        parent::__construct($registry, 'xai', 'image');
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

        $endpoint = '/v1/images/generations';
        $headers = [];

        $data = [
            'prompt' => $params['prompt'],
            'model' => $model->value
        ];

        try {
            $resp = $this->client->sendRequest('POST', $endpoint, $data, headers: $headers);
        } finally {
            $workspace->unallocate($estimate);
        }

        $resp = json_decode($resp->getBody()->getContents());

        $url = $resp->data[0]->url;
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
            RequestParams::fromArray($params),
            $cost
        );

        $entity->setOutputFile($file);
        $entity->setState(State::COMPLETED);

        return $entity;
    }
}
