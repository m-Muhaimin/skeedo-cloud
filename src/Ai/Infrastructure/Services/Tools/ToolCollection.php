<?php

namespace Ai\Infrastructure\Services\Tools;

use Ai\Domain\Entities\MessageEntity;
use IteratorAggregate;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Traversable;

/**
 * @implements IteratorAggregate<string,ToolInterface>
 */
class ToolCollection implements IteratorAggregate
{
    /** @var array<string,ToolInterface> */
    private array $tools = [];

    public function __construct(
        private ContainerInterface $container,
    ) {}

    /**
     * Add a tool to the collection
     * 
     * @param string $key The key to use for the tool
     * @param string|ToolInterface $tool The tool or the class name of the tool
     * @return static $this
     */
    public function add(string $key, string|ToolInterface $tool): static
    {
        $this->tools[$key] = $tool;
        return $this;
    }

    /**
     * Find a tool by key
     * 
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function find(string $key): ?ToolInterface
    {
        if (!isset($this->tools[$key])) {
            return null;
        }

        $tool = $this->tools[$key];

        if (is_string($tool)) {
            $tool = $this->container->get($tool);
        }

        if ($tool instanceof ToolInterface) {
            return $tool;
        }

        return null;
    }

    /**
     * Iterate over the tools
     * 
     * @return Traversable<string,ToolInterface>
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Override]
    public function getIterator(): Traversable
    {
        foreach ($this->tools as $key => $tool) {
            if (is_string($tool)) {
                $tool = $this->container->get($tool);
            }

            if (!($tool instanceof ToolInterface)) {
                continue;
            }

            $this->tools[$key] = $tool;
            yield $key => $tool;
        }
    }

    /**
     * Iterate over the tools that are enabled for the given message
     * 
     * @return Traversable<ToolInterface>
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function getToolsForMessage(MessageEntity $message): Traversable
    {
        $sub = $message->getConversation()->getWorkspace()->getSubscription();
        $user = $message->getUser();

        if (!$sub) {
            yield from [];
            return;
        }

        $plan = $sub->getPlan();
        $config = $plan->getConfig();

        foreach ($this as $key => $tool) {
            if (!$tool->isEnabled()) {
                continue;
            }

            if (isset($config->tools[$key]) && $config->tools[$key]) {
                yield $key => $tool;
            }

            if ($key === KeyFacts::LOOKUP_KEY) {
                // Key facts is always enabled internally
                yield $key => $tool;
            }

            if (
                in_array($key, [GetMemory::LOOKUP_KEY, SaveMemory::LOOKUP_KEY])
                && $user->getPreferences()->memories
                && isset($config->tools['memory'])
                && $config->tools['memory']
            ) {
                yield $key => $tool;
            }

            if (
                $key === ChatHistory::LOOKUP_KEY
                && $user->getPreferences()->history
                && isset($config->tools['memory'])
                && $config->tools['memory']
            ) {
                yield $key => $tool;
            }

            if (
                $key === KnowledgeBase::LOOKUP_KEY
                && $message->getAssistant()?->hasDataset()
            ) {
                yield $key => $tool;
            }
        }
    }
}
