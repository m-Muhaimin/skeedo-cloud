<?php

declare(strict_types=1);

namespace Presentation\Response;

use Easy\Http\Message\StatusCode;
use Laminas\Diactoros\Stream;

class DownloadResponse extends Response
{
    public function __construct(
        string $body,
        string $filename,
        int $size = 0,
        StatusCode $status = StatusCode::OK,
        array $headers = []
    ) {
        $headers['Content-Type'] = 'application/octet-stream';
        $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '"';
        $headers['Content-Length'] = $size > 0 ? (string) $size : (string) strlen($body);
        $headers['Cache-Control'] = 'no-cache, no-store, must-revalidate';
        $headers['Pragma'] = 'no-cache';
        $headers['Expires'] = '0';

        // Create a stream from the string content
        $stream = new Stream('php://temp', 'wb+');
        $stream->write($body);
        $stream->rewind();

        parent::__construct($stream, $status, $headers);
    }
}
