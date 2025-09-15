<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Application;
use File\Domain\Entities\FileEntity;
use JsonSerializable;
use Override;
use Presentation\Resources\DateTimeResource;
use Shared\Infrastructure\FileSystem\CdnInterface;

class FileResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(
        private FileEntity $file,
        private bool $secure = false
    ) {}

    #[Override]
    public function jsonSerialize(): array
    {
        $file = $this->file;


        if ($this->secure) {
            $time = time();
            $token = hash('sha256', $time . $file->getId() . env('JWT_TOKEN'));
            $url = 'app/download/' . $file->getId() . '/' . $time . '/' . $token;
        } else {
            $url = $file->getUrl();
            $cdn = Application::make(CdnInterface::class);

            if ($file->getStorage()->value === $cdn->getAdapterLookupKey()) {
                $url = $cdn->getUrl($file->getObjectKey()->value);
            }
        }

        return [
            'id' => $file->getId(),
            'created_at' => new DateTimeResource($file->getCreatedAt()),
            'updated_at' => new DateTimeResource($file->getUpdatedAt()),

            'storage' => $file->getStorage(),
            'object_key' => $file->getObjectKey(),
            'url' => $url,
            'size' => $file->getSize(),

            'extension' => $file->getExtension(),
            'has_embedding' => (bool) $file->getEmbedding()->value,
        ];
    }
}
