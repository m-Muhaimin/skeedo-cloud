<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Navigation;

use ArrayAccess;
use Gettext\Translator;
use Gettext\TranslatorFunctions;
use JsonSerializable;
use Override;

class Registry implements ArrayAccess, JsonSerializable
{
    public array $items = [];

    public function __construct()
    {
        $translator = new Translator();
        TranslatorFunctions::register($translator);

        // Once priority option is implemented, 
        // we can move this to the ViewMiddleware.
        Helper::applyDefaults($this);
    }

    public function section(
        string $key,
        ?string $label = null,
        ?bool $force = null
    ): self {
        $this->addSection($key, $label, $force);
        return $this;
    }

    public function item(
        string $target,
        Item $item
    ): self {
        // Ensure the section exists
        $current = &$this->addSection($target);
        $current['items'][] = $item;

        return $this;
    }

    #[Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    #[Override]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->items[$offset] = $value;
    }

    #[Override]
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->items;
    }

    /**
     * Adds or retrieves a navigation section based on the provided key.
     * 
     * The key can contain dots to indicate nested sections (e.g. 'app.primary').
     * If the section doesn't exist, it will be created along with any missing 
     * parent sections.
     * 
     * Since this method returns a reference, any modifications made to the 
     * returned array will directly affect the internal navigation structure.
     * 
     * @param string $key The section key/path (e.g. 'app.primary', 'admin.billing')
     * @param null|string $label Optional name for the section
     * @param null|bool $force If true, forces setting the name even if null
     * @return array& Reference to the created/existing section
     */
    private function &addSection(
        string $key,
        ?string $label = null,
        ?bool $force = null
    ): array {
        // Split the key by dots to get the path segments
        $segments = explode('.', $key);

        // Reference to the current level in the items array
        $current = &$this->items;

        // Navigate through the segments to find or create the target section
        foreach ($segments as $segment) {
            if (!isset($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }

        // Set the name if provided
        if ($label !== null || $force === true) {
            $current['label'] = $label;
        }

        // Ensure 'items' array exists
        if (!isset($current['items'])) {
            $current['items'] = [];
        }

        // Ensure 'label' is set
        if (!isset($current['label'])) {
            $current['label'] = null;
        }

        return $current;
    }
}
