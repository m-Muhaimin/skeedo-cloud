<?php

declare(strict_types=1);

namespace Assistant\Application\CommandHandlers;

use Ai\Domain\Embedding\EmbeddingServiceInterface;
use Ai\Domain\Services\AiServiceFactoryInterface;
use Ai\Domain\ValueObjects\Embedding;
use Ai\Domain\ValueObjects\Model;
use Ai\Infrastructure\Exceptions\UnreadableDocumentException;
use Ai\Infrastructure\Services\DocumentReader\DocumentReader;
use Assistant\Application\Commands\CreateDataUnitCommand;
use Assistant\Domain\Entities\AssistantEntity;
use Assistant\Domain\Repositories\AssistantRepositoryInterface;
use File\Domain\Entities\FileEntity;
use File\Domain\ValueObjects\ObjectKey;
use File\Domain\ValueObjects\Size;
use File\Domain\ValueObjects\Storage;
use File\Domain\ValueObjects\Url;
use Psr\Http\Message\UploadedFileInterface;
use Ramsey\Uuid\Nonstandard\Uuid;
use Dataset\Domain\Entities\AbstractDataUnitEntity;
use Dataset\Domain\Entities\FileUnitEntity;
use Dataset\Domain\Entities\LinkUnitEntity;
use Dataset\Domain\ValueObjects\Title;
use Dataset\Domain\ValueObjects\Url as DatasetUrl;
use RuntimeException;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\FilesystemException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Shared\Infrastructure\FileSystem\CdnInterface;

class CreateDataUnitCommandHandler
{
    public function __construct(
        private AssistantRepositoryInterface $repo,
        private CdnInterface $cdn,
        private DocumentReader $reader,
        private AiServiceFactoryInterface $factory,
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
    ) {}

    public function handle(CreateDataUnitCommand $cmd): AbstractDataUnitEntity
    {
        $assistant = $cmd->assistant instanceof AssistantEntity
            ? $cmd->assistant
            : $this->repo->ofId($cmd->assistant);

        if ($cmd->file) {
            $resource = $this->getFileResourceEntity($cmd->file);
            $assistant->addDataUnit($resource);
            return $resource;
        }

        if ($cmd->url) {
            $resource = $this->getPageResourceEntity($cmd->url);
            $assistant->addDataUnit($resource);
            return $resource;
        }

        throw new RuntimeException('Invalid command');
    }

    /**
     * @throws RuntimeException
     * @throws UnableToWriteFile
     * @throws FilesystemException
     * @throws UnreadableDocumentException
     */
    private function getFileResourceEntity(UploadedFileInterface $file): FileUnitEntity
    {
        $ext = strtolower(
            pathinfo($file->getClientFilename(), PATHINFO_EXTENSION)
        );

        // Save file to CDN
        $stream = $file->getStream();
        $stream->rewind();
        $name = Uuid::uuid4()->toString() . '.' . $ext;
        $this->cdn->write("/" . $name, $stream->getContents());

        $stream->rewind();
        $contents = $stream->getContents();

        $embedable = $this->reader->read($contents, $ext);

        $embedding = null;
        if ($embedable) {
            $model = new Model('text-embedding-3-small'); // Default model
            $service = $this->factory->create(EmbeddingServiceInterface::class, $model);
            $resp = $service->generateEmbedding($model, $embedable);

            $embedding = $resp->embedding;
        }

        $fe = new FileEntity(
            new Storage($this->cdn->getAdapterLookupKey()),
            new ObjectKey($name),
            new Url($this->cdn->getUrl($name)),
            new Size($file->getSize()),
            $embedding
        );

        $resource = new FileUnitEntity($fe);
        $resource->setTitle(
            new Title(
                $file->getClientFilename() ?
                    $this->getHumanReadableFileName($file->getClientFilename())
                    : null
            )
        );

        return $resource;
    }

    private function getHumanReadableFileName(string $fileName): string
    {
        // Remove file extension
        $name = pathinfo($fileName, PATHINFO_FILENAME);

        // Replace underscores and hyphens with spaces
        $name = str_replace(['_', '-'], ' ', $name);

        // Remove multiple consecutive spaces
        $name = preg_replace('/\s+/', ' ', $name);

        // Trim leading and trailing spaces
        $name = trim($name);

        // Capitalize the first letter of each word
        return mb_convert_case(
            $name,
            MB_CASE_TITLE,
            "UTF-8"
        );
    }

    private function getPageResourceEntity(DatasetUrl $url): LinkUnitEntity
    {
        $embedable = $this->reader->readFromUrl($url->value);
        $embedding = new Embedding();

        if ($embedable) {
            $model = new Model('text-embedding-3-small'); // Default model
            $service = $this->factory->create(EmbeddingServiceInterface::class, $model);
            $resp = $service->generateEmbedding($model, $embedable);

            $embedding = $resp->embedding;
        }

        return new LinkUnitEntity($url, $embedding);
    }
}
