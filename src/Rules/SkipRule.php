<?php

declare(strict_types=1);

namespace Leash\Rules;

/**
 * Decides whether a keyword match must be left alone.
 */
interface SkipRule
{
    /**
     * @param string $content  The full markdown body being searched.
     * @param int    $position Byte offset of the match within $content.
     * @param int    $length   Byte length of the match.
     */
    public function skips(string $content, int $position, int $length): bool;
}
