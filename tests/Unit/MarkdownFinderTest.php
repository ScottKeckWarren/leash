<?php

use Leash\MarkdownFinder;
use Leash\Output\BufferedOutput;

beforeEach(function () {
    $this->dir = makeTempDir();
    $this->output = new BufferedOutput();
    $this->finder = new MarkdownFinder($this->output);
});

afterEach(fn () => removeDir($this->dir));

function findAll(object $test, array $paths): array
{
    return iterator_to_array($test->finder->find($paths), false);
}

it('yields a markdown file passed directly', function () {
    touch($file = $this->dir . '/post.md');

    expect(findAll($this, [$file]))->toBe([$file])
        ->and($this->output->fetch())->toBe('');
});

it('warns about and skips non-markdown files', function () {
    touch($file = $this->dir . '/notes.txt');

    expect(findAll($this, [$file]))->toBe([])
        ->and($this->output->fetch())->toBe("Warning: Skipping non-markdown file: {$file}\n");
});

it('matches the md extension case-sensitively (parity)', function () {
    touch($file = $this->dir . '/POST.MD');

    expect(findAll($this, [$file]))->toBe([]);
});

it('warns about missing paths', function () {
    expect(findAll($this, ['nope/missing']))->toBe([])
        ->and($this->output->fetch())->toBe("Warning: Path not found: nope/missing\n");
});

it('walks directories recursively for markdown files only', function () {
    mkdir($this->dir . '/a/b', 0777, true);
    touch($this->dir . '/one.md');
    touch($this->dir . '/a/b/two.md');
    touch($this->dir . '/a/skip.txt');

    $found = findAll($this, [$this->dir]);
    sort($found);

    expect($found)->toBe([$this->dir . '/a/b/two.md', $this->dir . '/one.md'])
        ->and($this->output->fetch())->toBe('');
});

it('processes paths in the order given, warning as it goes', function () {
    touch($first = $this->dir . '/first.md');
    touch($second = $this->dir . '/second.md');

    $seen = [];
    foreach ($this->finder->find([$second, 'missing', $first]) as $file) {
        $seen[] = [$file, $this->output->fetch()];
    }

    expect($seen)->toBe([
        [$second, ''],
        [$first, "Warning: Path not found: missing\n"],
    ]);
});
