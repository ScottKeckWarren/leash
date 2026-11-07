<?php

declare(strict_types=1);

namespace Leash\Rules;

/**
 * Skips matches inside fenced code blocks. Every line starting with ``` or
 * ~~~ before the match toggles the "inside a block" state.
 */
final class FencedCodeBlockRule implements SkipRule
{
    public function skips(string $content, int $position, int $length): bool
    {
        preg_match_all('/^(```|~~~)/m', $content, $matches, PREG_OFFSET_CAPTURE);

        $inCodeBlock = false;

        foreach ($matches[0] as [, $markerPosition]) {
            if ($markerPosition >= $position) {
                break;
            }

            $inCodeBlock = !$inCodeBlock;
        }

        return $inCodeBlock;
    }
}
