<?php

declare(strict_types=1);

namespace Leash;

/**
 * Links keywords across a set of markdown files and reports the changes.
 */
final class LinkCreator
{
    public function __construct(
        private readonly DocumentLinker $linker,
        private readonly MarkdownFinder $finder,
        private readonly Reporter $reporter,
        private readonly bool $dryRun,
    ) {
    }

    /**
     * @param list<Keyword> $keywords
     * @param list<string>  $paths    Files and/or directories.
     */
    public function run(array $keywords, array $paths): void
    {
        $totalReplacements = 0;
        $filesModified = 0;

        foreach ($this->finder->find($paths) as $filePath) {
            $document = $this->linker->link(file_get_contents($filePath), $keywords);

            if (!$document->changed()) {
                continue;
            }

            $filesModified++;
            $totalReplacements += count($document->links);
            $this->reporter->fileChanged($filePath, $document->links);

            if (!$this->dryRun) {
                file_put_contents($filePath, $document->content);
            }
        }

        $this->reporter->summary($totalReplacements, $filesModified, $this->dryRun);
    }
}
