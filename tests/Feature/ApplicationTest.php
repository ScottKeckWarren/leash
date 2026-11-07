<?php

use Leash\Console\Application;
use Leash\Output\BufferedOutput;

beforeEach(function () {
    $this->previousDir = getcwd();
    $this->dir = realpath(makeTempDir());
    chdir($this->dir);
    $this->output = new BufferedOutput();
});

afterEach(function () {
    chdir($this->previousDir);
    removeDir($this->dir);
});

function runApp(object $test, array $args): int
{
    return (new Application($test->output))->run($args);
}

const HEADER = "Link Creator Script\n================================================================================\n";

it('fails with an error when keywords.txt is missing', function () {
    expect(runApp($this, []))->toBe(1)
        ->and($this->output->fetch())->toBe(HEADER . "Error: Keywords file not found: keywords.txt\n");
});

it('links the given paths and exits successfully', function () {
    file_put_contents('keywords.txt', "phpunit: https://x.test/p\n");
    file_put_contents('post.md', "Use phpunit\n");

    expect(runApp($this, ['post.md']))->toBe(0)
        ->and(file_get_contents('post.md'))->toBe("Use [phpunit](https://x.test/p)\n")
        ->and($this->output->fetch())->toStartWith(HEADER . "Loaded 1 keywords from keywords.txt\n\n" . str_repeat('-', 80) . "\nFile: post.md\n");
});

it('prints keyword warnings before the loaded count', function () {
    file_put_contents('keywords.txt', "bad line\nphpunit: u\n");

    runApp($this, ['nothing']);

    expect($this->output->fetch())->toStartWith(
        HEADER . "Warning: Invalid format on line 1: bad line\nLoaded 1 keywords from keywords.txt\n\n"
    );
});

it('previews without writing in dry run mode', function () {
    file_put_contents('keywords.txt', "phpunit: u\n");
    file_put_contents('post.md', "Use phpunit\n");

    expect(runApp($this, ['--dry-run', 'post.md']))->toBe(0)
        ->and(file_get_contents('post.md'))->toBe("Use phpunit\n")
        ->and($this->output->fetch())->toStartWith(HEADER . "** DRY RUN MODE ** - No files will be modified\n\nLoaded 1 keywords")
        ->and($this->output->fetch())->toEndWith("Run without --dry-run to apply changes.\n");
});

it('accepts the dry run flag after paths', function () {
    file_put_contents('keywords.txt', "phpunit: u\n");
    file_put_contents('post.md', "Use phpunit\n");

    runApp($this, ['post.md', '--dry-run']);

    expect(file_get_contents('post.md'))->toBe("Use phpunit\n");
});

it('ignores unknown flags', function () {
    file_put_contents('keywords.txt', "phpunit: u\n");
    file_put_contents('post.md', "Use phpunit\n");

    expect(runApp($this, ['--verbose', 'post.md']))->toBe(0)
        ->and(file_get_contents('post.md'))->toBe("Use [phpunit](u)\n")
        ->and($this->output->fetch())->not->toContain('--verbose');
});

it('falls back to the blog directories when no paths are given', function () {
    file_put_contents('keywords.txt', "phpunit: u\n");
    mkdir('source/_scripts', 0777, true);
    mkdir('source/_postsbyyear/2024', 0777, true);
    file_put_contents('source/_scripts/s.md', "phpunit\n");
    file_put_contents('source/_postsbyyear/2024/p.md', "phpunit\n");

    runApp($this, []);

    expect(file_get_contents('source/_scripts/s.md'))->toBe("[phpunit](u)\n")
        ->and(file_get_contents('source/_postsbyyear/2024/p.md'))->toBe("[phpunit](u)\n")
        ->and($this->output->fetch())->toContain("Files modified: 2\n");
});

it('warns when the default directories are missing', function () {
    file_put_contents('keywords.txt', "phpunit: u\n");

    runApp($this, []);

    expect($this->output->fetch())->toContain(
        "Warning: Path not found: source/_scripts\nWarning: Path not found: source/_postsbyyear\n"
    );
});
