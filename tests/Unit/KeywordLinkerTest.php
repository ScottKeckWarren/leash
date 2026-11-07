<?php

use Leash\KeywordLinker;
use Leash\Replacement;
use Leash\Rules\SkipRule;

function linker(): KeywordLinker
{
    return KeywordLinker::withDefaultRules();
}

it('returns null when the keyword does not appear', function () {
    expect(linker()->linkFirst('Nothing here', 'phpunit', 'https://x.test'))->toBeNull();
});

it('links the first occurrence and keeps its original casing', function () {
    $result = linker()->linkFirst('PHPUnit and phpunit', 'phpunit', 'https://x.test');

    expect($result)->toBeInstanceOf(Replacement::class)
        ->and($result->content)->toBe('[PHPUnit](https://x.test) and phpunit');
});

it('matches whole words only', function () {
    expect(linker()->linkFirst('phpunits and xphpunit', 'phpunit', 'u'))->toBeNull();
});

it('matches multi-word keywords', function () {
    $result = linker()->linkFirst('We use Code Review daily', 'code review', 'u');

    expect($result->content)->toBe('We use [Code Review](u) daily');
});

it('treats regex characters in keywords literally', function () {
    expect(linker()->linkFirst('nodexjs', 'node.js', 'u'))->toBeNull()
        ->and(linker()->linkFirst('Try CI/CD now', 'CI/CD', 'u')->content)->toBe('Try [CI/CD](u) now');
});

it('cannot match keywords that end in a non-word character before a space (parity)', function () {
    expect(linker()->linkFirst('C++ rocks', 'C++', 'u'))->toBeNull();
});

it('skips protected occurrences and links the next valid one', function () {
    $result = linker()->linkFirst('Run `phpunit` with phpunit', 'phpunit', 'u');

    expect($result->content)->toBe('Run `phpunit` with [phpunit](u)');
});

it('returns null when every occurrence is protected', function () {
    expect(linker()->linkFirst("```\nphpunit\n```\n<code>phpunit</code> [phpunit](x)", 'phpunit', 'u'))->toBeNull();
});

it('consults the injected rules', function () {
    $alwaysSkip = new class implements SkipRule {
        public function skips(string $content, int $position, int $length): bool
        {
            return true;
        }
    };

    expect((new KeywordLinker([$alwaysSkip]))->linkFirst('phpunit', 'phpunit', 'u'))->toBeNull()
        ->and((new KeywordLinker([]))->linkFirst('`phpunit`', 'phpunit', 'u')->content)->toBe('`[phpunit](u)`');
});

it('reports trimmed short context around the change', function () {
    $result = linker()->linkFirst('I love phpunit a lot', 'phpunit', 'u');

    expect($result->original)->toBe('I love phpunit a lot')
        ->and($result->replacement)->toBe('I love [phpunit](u) a lot');
});

it('limits context to 20 bytes either side of the match', function () {
    $content = str_repeat('a', 25) . ' phpunit ' . str_repeat('b', 25);

    $result = linker()->linkFirst($content, 'phpunit', 'u');

    expect($result->original)->toBe(str_repeat('a', 19) . ' phpunit ' . str_repeat('b', 19))
        ->and($result->replacement)->toBe(str_repeat('a', 19) . ' [phpunit](u) ' . str_repeat('b', 19));
});
