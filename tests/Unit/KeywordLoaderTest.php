<?php

use Leash\Exceptions\KeywordsFileNotFound;
use Leash\Keyword;
use Leash\KeywordLoader;
use Leash\Output\BufferedOutput;

beforeEach(function () {
    $this->dir = makeTempDir();
    $this->output = new BufferedOutput();
});

afterEach(fn () => removeDir($this->dir));

function loadKeywords(object $test, string $contents): array
{
    file_put_contents($test->dir . '/keywords.txt', $contents);

    $keywords = (new KeywordLoader($test->output))->load($test->dir . '/keywords.txt');

    return array_map(fn (Keyword $k) => [$k->keyword, $k->url], $keywords);
}

it('throws when the file does not exist', function () {
    (new KeywordLoader($this->output))->load($this->dir . '/missing.txt');
})->throws(KeywordsFileNotFound::class, 'Keywords file not found: ');

it('includes the path in the missing file message', function () {
    $exception = KeywordsFileNotFound::at('some/keywords.txt');

    expect($exception)->toBeInstanceOf(Exception::class)
        ->and($exception->getMessage())->toBe('Keywords file not found: some/keywords.txt');
});

it('parses trimmed keyword and URL pairs in file order', function () {
    expect(loadKeywords($this, "  PHPUnit :  https://x.test/phpunit  \nCode Review: https://x.test/cr\n"))
        ->toBe([['PHPUnit', 'https://x.test/phpunit'], ['Code Review', 'https://x.test/cr']]);
});

it('splits on the first colon only', function () {
    expect(loadKeywords($this, "CI: https://x.test:8080/ci\n"))->toBe([['CI', 'https://x.test:8080/ci']]);
});

it('skips comments, blank and whitespace-only lines silently', function () {
    expect(loadKeywords($this, "# comment\n\n   \n  # indented: comment\nA: u\n"))->toBe([['A', 'u']])
        ->and($this->output->fetch())->toBe('');
});

it('warns about lines without a colon', function () {
    expect(loadKeywords($this, "A: u\nnot valid\n"))->toBe([['A', 'u']])
        ->and($this->output->fetch())->toBe("Warning: Invalid format on line 2: not valid\n");
});

it('numbers warning lines without counting blank lines (parity)', function () {
    loadKeywords($this, "A: u\n\n\nnot valid\n");

    expect($this->output->fetch())->toBe("Warning: Invalid format on line 2: not valid\n");
});

it('warns about empty keywords or URLs', function () {
    expect(loadKeywords($this, ": u\nA:\nB:   \n"))->toBe([])
        ->and($this->output->fetch())->toBe(
            "Warning: Empty keyword or URL on line 1\n"
            . "Warning: Empty keyword or URL on line 2\n"
            . "Warning: Empty keyword or URL on line 3\n"
        );
});

it('treats a keyword or URL of "0" as empty (parity)', function () {
    expect(loadKeywords($this, "0: u\nA: 0\n"))->toBe([])
        ->and($this->output->fetch())->toBe(
            "Warning: Empty keyword or URL on line 1\nWarning: Empty keyword or URL on line 2\n"
        );
});

it('lets a later duplicate override the URL but keep the original position', function () {
    expect(loadKeywords($this, "A: one\nB: two\nA: three\n"))->toBe([['A', 'three'], ['B', 'two']]);
});

it('keeps keywords that differ only in case as separate entries', function () {
    expect(loadKeywords($this, "phpunit: a\nPHPUnit: b\n"))->toBe([['phpunit', 'a'], ['PHPUnit', 'b']]);
});

it('keeps numeric keywords as strings', function () {
    expect(loadKeywords($this, "2024: https://x.test/2024\n"))->toBe([['2024', 'https://x.test/2024']]);
});

it('silently skips a line containing only "0" (parity)', function () {
    expect(loadKeywords($this, "0\nA: u\n"))->toBe([['A', 'u']])
        ->and($this->output->fetch())->toBe('');
});
