#!/usr/bin/env php
<?php

/**
 * Link Creator Script
 *
 * This script replaces keywords with markdown links in blog posts and scripts.
 * It only replaces the first occurrence of each keyword per file.
 *
 * Usage:
 *   php scripts/create-links.php --dry-run                 # Preview changes without modifying files
 *   php scripts/create-links.php                           # Apply changes to all posts and scripts
 *   php scripts/create-links.php path/to/post.md           # Apply changes to a single file
 *   php scripts/create-links.php --dry-run path/to/post.md # Preview changes for a single file
 *
 * Any non-flag arguments are treated as files or directories to process.
 * With none given, defaults to source/_scripts and source/_postsbyyear.
 */

class LinkCreator
{
    private array $keywords = [];
    private bool $dryRun = false;
    private int $totalReplacements = 0;
    private int $filesModified = 0;

    public function __construct(bool $dryRun = false)
    {
        $this->dryRun = $dryRun;
    }

    /**
     * Load keywords from keywords.txt file
     */
    public function loadKeywords(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new Exception("Keywords file not found: {$filePath}");
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $lineNum => $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Parse "keyword: url" format
            if (strpos($line, ':') === false) {
                echo "Warning: Invalid format on line " . ($lineNum + 1) . ": {$line}\n";
                continue;
            }

            [$keyword, $url] = array_map('trim', explode(':', $line, 2));

            if (empty($keyword) || empty($url)) {
                echo "Warning: Empty keyword or URL on line " . ($lineNum + 1) . "\n";
                continue;
            }

            $this->keywords[$keyword] = $url;
        }

