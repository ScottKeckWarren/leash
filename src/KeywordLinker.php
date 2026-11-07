<?php

declare(strict_types=1);

namespace Leash;

use Leash\Rules\FencedCodeBlockRule;
use Leash\Rules\HtmlCodeTagRule;
use Leash\Rules\InlineCodeRule;
use Leash\Rules\MarkdownLinkRule;
use Leash\Rules\SkipRule;

/**
 * Turns the first unprotected, whole-word, case-insensitive occurrence of a
 * keyword into a markdown link.
 */
final class KeywordLinker
{
    /** Bytes of context shown on each side of a match in previews. */
    private const CONTEXT = 20;

    /**
     * @param list<SkipRule> $rules A match is skipped if any rule says so.
     */
    public function __construct(private readonly array $rules)
    {
    }

    public static function withDefaultRules(): self
    {
        return new self([
            new InlineCodeRule(),
            new FencedCodeBlockRule(),
            new MarkdownLinkRule(),
            new HtmlCodeTagRule(),
        ]);
    }

    public function linkFirst(string $content, string $keyword, string $url): ?Replacement
    {
        $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';

        if (!preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        foreach ($matches[0] as [$matchText, $position]) {
            $length = strlen($matchText);

            if ($this->isProtected($content, $position, $length)) {
                continue;
            }

            $link = "[{$matchText}]({$url})";
            $newContent = substr_replace($content, $link, $position, $length);

            $contextStart = max(0, $position - self::CONTEXT);
            $contextEnd = min(strlen($content), $position + $length + self::CONTEXT);
            $contextLength = $contextEnd - $contextStart;

            return new Replacement(
                $newContent,
                trim(substr($content, $contextStart, $contextLength)),
                trim(substr($newContent, $contextStart, strlen($link) + ($contextLength - $length))),
            );
        }

        return null;
    }

    private function isProtected(string $content, int $position, int $length): bool
    {
        foreach ($this->rules as $rule) {
            if ($rule->skips($content, $position, $length)) {
                return true;
            }
        }

        return false;
    }
}
