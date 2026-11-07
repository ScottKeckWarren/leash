<?php

use Leash\AppliedLink;
use Leash\Output\BufferedOutput;
use Leash\Reporter;

beforeEach(function () {
    $this->output = new BufferedOutput();
    $this->reporter = new Reporter($this->output, '/project');
});

it('prints each changed file with before and after previews', function () {
    $this->reporter->fileChanged('/project/posts/a.md', [
        new AppliedLink('PHPUnit', 'https://x.test/p', 'use PHPUnit', 'use [PHPUnit](https://x.test/p)'),
        new AppliedLink('pest', 'u', 'pest', '[pest](u)'),
    ]);

    $line = str_repeat('-', 80);

    expect($this->output->fetch())->toBe(
        "{$line}\nFile: posts/a.md\n{$line}\n"
        . "  [PHPUnit] -> [https://x.test/p]\n"
        . "    Before: use PHPUnit\n"
        . "    After:  use [PHPUnit](https://x.test/p)\n\n"
        . "  [pest] -> [u]\n"
        . "    Before: pest\n"
        . "    After:  [pest](u)\n\n"
    );
});

it('shows paths outside the working directory unchanged', function () {
    $this->reporter->fileChanged('posts/a.md', []);

    expect($this->output->fetch())->toContain("File: posts/a.md\n");
});

it('strips a working directory prefix even without a separator (parity)', function () {
    $this->reporter->fileChanged('/projectx/a.md', []);

    expect($this->output->fetch())->toContain("File: /a.md\n");
});

it('prints a summary', function () {
    $this->reporter->summary(3, 2, false);

    $line = str_repeat('=', 80);

    expect($this->output->fetch())->toBe(
        "\n{$line}\nSummary:\n  Total replacements: 3\n  Files modified: 2\n"
    );
});

it('adds a dry run notice to the summary', function () {
    $this->reporter->summary(0, 0, true);

    expect($this->output->fetch())->toEndWith(
        "  Files modified: 0\n\n** DRY RUN MODE ** No files were actually modified.\nRun without --dry-run to apply changes.\n"
    );
});
