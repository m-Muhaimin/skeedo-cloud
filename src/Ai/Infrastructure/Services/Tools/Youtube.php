<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Tools;

use Ai\Domain\ValueObjects\Model;
use Ai\Infrastructure\Services\CostCalculator;
use Easy\Container\Attributes\Inject;
use Override;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

class Youtube implements ToolInterface
{
    public const LOOKUP_KEY = 'youtube';
    private string $baseUrl = 'https://www.searchapi.io/api/';

    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private CostCalculator $calc,

        #[Inject('option.searchapi.api_key')]
        private ?string $apiKey = null,

        #[Inject('option.features.tools.youtube.is_enabled')]
        private ?bool $isEnabled = null,
    ) {}

    #[Override]
    public function isEnabled(): bool
    {
        return (bool) $this->apiKey && $this->isEnabled;
    }

    #[Override]
    public function getDescription(): string
    {
        return 'Retrives all meta information and transcription from YouTube for a given video ID.
        The `id` parameter is the YouTube video ID. The video ID can be found in the URL of the video.

        YouTube video URLs can follow one of the following formats:
        - https://www.youtube.com/watch?v=VIDEO_ID
        - https://youtu.be/VIDEO_ID
        - http://www.youtube.com/embed/VIDEO_ID
        - http://www.youtube.com/v/VIDEO_ID

        The tool will return a JSON string that contains the meta information and transcription of the video.
        It should be used when a user requests meta information and transcription of a YouTube video.
        
        The tool will return an empty string if the video ID is not found or the video is not available.';
    }

    #[Override]
    public function getDefinitions(): array
    {
        return [
            "type" => "object",
            "properties" => [
                "id" => [
                    "type" => "string",
                    "description" => "YouTube video ID. It can be found in the URL of the video."
                ]
            ],
            "required" => ["id"]
        ];
    }

    #[Override]
    public function call(
        UserEntity $user,
        WorkspaceEntity $workspace,
        array $params = [],
        array $files = [],
        array $knowledgeBase = [],
    ): CallResponse {
        // Get video details
        $video = $this->sendRequest(
            'GET',
            '/v1/search',
            params: [
                'engine' => 'youtube_video',
                'video_id' => $params['id']
            ]
        );

        $content = json_decode($video->getBody()->getContents());
        if (isset($content->error)) {
            throw new CallException($content->error);
        }

        // Get transcriptions
        $transcriptions = $this->sendRequest(
            'GET',
            '/v1/search',
            params: [
                'engine' => 'youtube_video',
                'video_id' => $params['id']
            ]
        );

        $body = json_decode($transcriptions->getBody()->getContents());
        $content->transcripts = $body->transcripts ?? [];

        // Calculate cost for 2 requests
        $cost = $this->calc->calculate(2, new Model('searchapi'));

        $content = json_encode($content, JSON_INVALID_UTF8_SUBSTITUTE);
        if ($content === false) {
            $content = 'Failed to encode results: ' . json_last_error_msg();
        }

        return new CallResponse(
            $content,
            $cost
        );
    }

    private function sendRequest(
        string $method,
        string $path,
        array|string $body = [],
        array $params = [],
        array $headers = []
    ): ResponseInterface {
        $baseUrl = $this->baseUrl;

        $req = $this->requestFactory
            ->createRequest($method, $baseUrl . trim($path, "/"))
            ->withHeader('Authorization', 'Bearer ' . $this->apiKey)
            ->withHeader('Accept', 'application/json');

        if ($body) {
            $req = $req
                ->withBody($this->streamFactory->createStream(
                    is_array($body) ? json_encode($body) : $body
                ));
        }

        if ($params) {
            $req = $req->withUri(
                $req->getUri()->withQuery(http_build_query($params))
            );
        }

        if ($headers) {
            foreach ($headers as $key => $value) {
                $req = $req->withHeader($key, $value);
            }
        }

        $resp = $this->client->sendRequest($req);

        if ($resp->getStatusCode() !== 200) {
            throw new CallException('Failed to retrieve data from YouTube' . $resp->getBody()->getContents());
        }

        return $resp;
    }
}
