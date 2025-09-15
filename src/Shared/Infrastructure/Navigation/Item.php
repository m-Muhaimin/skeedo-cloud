<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Navigation;

/**
 * Represents a navigation item with various display and routing properties.
 */
class Item
{
    /** @var string|null Optional icon identifier for the navigation item */
    public ?string $icon = null;

    /** @var string|null Optional starting gradient color for styling */
    public ?string $from = null;

    /** @var string|null Optional ending gradient color for styling */
    public ?string $to = null;

    /** @var IconType|null Optional icon type specification */
    public ?IconType $iconType = null;

    /** @var string|null Optional detailed description of the navigation item */
    public ?string $description = null;

    /** @var string|null Optional additional information about the navigation item */
    public ?string $info = null;

    /** @var array<string> Optional badges or tags to be displayed with the navigation item */
    public array $tags = [];

    /** @var bool Whether the navigation item is experimental */
    public bool $isExperimental = false;

    /** @var bool Whether the navigation item is built-in */
    public bool $isBuiltIn = true;

    /**
     * Creates a new navigation item.
     *
     * @param string $url The URL/path this navigation item links to
     * @param string $label The display name of the navigation item
     */
    public function __construct(
        public string $url,
        public string $label,
        ?string $icon = null
    ) {
        $this->setIcon($icon);
    }

    /**
     * Sets the icon for the navigation item with automatic type detection.
     * 
     * Supports multiple icon formats:
     * - 'svg:path'     - SVG icon from a path
     * - 'include:path' - Icon from an include file
     * - 'src:path'     - Icon from a source path
     * - 'icon-name'    - Tabler icon (default if no prefix)
     *
     * @param string $icon The icon identifier with optional type prefix
     * @return self Returns the current instance for method chaining
     */
    public function setIcon(?string $icon): self
    {
        if ($icon && str_starts_with($icon, 'svg:')) {
            $this->iconType = IconType::SVG;
            $this->icon = str_replace('svg:', '', $icon);
        } elseif ($icon && str_starts_with($icon, 'include:')) {
            $this->iconType = IconType::INCLUDE;
            $this->icon = str_replace('include:', '', $icon);
        } elseif ($icon && str_starts_with($icon, 'src:')) {
            $this->iconType = IconType::SRC;
            $this->icon = str_replace('src:', '', $icon);
        } elseif ($icon) {
            $this->iconType = IconType::TI;
            $this->icon = $icon;
        } else {
            $this->iconType = null;
            $this->icon = null;
        }

        return $this;
    }
}
