<?php

declare(strict_types=1);

namespace Leash\Rules;

/**
 * Skips matches that are already part of a markdown link, either in the
 * link text ([text](url)) or in the URL.
 */
final class MarkdownLinkRule implements SkipRule
{
    /** Bytes before the match to include when looking for a surrounding URL. */
    private const URL_LOOKBEHIND = 100;

    /** Total bytes of the window searched for a surrounding URL. */
    private const URL_WINDOW = 200;

    public function skips(string $content, int $position, int $length): bool
    {
        $lastOpenBracket = strrpos(substr($content, 0, $position), '[');

        if ($lastOpenBracket === false) {
            return false;
        }

        return $this->isInLinkText($content, $position, $lastOpenBracket)
            || $this->isInLinkUrl($content, $position, $length);
    }

    private function isInLinkText(string $content, int $position, int $lastOpenBracket): bool
    {
        $closeBracket = strpos($content, ']', $lastOpenBracket);

        if ($closeBracket === false || $closeBracket <= $position) {
            return false;
        }

        return str_starts_with(trim(substr($content, $closeBracket + 1)), '(');
    }

    private function isInLinkUrl(string $content, int $position, int $length): bool
    {
        $segment = substr($content, max(0, $position - self::URL_LOOKBEHIND), self::URL_WINDOW);
        $match = preg_quote(substr($content, $position, $length), '/');

        return preg_match('/\[[^\]]+\]\([^\)]*' . $match . '[^\)]*\)/', $segment) === 1;
    }
}
