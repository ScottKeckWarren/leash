<?php

declare(strict_types=1);

namespace Leash\Rules;

/**
 * Skips matches inside an HTML <code> element: the last "<code" before the
 * match has not been closed by a later "</code>".
 */
final class HtmlCodeTagRule implements SkipRule
{
    public function skips(string $content, int $position, int $length): bool
    {
        $beforePosition = substr($content, 0, $position);

        $lastOpen = strrpos($beforePosition, '<code');
        $lastClose = strrpos($beforePosition, '</code>');

        return $lastOpen !== false && ($lastClose === false || $lastOpen > $lastClose);
    }
}