        echo "Loaded " . count($this->keywords) . " keywords from {$filePath}\n\n";
    }

    /**
     * Process markdown files at the given paths (files or directories)
     */
    public function processPaths(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $this->processDirectory($path);
            } elseif (is_file($path)) {
                if (pathinfo($path, PATHINFO_EXTENSION) !== 'md') {
                    echo "Warning: Skipping non-markdown file: {$path}\n";
                    continue;
                }
                $this->processFile($path);
            } else {
                echo "Warning: Path not found: {$path}\n";
            }
        }

        echo "\n" . str_repeat('=', 80) . "\n";
        echo "Summary:\n";
        echo "  Total replacements: {$this->totalReplacements}\n";
        echo "  Files modified: {$this->filesModified}\n";

        if ($this->dryRun) {
            echo "\n** DRY RUN MODE ** No files were actually modified.\n";
            echo "Run without --dry-run to apply changes.\n";
        }
    }

    /**
     * Recursively process a directory
     */
    private function processDirectory(string $directory): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'md') {
                $this->processFile($file->getPathname());
            }
        }
    }

    /**
     * Process a single markdown file
     */
    private function processFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $originalContent = $content;

        // Split content into frontmatter and body
        $parts = $this->splitFrontmatter($content);
        $frontmatter = $parts['frontmatter'];
        $body = $parts['body'];

        // Track which keywords have been replaced in this file
        $replacedInFile = [];

        // Process each keyword
        foreach ($this->keywords as $keyword => $url) {
            $result = $this->replaceFirstOccurrence($body, $keyword, $url);

            if ($result['replaced']) {
                $body = $result['content'];
                $replacedInFile[] = [
                    'keyword' => $keyword,
                    'url' => $url,
                    'original' => $result['original'],
                    'replacement' => $result['replacement']
                ];
            }
        }

        // If replacements were made, show details and optionally save
        if (!empty($replacedInFile)) {
            $this->filesModified++;
            $relativePath = $this->getRelativePath($filePath);

            echo str_repeat('-', 80) . "\n";
            echo "File: {$relativePath}\n";
            echo str_repeat('-', 80) . "\n";

            foreach ($replacedInFile as $replacement) {
                $this->totalReplacements++;
                echo "  [{$replacement['keyword']}] -> [{$replacement['url']}]\n";
                echo "    Before: {$replacement['original']}\n";
                echo "    After:  {$replacement['replacement']}\n";
                echo "\n";
            }

            if (!$this->dryRun) {
                // Reconstruct the file with frontmatter
                $newContent = $frontmatter . $body;
                file_put_contents($filePath, $newContent);
            }
        }
    }

    /**
     * Split content into frontmatter and body
     */
    private function splitFrontmatter(string $content): array
    {
        // Check if content starts with frontmatter (---)
        if (preg_match('/^---\s*\n(.*?\n)---\s*\n(.*)/s', $content, $matches)) {
            return [
                'frontmatter' => "---\n{$matches[1]}---\n",
                'body' => $matches[2]
            ];
        }

        return [
            'frontmatter' => '',
            'body' => $content
        ];
    }

    /**
     * Replace the first occurrence of a keyword with a markdown link
     * Avoids replacements in code blocks and existing links
     */
    private function replaceFirstOccurrence(string $content, string $keyword, string $url): array
    {
        // Create a case-insensitive word boundary regex pattern
        $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';

        // Find all matches with their positions
        if (!preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return ['replaced' => false, 'content' => $content];
        }

        // Try each match until we find one that's not in a code block or link
        foreach ($matches[0] as $match) {
            $matchText = $match[0];
            $position = $match[1];

            // Check if this occurrence is in a valid position
            if ($this->isValidPosition($content, $position, strlen($matchText))) {
                // Create the replacement link, preserving the original case
                $replacement = "[{$matchText}]({$url})";

                // Replace only this occurrence
                $newContent = substr_replace($content, $replacement, $position, strlen($matchText));

                // Extract context for display
                $contextStart = max(0, $position - 20);
                $contextEnd = min(strlen($content), $position + strlen($matchText) + 20);
                $contextLength = $contextEnd - $contextStart;
                $originalContext = substr($content, $contextStart, $contextLength);
                $replacementContext = substr($newContent, $contextStart, strlen($replacement) + ($contextLength - strlen($matchText)));

                return [
                    'replaced' => true,
                    'content' => $newContent,
                    'original' => trim($originalContext),
                    'replacement' => trim($replacementContext)
                ];
            }
        }

        return ['replaced' => false, 'content' => $content];
    }

    /**
     * Check if a position in the content is valid for replacement
     * Returns false if the position is inside a code block or link
     */
    private function isValidPosition(string $content, int $position, int $length): bool
    {
        // Check if inside inline code (backticks)
        if ($this->isInsideInlineCode($content, $position)) {
            return false;
        }

        // Check if inside fenced code block
        if ($this->isInsideCodeBlock($content, $position)) {
            return false;
        }

        // Check if inside an existing link
        if ($this->isInsideLink($content, $position, $length)) {
            return false;
        }

        // Check if inside HTML code tag
        if ($this->isInsideHtmlCodeTag($content, $position)) {
            return false;
        }

        return true;
    }

    /**
     * Check if position is inside inline code (backticks)
     */
    private function isInsideInlineCode(string $content, int $position): bool
    {
        $beforePosition = substr($content, 0, $position);

        // Count backticks before the position
        $backtickCount = substr_count($beforePosition, '`');

        // If odd number of backticks, we're inside inline code
        return $backtickCount % 2 === 1;
    }

    /**
     * Check if position is inside a fenced code block
     */
    private function isInsideCodeBlock(string $content, int $position): bool
    {
        // Find all code block markers (``` or ~~~)
        preg_match_all('/^(```|~~~)/m', $content, $matches, PREG_OFFSET_CAPTURE);

        $inCodeBlock = false;
        foreach ($matches[0] as $match) {
            $markerPosition = $match[1];

            if ($markerPosition < $position) {
                $inCodeBlock = !$inCodeBlock;
            } else {
                break;
            }
        }

        return $inCodeBlock;
    }

    /**
     * Check if position is inside a markdown link
     */
    private function isInsideLink(string $content, int $position, int $length): bool
    {
        // Look backwards for an opening bracket
        $beforePosition = substr($content, 0, $position);
        $lastOpenBracket = strrpos($beforePosition, '[');

        if ($lastOpenBracket === false) {
            return false;
        }

        // Look forward for the closing of the link
        $afterMatch = substr($content, $position + $length);

        // Check if there's a ] followed by ( before any new [
        $closeBracket = strpos($content, ']', $lastOpenBracket);
        $newOpenBracket = strpos($beforePosition, '[', $lastOpenBracket + 1);

        if ($closeBracket !== false && $closeBracket > $position) {
            // We're between [ and ]
            $afterCloseBracket = substr($content, $closeBracket + 1);
            if (str_starts_with(trim($afterCloseBracket), '(')) {
                return true;
            }
        }

        // Check if we're in the URL part [text](url)
        $segment = substr($content, max(0, $position - 100), 200);
        if (preg_match('/\[[^\]]+\]\([^\)]*' . preg_quote(substr($content, $position, $length), '/') . '[^\)]*\)/', $segment)) {
            return true;
        }

        return false;
    }

    /**
     * Check if position is inside HTML code tag
     */
    private function isInsideHtmlCodeTag(string $content, int $position): bool
    {
        $beforePosition = substr($content, 0, $position);

        // Find last opening and closing code tags
        $lastOpen = strrpos($beforePosition, '<code');
        $lastClose = strrpos($beforePosition, '</code>');

        // If there's an opening tag after the last closing tag (or no closing tag), we're inside
        if ($lastOpen !== false && ($lastClose === false || $lastOpen > $lastClose)) {
            return true;
        }

        return false;
    }

    /**
     * Get relative path from project root
     */
    private function getRelativePath(string $filePath): string
    {
        $cwd = getcwd();
        if (str_starts_with($filePath, $cwd)) {
            return substr($filePath, strlen($cwd) + 1);
        }
        return $filePath;
    }
}

// Main execution
try {
    $args = array_slice($argv, 1);
    $dryRun = in_array('--dry-run', $args);
    $paths = array_values(array_filter($args, fn ($arg) => !str_starts_with($arg, '--')));

    echo "Link Creator Script\n";
    echo str_repeat('=', 80) . "\n";

    if ($dryRun) {
        echo "** DRY RUN MODE ** - No files will be modified\n\n";
    }

    $creator = new LinkCreator($dryRun);
    $creator->loadKeywords('keywords.txt');

    if (empty($paths)) {
        $paths = [
            'source/_scripts',
            'source/_postsbyyear'
        ];
    }

    $creator->processPaths($paths);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
