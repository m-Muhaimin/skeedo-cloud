<?php

declare(strict_types=1);

namespace Preset\Domain\Placeholder;

use JsonSerializable;

/**
 * Interface PlaceholderInterface
 * 
 * This interface represents a placeholder object that can be serialized to JSON.
 * It extends the JsonSerializable interface and defines a method for JSON serialization.
 */
interface PlaceholderInterface extends JsonSerializable
{
    /**
     * Serializes the placeholder object to JSON.
     * 
     * @return array The serialized representation of the placeholder object as 
     * an associative array.
     */
    public function jsonSerialize(): array;
}
