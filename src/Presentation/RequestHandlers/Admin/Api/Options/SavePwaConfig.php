<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Options;

use DOMDocument;
use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Exception;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\FilesystemException;
use League\Flysystem\Visibility;
use Option\Application\Commands\SaveOptionCommand;
use Override;
use Presentation\Response\EmptyResponse;
use Presentation\Validation\ValidationException;
use Presentation\Validation\Validator;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Nonstandard\Uuid;
use RuntimeException;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use Shared\Infrastructure\FileSystem\CdnInterface;

#[Route(path: '/pwa', method: RequestMethod::POST)]
class SavePwaConfig extends OptionsApi implements
    RequestHandlerInterface
{
    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private Validator $validator,
        private Dispatcher $dispatcher,
        private CdnInterface $cdn,

        #[Inject('config.dirs.webroot')]
        private string $webroot,
    ) {}

    /**
     * @throws ValidationException
     * @throws NoHandlerFoundException
     * @throws RuntimeException
     * @throws UnableToWriteFile
     * @throws FilesystemException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);
        $payload = (object) $request->getParsedBody();

        if (!property_exists($payload, 'pwa')) {
            return new EmptyResponse();
        }

        $payload = $payload->pwa;

        if (property_exists($payload, 'is_enabled')) {
            $cmd = new SaveOptionCommand('pwa', json_encode([
                'is_enabled' => $payload->is_enabled ? '1' : '0'
            ]));

            $this->dispatcher->dispatch($cmd);
        }

        $path = $this->webroot . '/app.webmanifest';
        $pwa = json_decode(
            file_exists($path) ? file_get_contents($path) : '{}'
        );

        $fields = ['name', 'short_name', 'description', 'theme_color', 'background_color', 'display'];

        foreach ($fields as $field) {
            if (!property_exists($payload, $field)) {
                continue;
            }

            if ($payload->$field) {
                $pwa->$field = $payload->$field;
                continue;
            }

            if (property_exists($pwa, $field)) {
                unset($pwa->$field);
            }
        }

        $files = $request->getUploadedFiles();
        if (isset($files['pwa']['icon']) && $files['pwa']['icon']->getSize() > 0) {
            $file = $this->saveFile($files['pwa']['icon']);
            $icon = (object) [
                'src' => $file['url'],
                'sizes' => $file['width'] . 'x' . $file['height'],
                'type' => $file['type'],
                'purpose' => 'any'
            ];

            $pwa->icons = [$icon];
        }

        if (isset($pwa->icons) && property_exists($payload, 'maskable_icon')) {
            // Filter out all icons with purpose 'maskable'
            $pwa->icons = array_filter($pwa->icons, fn($icon) => $icon->purpose !== 'maskable');

            // Store the "any" purpose icon if it exists
            $anyIcon = null;
            foreach ($pwa->icons as $icon) {
                if ($icon->purpose === 'any') {
                    $anyIcon = $icon;
                    break;
                }
            }

            // Add maskable icon if enabled and we have an "any" icon to clone
            if ($payload->maskable_icon && $anyIcon) {
                $maskableIcon = clone $anyIcon;
                $maskableIcon->purpose = 'maskable';
                array_unshift($pwa->icons, $maskableIcon);
            }
        }

        if (isset($pwa->icons)) {
            $pwa->icons = array_values($pwa->icons);
        }

        $pwa->start_url = '/';

        file_put_contents($path, json_encode($pwa, JSON_PRETTY_PRINT));
        return new EmptyResponse();
    }

    /**
     * @throws ValidationException
     */
    private function validateRequest(ServerRequestInterface $req): void
    {
        $payload = json_decode(json_encode($req->getParsedBody()), true);

        $this->validator->validate(
            $payload,
            [
                'pwa.name' => 'string',
                'pwa.short_name' => 'string',
                'pwa.description' => 'string',
                'pwa.theme_color' =>  [
                    'string',
                    'regex' => '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'
                ],
                'pwa.background_color' => [
                    'string',
                    'regex' => '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'
                ],
                'pwa.display' => 'in:fullscreen,standalone,minimal-ui,browser',
            ]
        );
    }

    /**
     * @return array{url:string,type:string,width:int,height:int}
     */
    private function saveFile(UploadedFileInterface $file): array
    {
        $ext = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
        $name = Uuid::uuid4()->toString() . '.' . $ext;
        $fileData = $file->getStream()->getContents();

        $this->cdn->write("/" . $name, $fileData, [
            // Always make it public even though the pre-signed secure URLs option is enabled.
            'visibility' => Visibility::PUBLIC
        ]);

        if ($ext == 'svg') {
            $dom = new DOMDocument('1.0', 'utf-8');
            $dom->loadXML($fileData);
            $svg = $dom->documentElement;

            $size = [
                $svg->getAttribute('width') ?? 512,
                $svg->getAttribute('height') ?? 512
            ];
        } else {
            $size = getimagesizefromstring($fileData);
        }

        $url = $this->cdn->getUrl($name);
        $url = strstr($url, '?', true) ?: $url;

        return [
            'url' => $url,
            'type' => $file->getClientMediaType(),
            'width' => $size[0],
            'height' => $size[1]
        ];
    }
}
