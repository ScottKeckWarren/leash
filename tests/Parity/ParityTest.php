<?php

/*
 * Black-box parity: run the original create-links.php and bin/leash against
 * identical copies of the same fixture and require identical exit codes,
 * stdout and resulting files.
 */

use Tests\Support\Cli;

const REFERENCE_SCRIPT = __DIR__ . '/create-links.php';
const LEASH_SCRIPT = __DIR__ . '/../../bin/leash';

function copyTree(string $from, string $to): void
{
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($from, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($items as $item) {
        $target = $to . substr($item->getPathname(), strlen($from));
        $item->isDir() ? mkdir($target, 0777, true) : copy($item->getPathname(), $target);
    }
}

/**
 * @return array<string, string> relative path => contents
 */
function snapshotTree(string $dir): array
{
    $files = [];
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));

    foreach ($items as $item) {
        $files[substr($item->getPathname(), strlen($dir) + 1)] = file_get_contents($item->getPathname());
    }

    ksort($files);

    return $files;
}

/**
 * Run both implementations on fresh copies of $fixtureDir with $args.
 *
 * @return array{reference: array, leash: array}
 */
function runBoth(string $fixtureDir, array $args): array
{
    $results = [];

    foreach (['reference' => REFERENCE_SCRIPT, 'leash' => LEASH_SCRIPT] as $name => $script) {
        $dir = makeTempDir();
        copyTree($fixtureDir, $dir);

        $results[$name] = Cli::run($script, $args, $dir) + ['files' => snapshotTree($dir)];

        removeDir($dir);
    }

    return $results;
}

function expectParity(array $results): void
{
    ['reference' => $reference, 'leash' => $leash] = $results;

    expect($leash['stderr'])->toBe($reference['stderr'])
        ->and($leash['stdout'])->toBe($reference['stdout'])
        ->and($leash['exit'])->toBe($reference['exit'])
        ->and($leash['files'])->toBe($reference['files']);
}

dataset('blog arguments', [
    'default paths' => [[]],
    'default paths, dry run' => [['--dry-run']],
    'one directory' => [['source/_scripts']],
    'one file, dry run' => [['--dry-run', 'source/_postsbyyear/2024/heuristics.md']],
    'flag after path' => [['source/_postsbyyear', '--dry-run']],
    'mixed and invalid paths' => [['source/_scripts/notes.txt', 'missing', 'source/_scripts/links.md']],
    'unknown flag' => [['--verbose']],
    'same file twice' => [['source/_scripts/intro.md', 'source/_scripts/intro.md']],
]);

it('matches the original script on the blog fixture', function (array $args) {
    $results = runBoth(__DIR__ . '/fixtures/blog', $args);

    // Guard against a vacuous pass: the fixture must actually exercise linking.
    expect($results['reference']['stdout'])->toContain('Summary:');

    expectParity($results);
})->with('blog arguments');

it('matches the original script when keywords.txt is missing', function (array $args) {
    expectParity(runBoth(__DIR__ . '/fixtures/missing-keywords', $args));
})->with([[[]], [['--dry-run', 'source/_scripts']]]);

it('matches the original script on a seeded random corpus', function (int $seed) {
    mt_srand($seed);

    $fixture = makeTempDir();
    $keywords = ['PHPUnit', 'pest', 'Code Review', 'testing', 'blog', 'CI/CD', 'docker', 'node.js', 'link'];

    file_put_contents($fixture . '/keywords.txt', implode("\n", array_map(
        fn (string $k) => "{$k}: https://example.com/" . strtolower(str_replace([' ', '/'], '-', $k)) . '/blog/',
        $keywords,
    )) . "\n");

    $tokens = [
        ...$keywords, 'PHPUNIT', 'Pest', 'code review', 'Testing', 'Blog',
        '`', '``', '```', "\n```\n", "\n~~~\n", '[', ']', '(', ')', '](https://x.test/pest)',
        '<code>', '</code>', '<code class="php">', "\n---\n", "\n", "\n\n", ' ', ' ', ' ', ', ', '. ',
        'the', 'and', 'with', 'testing123', 'pests', 'Café', '#', '*', '_',
    ];

    mkdir($fixture . '/docs');
    for ($i = 0; $i < 60; $i++) {
        $body = '';
        for ($t = 0, $n = mt_rand(5, 120); $t < $n; $t++) {
            $body .= $tokens[mt_rand(0, count($tokens) - 1)] . (mt_rand(0, 2) ? ' ' : '');
        }
        $frontmatter = mt_rand(0, 1) ? "---\ntitle: PHPUnit and pest\n---\n" : '';
        file_put_contents(sprintf('%s/docs/doc-%02d.md', $fixture, $i), $frontmatter . $body);
    }

    $results = runBoth($fixture, ['docs']);
    removeDir($fixture);

    expect($results['reference']['stdout'])->toContain('Total replacements:');
    expectParity($results);
})->with([1, 42, 1337, 2024, 9001]);
