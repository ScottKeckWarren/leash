<?php

use Leash\AppliedLink;
use Leash\DocumentLinker;
use Leash\Keyword;
use Leash\KeywordLinker;

function documentLinker(): DocumentLinker
{
    return new DocumentLinker(KeywordLinker::withDefaultRules());
}

it('reports no changes when no keyword matches', function () {
    $result = documentLinker()->link("Hello\n", [new Keyword('phpunit', 'u')]);

    expect($result->changed())->toBeFalse()
        ->and($result->links)->toBe([])
        ->and($result->content)->toBe("Hello\n");
});

it('links each keyword once, in keyword order', function () {
    $result = documentLinker()->link(
        "Pest and PHPUnit. Pest again, PHPUnit again.\n",
        [new Keyword('PHPUnit', 'https://x.test/p'), new Keyword('pest', 'https://x.test/pest')],
    );

    expect($result->changed())->toBeTrue()
        ->and($result->content)->toBe("[Pest](https://x.test/pest) and [PHPUnit](https://x.test/p). Pest again, PHPUnit again.\n")
        ->and(array_map(fn (AppliedLink $l) => $l->keyword, $result->links))->toBe(['PHPUnit', 'pest']);
});

it('records the URL and preview context for each link', function () {
    $link = documentLinker()->link('Use phpunit', [new Keyword('phpunit', 'u')])->links[0];

    expect($link->url)->toBe('u')
        ->and($link->original)->toBe('Use phpunit')
        ->and($link->replacement)->toBe('Use [phpunit](u)');
});

it('never links inside frontmatter', function () {
    $result = documentLinker()->link(
        "---\ntitle: phpunit tips\n---\nAll about phpunit\n",
        [new Keyword('phpunit', 'u')],
    );

    expect($result->content)->toBe("---\ntitle: phpunit tips\n---\nAll about [phpunit](u)\n");
});

it('does not let a later keyword match inside a link added by an earlier one', function () {
    $result = documentLinker()->link(
        'Read about testing',
        [new Keyword('testing', 'https://x.test/blog/'), new Keyword('blog', 'https://x.test/')],
    );

    expect($result->content)->toBe('Read about [testing](https://x.test/blog/)')
        ->and($result->links)->toHaveCount(1);
});

it('links both case variants of a duplicated keyword (known issue parity)', function () {
    $result = documentLinker()->link(
        'phpunit and PHPUnit',
        [new Keyword('phpunit', 'a'), new Keyword('PHPUnit', 'b')],
    );

    expect($result->content)->toBe('[phpunit](a) and [PHPUnit](b)');
});
