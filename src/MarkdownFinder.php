<?php

declare(strict_types=1);

namespace Leash;

use Leash\Output\Output;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Expands files and directories into the markdown files to process.
 *
 * Files are yielded lazily so that warnings appear in the output at the
 * point the offending path is reached.
 */
final class MarkdownFinder
{
    private const EXTENSION = 'md';

    public function __construct(private readonly Output $output)
    {
    }

    /**
     * @param list<string> $paths
     * @return iterable<string>
     */
    public function find(array $paths): iterable
    {
        foreach ($paths as $path) {
            if (is_dir($path)) {
                yield from $this->walk($path);
            } elseif (is_file($path)) {
                if (pathinfo($path, PATHINFO_EXTENSION) !== self::EXTENSION) {
                    $this->output->write("Warning: Skipping non-markdown file: {$path}\n");
                    continue;
                }

                yield $path;
            } else {
                $this->output->write("Warning: Path not found: {$path}\n");
            }
        }
    }

    /**
     * @return iterable<string>
     */
    private function walk(string $directory): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() === self::EXTENSION) {
                yield $file->getPathname();
            }
        }
    }
}
