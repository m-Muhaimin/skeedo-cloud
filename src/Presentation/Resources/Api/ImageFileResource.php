<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Application;
use File\Domain\Entities\ImageFileEntity;
use JsonSerializable;
use Override;
use Presentation\Resources\DateTimeResource;
use Shared\Infrastructure\FileSystem\CdnInterface;

class ImageFileResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(private ImageFileEntity $file) {}

    #[Override]
    public function jsonSerialize(): array
    {
        $file = $this->file;

        $url = $file->getUrl();
        $cdn = Application::make(CdnInterface::class);

        if ($file->getStorage()->value === $cdn->getAdapterLookupKey()) {
            $url = $cdn->getUrl($file->getObjectKey()->value);
        }

        return [
            'id' => $file->getId(),
            'created_at' => new DateTimeResource($file->getCreatedAt()),
            'updated_at' => new DateTimeResource($file->getUpdatedAt()),

            'storage' => $file->getStorage(),
            'object_key' => $file->getObjectKey(),
            'url' => $url,
            'size' => $file->getSize(),

            'width' => $file->getWidth(),
            'height' => $file->getHeight(),
            'blur_hash' => $file->getBlurHash(),

            'extension' => $file->getExtension(),
        ];
    }
}
