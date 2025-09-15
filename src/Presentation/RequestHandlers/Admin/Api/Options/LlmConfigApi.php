<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Options;

use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Response\EmptyResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\Services\ModelRegistry;

#[Route(path: '/llms/[ollama:id]', method: RequestMethod::POST)]
#[Route(path: '/llms/[uuid:id]', method: RequestMethod::POST)]
class LlmConfigApi extends OptionsApi
implements RequestHandlerInterface
{
    public function __construct(
        private ModelRegistry $registry,

        #[Inject('config.dirs.root')]
        private string $root,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        $config = json_decode(json_encode($request->getParsedBody()), true);

        $models = $config['models'] ?? [];
        $headers = $config['headers'] ?? [];
        $name = $id == 'ollama' ? 'Ollama' : ($config['name'] ?? $id);
        $apiKey = $config['api_key'] ?? null;
        $server = $config['server'] ?? null;

        $registry = $this->registry;

        // Fetch directory, modify, then set back to avoid indirect modification notice
        $directory = $registry['directory'];

        // Find or create the LLM service
        $index = null;
        foreach ($directory as $i => $service) {
            if ($service['key'] === $id) {
                $index = $i;
                break;
            }
        }

        if ($index === null) {
            $index = count($directory);
            $service = [
                'key' => $id,
                'name' => $name,
                'custom' => true,
                'models' => [],
            ];

            if ($id !== 'ollama') {
                $service['custom'] = true;
            }

            $directory[] = $service;
        }

        $directory[$index]['name'] = $name;

        if ($server) {
            $directory[$index]['server'] = $server;
        }

        if ($apiKey) {
            $directory[$index]['api_key'] = $apiKey;
        }

        $directory[$index]['headers'] = [];
        foreach ($headers as $header) {
            $directory[$index]['headers'][] = [
                'key' => $header['key'],
                'value' => $header['value'],
            ];
        }

        // Build a map of models from payload for easy lookup
        $payloadModels = [];
        foreach ($models as $model) {
            $payloadModels[trim($model['key'])] = $model;
        }

        // Update or add models in registry
        $existingModels = [];
        foreach ($directory[$index]['models'] as $model) {
            $existingModels[trim($model['key'])] = $model;
        }

        // Add or update models from payload, preserving rates if present in existing model
        $updatedModels = [];
        foreach ($payloadModels as $key => $model) {
            $existing = $existingModels[$key] ?? [];
            $modelData = [
                'type' => 'llm',
                'key' => trim($model['key']),
                'name' => $model['name'] ?? '',
                'rates' => [
                    [
                        'key' => $model['key'] . '-input',
                        'type' => 'input',
                        'unit' => 'token',
                    ],
                    [
                        'key' => $model['key'] . '-output',
                        'type' => 'output',
                        'unit' => 'token',
                    ],
                ],
                'config' => [
                    'stream' => true,
                    'vision' => (bool)($model['config']['vision'] ?? false),
                    'tools' => (bool)($model['config']['tools'] ?? false),
                    'titler' => true,
                    'writer' => true,
                    'coder' => true,
                    'provider' => $model['config']['provider'] ?? null,
                ],
            ];

            if (isset($model['provider']['name'])) {
                $modelData['provider'] = [
                    'name' => $model['provider']['name']
                ];
            }

            $updatedModels[] = array_replace_recursive($existing, $modelData);
        }

        // Save only models present in payload (removes others)
        $directory[$index]['models'] = $updatedModels;

        // Set the modified directory back to the registry
        $registry['directory'] = $directory;
        $this->registry->save();

        return new EmptyResponse();
    }
}
