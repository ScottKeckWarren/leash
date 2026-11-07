<?php

declare(strict_types=1);

namespace Leash\Console;

use Exception;
use Leash\DocumentLinker;
use Leash\KeywordLinker;
use Leash\KeywordLoader;
use Leash\LinkCreator;
use Leash\MarkdownFinder;
use Leash\Output\Output;
use Leash\Reporter;

/**
 * The command line entry point: parses arguments and wires everything up.
 */
final class Application
{
    /** Read from the current working directory. */
    public const KEYWORDS_FILE = 'keywords.txt';

    /** Used when no paths are given (the original blog's directories). */
    public const DEFAULT_PATHS = [
        'source/_scripts',
        'source/_postsbyyear',
    ];

    private const DRY_RUN_FLAG = '--dry-run';

    public function __construct(private readonly Output $output)
    {
    }

    /**
     * @param list<string> $args Arguments without the script name.
     * @return int Process exit code.
     */
    public function run(array $args): int
    {
        $dryRun = in_array(self::DRY_RUN_FLAG, $args, true);
        $paths = array_values(array_filter($args, fn (string $arg) => !str_starts_with($arg, '--')));

        $this->output->write("Link Creator Script\n");
        $this->output->write(str_repeat('=', 80) . "\n");

        if ($dryRun) {
            $this->output->write("** DRY RUN MODE ** - No files will be modified\n\n");
        }

        try {
            $keywords = (new KeywordLoader($this->output))->load(self::KEYWORDS_FILE);
            $this->output->write('Loaded ' . count($keywords) . ' keywords from ' . self::KEYWORDS_FILE . "\n\n");

            $creator = new LinkCreator(
                new DocumentLinker(KeywordLinker::withDefaultRules()),
                new MarkdownFinder($this->output),
                new Reporter($this->output, (string) getcwd()),
                $dryRun,
            );

            $creator->run($keywords, $paths === [] ? self::DEFAULT_PATHS : $paths);
        } catch (Exception $e) {
            $this->output->write("Error: {$e->getMessage()}\n");

            return 1;
        }

        return 0;
    }
}
