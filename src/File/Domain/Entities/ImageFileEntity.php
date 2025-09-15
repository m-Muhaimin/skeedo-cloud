<?php

declare(strict_types=1);

namespace File\Domain\Entities;

use Doctrine\ORM\Mapping as ORM;
use File\Domain\ValueObjects\BlurHash;
use File\Domain\ValueObjects\Height;
use File\Domain\ValueObjects\ObjectKey;
use File\Domain\ValueObjects\Size;
use File\Domain\ValueObjects\Storage;
use File\Domain\ValueObjects\Url;
use File\Domain\ValueObjects\Width;

#[ORM\Entity]
class ImageFileEntity extends FileEntity
{
    #[ORM\Embedded(class: Width::class, columnPrefix: false)]
    private Width $width;

    #[ORM\Embedded(class: Height::class, columnPrefix: false)]
    private Height $height;

    #[ORM\Embedded(class: BlurHash::class, columnPrefix: false)]
    private BlurHash $blurHash;

    public function __construct(
        Storage $storage,
        ObjectKey $objectKey,
        Url $url,
        Size $size,
        Width $width,
        Height $height,
        BlurHash $blurHash,
    ) {
        parent::__construct(
            $storage,
            $objectKey,
            $url,
            $size,
        );

        $this->width = $width;
        $this->height = $height;
        $this->blurHash = $blurHash;
    }

    public function getWidth(): Width
    {
        return $this->width;
    }

    public function getHeight(): Height
    {
        return $this->height;
    }

    public function getBlurHash(): BlurHash
    {
        return $this->blurHash;
    }
}
