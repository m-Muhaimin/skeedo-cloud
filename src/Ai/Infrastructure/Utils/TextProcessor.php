<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Utils;

use Ai\Domain\ValueObjects\Content;

class TextProcessor
{
    /**
     * List of common special characters and control characters to remove
     */
    private static array $specialChars = [
        '\x00-\x1F', // Control characters
        '\x7F',      // DEL character
        '[\xA0]',    // Non-breaking space
        '[^\S\r\n]+', // Multiple spaces/tabs (but keep single newlines)
    ];

    /**
     * Sanitize the content by taking the first $maxWords words
     *
     * @param Content $content The content to sanitize
     * @param int $maxWords The maximum number of words to take
     * @return string The sanitized content
     */
    public static function sanitize(
        Content $content,
        int $maxWords = 100
    ): string {
        $text = $content->value;

        // Remove special characters and normalize spaces
        $pattern = '/(' . implode('|', self::$specialChars) . ')/u';
        $text = preg_replace($pattern, ' ', $text);

        // Remove multiple consecutive spaces/newlines
        $text = preg_replace('/\s+/', ' ', $text);

        // Remove URLs
        $text = preg_replace('/https?:\/\/\S+/', '', $text);

        // Remove email addresses
        $text = preg_replace('/[\w\-\.]+@([\w\-]+\.)+[\w\-]{2,4}/', '', $text);

        // Split into words using Unicode word boundaries
        $words = preg_split('/\b/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        // Filter out meaningless words and trim each word
        $words = array_filter($words, function ($word) {
            $word = trim($word);
            // Remove standalone numbers and single characters (except 'a' and 'i')
            if (is_numeric($word) || (strlen($word) === 1 && !in_array(strtolower($word), ['a', 'i']))) {
                return false;
            }
            // Remove if it's just punctuation or whitespace
            return !preg_match('/^[\p{P}\s]+$/u', $word);
        });

        // Take only the specified number of words
        $limitedWords = array_slice(array_values($words), 0, $maxWords);

        // Join the words back together
        return trim(implode(' ', $limitedWords));
    }

    /**
     * Get the system message for the title generator
     *
     * @return string The system message
     */
    public static function getSystemMessage(): string
    {
        return <<<PROMPT
You are a precise title generator. Your task is to generate a single title that perfectly matches the content's language and context.

Requirements for the title:
- Must be in the EXACT SAME LANGUAGE as the provided content
- Length: 30-64 characters
- Style: Natural language, not a list
- Format: Blog post/news article style
- No quotes, special characters, or emojis
- Proper capitalization for the target language
- Single title only, no variations

Respond ONLY with the generated title, no explanations or additional text.
PROMPT;
    }

    /**
     * Get the user message for the title generator
     *
     * @param string $content The content to generate the title for
     * @return string The user message
     */
    public static function getUserMessage(string $content): string
    {
        return <<<USER
Content to generate title for:
"""{$content}"""
USER;
    }
}
