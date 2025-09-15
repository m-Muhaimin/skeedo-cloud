<?php

declare(strict_types=1);

namespace Migrations\Update;

use Easy\Container\Attributes\Inject;
use Option\Application\Commands\DeleteOptionCommand;
use Option\Domain\Exceptions\OptionNotFoundException;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\Migrations\MigrationInterface;
use Shared\Infrastructure\Services\ModelRegistry;

class Version30000 implements MigrationInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private ModelRegistry $registry,

        #[Inject('config.dirs.root')]
        private string $root,

        #[Inject('option.features')]
        private array $features = [],

        #[Inject('option.ollama')]
        private array $ollama = [],

        #[Inject('option.llms')]
        private array $llms = [],
    ) {}

    public function up(): void
    {
        $this->models();
    }

    private function models(): void
    {
        $registry = $this->registry;

        // Fetch directory as a local variable
        $directory = $registry['directory'];

        // Models
        $models = array_merge(
            $this->features['imagine']['models'] ?? [],
            $this->features['voiceover']['models'] ?? [],
            $this->features['chat']['models'] ?? [],
            $this->features['composer']['models'] ?? [],
            $this->features['video']['models'] ?? [],
        );

        $models = array_values($models);

        // Update enabled status in local $directory
        foreach ($directory as $i => $item) {
            foreach ($item['models'] as $j => $model) {
                if (in_array($model['key'], $models)) {
                    $directory[$i]['models'][$j]['enabled'] = true;
                }
            }
        }

        // Add Ollama service
        $service = [
            'key' => 'ollama',
            'name' => 'Ollama',
            'server' => $this->ollama['server'] ?? null,
            'models' => [],
        ];

        if (isset($this->ollama['models'])) {
            foreach ($this->ollama['models'] ?? [] as $m) {
                $model = [
                    'type' => 'llm',
                    'key' => $m['key'],
                    'name' => $m['name'],
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
                        ]
                    ],
                    'config' => [
                        'stream' => true,
                        'vision' => (bool) ($model['vision'] ?? false),
                        'tools' => (bool) ($model['tools'] ?? false),
                        'titler' => true,
                        'writer' => true,
                        'coder' => true,
                    ]
                ];

                if (in_array($model['key'], $models)) {
                    $model['enabled'] = true;
                }

                $service['models'][] = $model;
            }
        }

        // Add Ollama service to local $directory
        $directory[] = $service;

        // Add custom LLM services
        foreach ($this->llms as $key => $llm) {
            $service = [
                'key' => $key,
                'name' => $llm['name'],
                'server' => $llm['server'],
                'api_key' => $llm['key'],
                'custom' => true,
                'headers' => [],
                'models' => [],
            ];

            foreach ($llm['headers'] ?? [] as $header) {
                $service['headers'][] = [
                    'key' => $header['key'],
                    'value' => $header['value'],
                ];
            }

            foreach ($llm['models'] ?? [] as $m) {
                $model = [
                    'type' => 'llm',
                    'key' => $m['key'],
                    'name' => $m['name'],
                    'rates' => [
                        [
                            'key' => $m['key'] . '-input',
                            'type' => 'input',
                            'unit' => 'token',
                        ],
                        [
                            'key' => $m['key'] . '-output',
                            'type' => 'output',
                            'unit' => 'token',
                        ],
                    ],
                    'config' => [
                        'stream' => true,
                        'vision' => (bool) ($m['vision'] ?? false),
                        'tools' => (bool) ($m['tools'] ?? false),
                        'titler' => true,
                        'writer' => true,
                        'coder' => true,
                    ]
                ];

                if (in_array($model['key'], $models)) {
                    $model['enabled'] = true;
                }

                $service['models'][] = $model;
            }

            // Add custom LLM service to local $directory
            $directory[] = $service;
        }

        // Assign modified $directory back to registry
        $registry['directory'] = $directory;

        $this->registry->save();

        // Clean up options
        $keys = [
            'features.imagine.models',
            'features.voiceover.models',
            'features.chat.models',
            'features.composer.models',
            'features.video.models',

            'ollama',
            'llms',
        ];

        foreach ($keys as $key) {
            try {
                $cmd = new DeleteOptionCommand($key);
                $this->dispatcher->dispatch($cmd);
            } catch (OptionNotFoundException $th) {
                //throw $th;
            }
        }
    }
}
