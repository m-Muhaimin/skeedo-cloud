<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\DocumentReader;

use Ai\Infrastructure\Exceptions\UnreadableDocumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Throwable;

class DocumentReader
{
    /** @var array<class-string<DocumentReaderInterface>|DocumentReaderInterface> */
    private array $readers;

    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly ContainerInterface $container,
    ) {}

    /**
     * @param class-string<DocumentReaderInterface>|DocumentReaderInterface $reader
     * @return void
     */
    public function addReader(string|DocumentReaderInterface $reader): void
    {
        $this->readers[] = $reader;
    }

    public function readFromUrl(string $url, ?int $max = null): ?string
    {
        $request = $this->requestFactory->createRequest('GET', $url);
        $response = $this->client->sendRequest($request);

        $contents = $response->getBody()->getContents();

        $contentType = $response->getHeaderLine('Content-Type');
        return $this->read($contents, $contentType, $max);
    }

    /**
     * @param null|string $ext extension without dot or mime
     */
    public function read(
        string $contents,
        ?string $ext = null,
        ?int $max = null
    ): ?string {
        if ($max !== null && $max < 0) {
            $max = null;
        }

        $identifier = $ext;
        if (!$ext) {
            $identifier = trim($contents);
        }

        foreach ($this->readers as $index => $reader) {
            if (!($reader instanceof DocumentReaderInterface)) {
                try {
                    /** @var DocumentReaderInterface */
                    $reader = $this->container->get($reader);
                    $this->readers[$index] = $reader;
                } catch (Throwable $th) {
                    unset($this->readers[$index]);
                    continue;
                }
            }

            if ($reader->supports($identifier)) {
                try {
                    return $reader->read($contents, $max);
                } catch (UnreadableDocumentException) {
                    continue;
                }
            }
        }

        // If no reader could handle the content, return null
        return null;
    }
}
