<?php

declare(strict_types=1);

namespace Leash;

use Leash\Output\Output;

/**
 * Writes the human-readable preview of changes and the final summary.
 */
final class Reporter
{
    private const WIDTH = 80;

    public function __construct(
        private readonly Output $output,
        private readonly string $workingDirectory,
    ) {
    }

    /**
     * @param list<AppliedLink> $links
     */
    public function fileChanged(string $filePath, array $links): void
    {
        $separator = str_repeat('-', self::WIDTH) . "\n";

        $this->output->write($separator);
        $this->output->write("File: {$this->relativePath($filePath)}\n");
        $this->output->write($separator);

        foreach ($links as $link) {
            $this->output->write("  [{$link->keyword}] -> [{$link->url}]\n");
            $this->output->write("    Before: {$link->original}\n");
            $this->output->write("    After:  {$link->replacement}\n");
            $this->output->write("\n");
        }
    }

    public function summary(int $totalReplacements, int $filesModified, bool $dryRun): void
    {
        $this->output->write("\n" . str_repeat('=', self::WIDTH) . "\n");
        $this->output->write("Summary:\n");
        $this->output->write("  Total replacements: {$totalReplacements}\n");
        $this->output->write("  Files modified: {$filesModified}\n");

        if ($dryRun) {
            $this->output->write("\n** DRY RUN MODE ** No files were actually modified.\n");
            $this->output->write("Run without --dry-run to apply changes.\n");
        }
    }

    /**
     * Strips the working directory prefix. Like the original script it does
     * not check for a path separator after the prefix.
     */
    private function relativePath(string $filePath): string
    {
        if (str_starts_with($filePath, $this->workingDirectory)) {
            return substr($filePath, strlen($this->workingDirectory) + 1);
        }

        return $filePath;
    }
}
