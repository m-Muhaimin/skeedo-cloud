<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api;

use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Exception;
use JsonException;
use Presentation\Commands\UpdateCommand;
use Presentation\Exceptions\UnprocessableEntityException;
use Presentation\RequestHandlers\Admin\Api\AdminApi;
use Presentation\Response\EmptyResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\Services\LicenseManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;
use ZipArchive;

#[Route(path: '/update', method: RequestMethod::POST)]
class UpdateApi extends AdminApi implements
    RequestHandlerInterface
{
    public function __construct(
        private ContainerInterface $container,
        private LicenseManager $lm,

        #[Inject('config.enable_debugging')]
        private bool $debug = false,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $files = $request->getUploadedFiles();

        /** @var UploadedFileInterface|null $file */
        $file = array_values($files)[0] ?? null;

        if (!$file) {
            throw new UnprocessableEntityException('Upload failed');
        }

        // Check if the extension is .zip
        $filename = $file->getClientFilename();
        if (pathinfo($filename, PATHINFO_EXTENSION) !== 'zip') {
            throw new UnprocessableEntityException('Please upload a zip file');
        }

        try {
            $this->lm->refresh();
        } catch (Exception $e) {
            throw new UnprocessableEntityException($e->getMessage());
        }

        $filename = uniqid() . '.zip';
        $filepath = sys_get_temp_dir() . '/' . $filename;

        try {
            $file->moveTo($filepath);
        } catch (Throwable $th) {
            throw new UnprocessableEntityException(
                $this->debug && $th->getMessage() ? $th->getMessage() : 'Failed to update',
                previous: $th
            );
        }

        try {
            $this->validateZipFile(
                $filepath
            );

            $in = new ArrayInput([
                'file' => $filepath,
            ]);
            $in->setInteractive(false);
            $out = new BufferedOutput();

            /** @var UpdateCommand */
            $cmd = $this->container->get(UpdateCommand::class);
            $resp = $cmd->run($in, $out);

            if ($resp !== Command::SUCCESS) {
                throw new Exception('Failed to update');
            }
        } catch (Throwable $th) {
            unlink($filepath);

            throw new UnprocessableEntityException(
                $th->getMessage() ?: 'Invalid update file',
                previous: $th
            );
        }

        unlink($filepath);
        return new EmptyResponse();
    }

    public function validateZipFile(string $path): void
    {
        $zip = new ZipArchive();
        $openResp = $zip->open($path, ZipArchive::RDONLY);

        if ($openResp !== true) {
            throw new Exception(
                "Failed open zip archive with following code: " . $openResp
            );
        }

        $jsonFileContent = $zip->getFromName('composer.json');
        if ($jsonFileContent === false) {
            throw new Exception('Composer file not found');
        }

        try {
            $json = json_decode(
                $jsonFileContent,
                null,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            throw new Exception(
                message: "Failed to decode composer.json file with following error: " . $e->getMessage(),
                previous: $e
            );
        }

        if (!isset($json->name)) {
            throw new Exception(
                "Invalid composer.json file: <name> is not set."
            );
        }

        if ($json->name !== 'heyaikeedo/aikeedo') {
            throw new Exception('Invalid update file');
        }
    }
}
