<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Tools;

use Ai\Application\Commands\GenerateImageCommand;
use Ai\Domain\Entities\ImageEntity;
use Ai\Domain\Image\ImageServiceInterface;
use Ai\Domain\Services\AiServiceFactoryInterface;
use Billing\Domain\ValueObjects\CreditCount;
use Easy\Container\Attributes\Inject;
use Override;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

class GenerateImage implements ToolInterface
{
    public const LOOKUP_KEY = 'generate_image';
    private array $models = [];

    public function __construct(
        private Dispatcher $dispatcher,
        private AiServiceFactoryInterface $aiServiceFactory,

        #[Inject('option.features.tools.generate_image.is_enabled')]
        private ?bool $isEnabled = null,
    ) {
        foreach ($aiServiceFactory->list(ImageServiceInterface::class) as $service) {
            foreach ($service->getSupportedModels() as $model) {
                $this->models[] = $model->value;
            }
        }
    }

    #[Override]
    public function isEnabled(): bool
    {
        return (bool) $this->isEnabled && count($this->models) > 0;
    }

    #[Override]
    public function getDescription(): string
    {
        return 'Generates an image from a given prompt and model. The tool will
        return an image that is generated based on the prompt and model provided.
        It should be used when the user asks to generate an image based on the
        prompt and model. The image will be generated using the AI model. Some of 
        available models may not be accessible based on the subscirption plan.';
    }

    #[Override]
    public function getDefinitions(): array
    {
        return [
            "type" => "object",
            "properties" => [
                "prompt" => [
                    "type" => "string",
                    "description" => "The prompt to generate the image."
                ],
                "model" => [
                    "type" => "string",
                    "description" => "The AI model to use for generating the image.",
                    "enum" => $this->models
                ],
            ],
            "required" => ["prompt", "model"]
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
        $prompt = $params['prompt'];
        $model = $params['model'];

        if (!in_array($model, $this->models)) {
            $model = $this->models[0];
        }

        $cmd = new GenerateImageCommand(
            $workspace,
            $user,
            $model
        );

        $cmd->param('prompt', $prompt);

        try {
            /** @var ImageEntity */
            $image = $this->dispatcher->dispatch($cmd);
        } catch (\Throwable $th) {
            throw new CallException($th->getMessage(), previous: $th);
        }

        // The cost is already calculated in the command
        $cost = new CreditCount(0);

        return new CallResponse(
            'Here is the URL of the generated image:'
                . $image->getOutputFile()->getUrl()->value,
            $cost,
            $image
        );
    }
}
