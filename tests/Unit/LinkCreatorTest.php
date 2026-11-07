<?php

use Leash\DocumentLinker;
use Leash\Keyword;
use Leash\KeywordLinker;
use Leash\LinkCreator;
use Leash\MarkdownFinder;
use Leash\Output\BufferedOutput;
use Leash\Reporter;

beforeEach(function () {
    $this->dir = makeTempDir();
    $this->output = new BufferedOutput();
    $this->keywords = [new Keyword('phpunit', 'https://x.test/p')];
});

afterEach(fn () => removeDir($this->dir));

function creator(object $test, bool $dryRun): LinkCreator
{
    return new LinkCreator(
        new DocumentLinker(KeywordLinker::withDefaultRules()),
        new MarkdownFinder($test->output),
        new Reporter($test->output, $test->dir),
        $dryRun,
    );
}

it('writes linked content back to changed files', function () {
    file_put_contents($file = $this->dir . '/a.md', "Use phpunit\n");

    creator($this, false)->run($this->keywords, [$file]);

    expect(file_get_contents($file))->toBe("Use [phpunit](https://x.test/p)\n")
        ->and($this->output->fetch())->toContain("File: a.md\n")
        ->and($this->output->fetch())->toContain("  Total replacements: 1\n  Files modified: 1\n")
        ->and($this->output->fetch())->not->toContain('DRY RUN');
});

it('leaves files untouched in dry run mode but still reports', function () {
    file_put_contents($file = $this->dir . '/a.md', "Use phpunit\n");

    creator($this, true)->run($this->keywords, [$file]);

    expect(file_get_contents($file))->toBe("Use phpunit\n")
        ->and($this->output->fetch())->toContain("    After:  Use [phpunit](https://x.test/p)\n")
        ->and($this->output->fetch())->toContain("  Files modified: 1\n\n** DRY RUN MODE **");
});

it('does not report or rewrite files without matches', function () {
    file_put_contents($file = $this->dir . '/a.md', "Nothing\n");
    $mtime = filemtime($file);
    touch($file, $mtime - 100);

    creator($this, false)->run($this->keywords, [$file]);

    // PHP 8.2's touch() leaves the stat cache stale, so read the real mtime.
    clearstatcache();

    expect(filemtime($file))->toBe($mtime - 100)
        ->and($this->output->fetch())->not->toContain('File:')
        ->and($this->output->fetch())->toContain("  Total replacements: 0\n  Files modified: 0\n");
});

it('totals replacements across files', function () {
    file_put_contents($a = $this->dir . '/a.md', "phpunit and pest\n");
    file_put_contents($b = $this->dir . '/b.md', "pest only\n");

    creator($this, false)->run([...$this->keywords, new Keyword('pest', 'u')], [$a, $b]);

    expect($this->output->fetch())->toContain("  Total replacements: 3\n  Files modified: 2\n");
});

it('prints the summary after any path warnings', function () {
    creator($this, true)->run($this->keywords, ['missing']);

    expect($this->output->fetch())->toStartWith("Warning: Path not found: missing\n\n" . str_repeat('=', 80));
});
