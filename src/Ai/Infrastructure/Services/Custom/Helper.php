<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Custom;

class Helper
{
    /**
     * Recursively searches for a 'usage' property in the given object and its 
     * nested properties.
     * 
     * Different LLM providers may return usage statistics in different locations
     * within their response objects. For example:
     * - Standard location: response->usage
     * - Groq: response->x_groq->usage
     * - Other providers might nest it differently
     * 
     * This method provides a flexible way to find the usage object regardless
     * of where it's nested in the response structure.
     *
     * @param object $item The object to search through
     * @return object|null Returns the usage object if found, null otherwise
     */
    public function findUsageObject(object $item): ?object
    {
        // If this object has a 'usage' property
        if (isset($item->usage)) {
            return $item->usage;
        }

        // Recursively search through all object properties
        foreach (get_object_vars($item) as $property) {
            if (is_object($property)) {
                $usage = $this->findUsageObject($property);
                if ($usage !== null) {
                    return $usage;
                }
            }
        }

        return null;
    }
}
