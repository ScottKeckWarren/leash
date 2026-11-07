<?php

declare(strict_types=1);

namespace Leash\Rules;

/**
 * Skips matches inside inline code: an odd number of backticks before the
 * match means a code span is still open.
 */
final class InlineCodeRule implements SkipRule
{
    public function skips(string $content, int $position, int $length): bool
    {
        return substr_count(substr($content, 0, $position), '`') % 2 === 1;
    }
}
