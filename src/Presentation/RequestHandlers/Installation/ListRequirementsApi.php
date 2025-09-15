<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Installation;

use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\UnprocessableEntityException;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

#[Route(path: '/requirements', method: RequestMethod::GET)]
class ListRequirementsApi extends InstallationApi implements
    RequestHandlerInterface
{
    public function __construct(
        #[Inject('config.dirs.root')]
        private string $rootDir,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            return $this->handleRequirementsRequest($request);
        } catch (Throwable $th) {
            throw new UnprocessableEntityException($th->getMessage());
        }
    }

    private function handleRequirementsRequest(
        ServerRequestInterface $request
    ): ResponseInterface {
        $config = [];

        // Check PHP version
        $config[] = [
            'name' => 'PHP Version',
            'requirement' => '8.2+',
            'current' => PHP_VERSION,
            'is_satisfied' => version_compare(PHP_VERSION, '8.2') >= 0,
            'is_required' => true
        ];


        // PHP INI: file_uploads
        $config[] = [
            'name' => 'File uploads',
            'requirement' => 'On',
            'current' => ini_get('file_uploads') ? 'On' : 'Off',
            'is_satisfied' => (bool) ini_get('file_uploads'),
            'is_required' => true
        ];

        // PHP INI: post_max_size
        $config[] = [
            'name' => 'Post max size',
            'requirement' => '128M+',
            'current' => ini_get('post_max_size'),
            'is_satisfied' => $this->parseSize(ini_get('post_max_size')) >= 128 * 1024 * 1024,
            'is_required' => false
        ];

        // PHP INI: upload_max_filesize
        $config[] = [
            'name' => 'Upload max filesize',
            'requirement' => '128M+',
            'current' => ini_get('upload_max_filesize'),
            'is_satisfied' => $this->parseSize(ini_get('upload_max_filesize')) >= 128 * 1024 * 1024,
            'is_required' => false
        ];

        $extensions = array();

        // Check extensions
        $installedExts = array_map('strtolower', get_loaded_extensions());
        $check = ['bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'gd', 'intl', 'json', 'libxml', 'mbstring', 'openssl', 'pcre', 'phar', 'simplexml', 'tokenizer', 'xml', 'xmlwriter', 'zip'];

        foreach ($check as $ext) {
            $isInstalled = in_array(strtolower($ext), $installedExts);

            $extensions[] = [
                'name' => $ext,
                'is_satisfied' => $isInstalled,
                'is_required' => true
            ];
        }

        $check = ['amqp', 'mongodb', 'xdebug', 'pcov', 'uuid', 'uopz'];
        foreach ($check as $ext) {
            $isInstalled = in_array(strtolower($ext), $installedExts);

            $extensions[] = [
                'name' => $ext,
                'is_satisfied' => $isInstalled,
                'is_required' => false
            ];
        }

        // Check writeable directories
        $writeAccess = [];
        $checkDirs = [
            '/public/',
            '/public_html/',
            '/var/',
            '/',
        ];

        foreach ($checkDirs as $dir) {
            $path = realpath($this->rootDir . $dir);

            if (!$path) {
                continue;
            }

            $writeAccess[] = [
                'name' => $dir,
                'is_satisfied' => is_writable($path),
                'is_dir' => is_dir($path),
                'is_required' => true
            ];
        }


        // Check global isSatisfied value
        $isSatisfied = true;
        foreach ($config as $set) {
            if ($set['is_required'] && !$set['is_satisfied']) {
                $isSatisfied = false;
            }
        }

        foreach ($extensions as $set) {
            if ($set['is_required'] && !$set['is_satisfied']) {
                $isSatisfied = false;
            }
        }

        foreach ($writeAccess as $set) {
            if ($set['is_required'] && !$set['is_satisfied']) {
                $isSatisfied = false;
            }
        }

        return new JsonResponse([
            'is_satisfied' => $isSatisfied,
            'config' => $config,
            'ext' => $extensions,
            'write_access' => $writeAccess
        ]);
    }

    /**
     * Parse a size string to bytes.
     * 
     * @see https://stackoverflow.com/questions/13076480/php-get-actual-maximum-upload-size
     * @param string $size
     * @return int
     */
    private function parseSize($size)
    {
        // Remove the non-unit characters from the size.
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);

        // Remove the non-numeric characters from the size.
        $size = (int) preg_replace('/[^0-9\.]/', '', $size);

        if ($unit) {
            // Find the position of the unit in the ordered string which is the 
            // power of magnitude to multiply a kilobyte by.
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        } else {
            return round($size);
        }
    }
}
