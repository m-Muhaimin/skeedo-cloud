<?php

declare(strict_types=1);

namespace Plugin\Domain\Services;

use Plugin\Domain\Context;
use Plugin\Domain\Exceptions\InvalidPluginComposerJsonFileException;
use Plugin\Domain\Exceptions\InvalidPluginException;
use Plugin\Domain\Exceptions\PluginComposerFileNotFoundException;
use ZipArchive;

class PluginValidationService
{
    /**
     * @param string $zipFilePath
     * @return Context
     * @throws InvalidPluginException
     * @throws PluginComposerFileNotFoundException
     * @throws InvalidPluginComposerJsonFileException
     */
    public function validateZipFile(string $zipFilePath): Context
    {
        $zip = new ZipArchive();
        $openResp = $zip->open($zipFilePath, ZipArchive::RDONLY);

        if ($openResp !== true) {
            throw new InvalidPluginException(
                "Failed open zip archive with following code: " . $openResp
            );
        }

        $jsonFileContent = $zip->getFromName('composer.json');
        if ($jsonFileContent === false) {
            throw new PluginComposerFileNotFoundException();
        }

        return new Context($jsonFileContent);
    }
}
