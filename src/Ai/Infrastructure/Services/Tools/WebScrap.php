<?php


declare(strict_types=1);

namespace Ai\Infrastructure\Services\Tools;

use Ai\Infrastructure\Services\DocumentReader\DocumentReader;
use Billing\Domain\ValueObjects\CreditCount;
use Easy\Container\Attributes\Inject;
use Override;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

class WebScrap implements ToolInterface
{
    public const LOOKUP_KEY = 'web_scrap';

    public function __construct(
        private DocumentReader $reader,

        #[Inject('option.features.tools.web_scrap.is_enabled')]
        private ?bool $isEnabled = null,
    ) {}


    #[Override]
    public function isEnabled(): bool
    {
        return (bool) $this->isEnabled;
    }

    #[Override]
    public function getDescription(): string
    {
        return 'Retrieves the HTML content of a webpage at the given URL. The 
        tool will return the HTML content of the webpage as a string. It should 
        be used when the user asks for information from a webpage that is not 
        present in the AI model\'s knowledge base. Regardless of the language of 
        the scanned website content, the user\'s prompt must be answered in 
        the original language.';
    }

    #[Override]
    public function getDefinitions(): array
    {
        return [
            "type" => "object",
            "properties" => [
                "url" => [
                    "type" => "string",
                    "description" => "URL of the webpage to browse."
                ],
            ],
            "required" => ["url"]
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
        $url = $params['url'];

        return new CallResponse(
            $this->reader->readFromUrl($url, 128000) ?: '',
            new CreditCount(0)
        );
    }
}
