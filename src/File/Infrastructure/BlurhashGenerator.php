<?php

declare(strict_types=1);

namespace File\Infrastructure;

use File\Domain\ValueObjects\BlurHash;
use GdImage;
use kornrunner\Blurhash\Blurhash as BlurhashHelper;

class BlurhashGenerator
{
    public static function generateBlurHash(GdImage $image, int $width, int $height): BlurHash
    {
        if ($width > 64) {
            $height = (int) (64 / $width * $height);
            $width = 64;
            $image = imagescale($image, $width);
        }

        $pixels = [];
        for ($y = 0; $y < $height; ++$y) {
            $row = [];
            for ($x = 0; $x < $width; ++$x) {
                $index = imagecolorat($image, $x, $y);
                $colors = imagecolorsforindex($image, $index);

                $row[] = [$colors['red'], $colors['green'], $colors['blue']];
            }
            $pixels[] = $row;
        }

        $components_x = 4;
        $components_y = 3;
        return new BlurHash(
            BlurhashHelper::encode($pixels, $components_x, $components_y)
        );
    }
}
