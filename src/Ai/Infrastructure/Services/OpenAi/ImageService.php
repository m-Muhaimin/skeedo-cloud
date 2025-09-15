<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\OpenAi;

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
use Billing\Domain\ValueObjects\CreditCount;
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
use Psr\Http\Message\UploadedFileInterface;
use Shared\Infrastructure\FileSystem\CdnInterface;
use Shared\Infrastructure\Services\ModelRegistry;
use Throwable;
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

        #[Inject('option.features.is_safety_enabled')]
        private bool $checkSafety = true,

        #[Inject('option.features.imagine.is_enabled')]
        private bool $isToolEnabled = false,
    ) {
        parent::__construct($registry, 'openai', 'image');
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

        $estimate = $this->calc->estimate($model, CostCalculator::OUTPUT);
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

        if (in_array($model->value, ['dall-e-3'])) {
            $data['response_format'] = 'b64_json';
        }

        if (array_key_exists('size', $params)) {
            $data['size'] = $params['size'];
        }

        if (array_key_exists('quality', $params)) {
            $data['quality'] = $params['quality'];
        }

        if (array_key_exists('style', $params)) {
            $data['style'] = $params['style'];
        }

        if ($model->value === 'gpt-image-1') {
            $data['moderation'] = $this->checkSafety ? 'auto' : 'low';
        }

        $resources = [];
        if (
            $model->value === 'gpt-image-1'
            && isset($params['images']) && is_array($params['images']) && count($params['images']) > 0
        ) {
            /** @var UploadedFileInterface $image */
            foreach ($params['images'] as $image) {
                $filename = $image->getClientFilename();
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                $path = sys_get_temp_dir() . '/' . uniqid() . '.' . $ext;
                $image->moveTo($path);

                $resource = fopen($path, 'r');
                $resources[] = $resource;

                $data['image[]'] = $resource;
            }

            if (count($resources) > 0) {
                $endpoint = '/v1/images/edits';
                $headers['Content-Type'] = 'multipart/form-data';
            }
        }

        try {
            $resp = $this->client->sendRequest('POST', $endpoint, $data, headers: $headers);
        } finally {
            $workspace->unallocate($estimate);
        }

        $resp = json_decode($resp->getBody()->getContents());

        foreach ($resources as $resource) {
            try {
                fclose($resource);
                unlink($path);
            } catch (Throwable $th) {
                // Failed to close or delete resource,
                // this is not a big deal, so we can ignore it
            }
        }

        if (!isset($resp->data) || !is_array($resp->data) || count($resp->data) === 0) {
            throw new DomainException('Failed to generate image');
        }

        $content = base64_decode($resp->data[0]->b64_json);

        if ($this->client->hasCustomKey()) {
            // Cost is not calculated for custom keys,
            $cost = new CreditCount(0);
        } else if ($model->value === 'gpt-image-1') {
            $tc = $this->calc->calculate($resp->usage->input_tokens_details->text_tokens, $model, CostCalculator::INPUT);
            $ic = $this->calc->calculate($resp->usage->input_tokens_details->image_tokens, $model, CostCalculator::IMAGE);
            $oc = $this->calc->calculate($resp->usage->output_tokens, $model, CostCalculator::OUTPUT);
            $cost = new CreditCount($tc->value + $ic->value + $oc->value);
        } else {
            $flags = isset($data['quality']) && $data['quality'] == 'hd'
                ? CostCalculator::QUALITY_HD
                : CostCalculator::QUALITY_SD;

            if (isset($data['size'])) {
                match ($data['size']) {
                    '256x256' => $flags |= CostCalculator::SIZE_256x256,
                    '512x512' => $flags |= CostCalculator::SIZE_512x512,
                    '1024x1024' => $flags |= CostCalculator::SIZE_1024x1024,
                    '1024x1792' => $flags |= CostCalculator::SIZE_1024x1792,
                    '1792x1024' => $flags |= CostCalculator::SIZE_1792x1024,
                    default => $flags |= CostCalculator::SIZE_1024x1024
                };
            } else {
                $flags |= CostCalculator::SIZE_1024x1024;
            }

            $cost = $this->calc->calculate(
                1,
                $model,
                $flags
            );
        }

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
