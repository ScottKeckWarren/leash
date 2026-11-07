<?php

declare(strict_types=1);

namespace Leash;

use Leash\Exceptions\KeywordsFileNotFound;
use Leash\Output\Output;

/**
 * Reads a keywords file of "keyword: url" lines.
 *
 * Lines starting with # are comments. Each line is split on its first
 * colon, so URLs containing colons are fine.
 */
final class KeywordLoader
{
    public function __construct(private readonly Output $output)
    {
    }

    /**
     * @return list<Keyword> In file order. A repeated keyword keeps its first
     *                       position but takes the last URL given for it.
     */
    public function load(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw KeywordsFileNotFound::at($filePath);
        }

        // Blank lines are dropped before numbering, so warning line numbers
        // ignore them. Kept as-is for parity with the original script.
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        /** @var array<string, string> $urls */
        $urls = [];

        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;
            $line = trim($line);

            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, ':')) {
                $this->output->write("Warning: Invalid format on line {$lineNumber}: {$line}\n");
                continue;
            }

            [$keyword, $url] = array_map('trim', explode(':', $line, 2));

            // empty() also rejects "0"; kept for parity with the original script.
            if (empty($keyword) || empty($url)) {
                $this->output->write("Warning: Empty keyword or URL on line {$lineNumber}\n");
                continue;
            }

            $urls[$keyword] = $url;
        }

        $keywords = [];

        foreach ($urls as $keyword => $url) {
            // Numeric keywords become integer array keys; cast them back.
            $keywords[] = new Keyword((string) $keyword, $url);
        }

        return $keywords;
    }
}
