<?php

use Leash\Markdown\Frontmatter;

it('splits YAML frontmatter from the body', function () {
    $document = Frontmatter::split("---\ntitle: Hello\n---\nBody text\n");

    expect($document->frontmatter)->toBe("---\ntitle: Hello\n---\n")
        ->and($document->body)->toBe("Body text\n");
});

it('returns the whole content as body when there is no frontmatter', function () {
    $document = Frontmatter::split("Just a body\n");

    expect($document->frontmatter)->toBe('')
        ->and($document->body)->toBe("Just a body\n");
});

it('ignores a frontmatter block that does not start the file', function () {
    $content = "Intro\n---\ntitle: x\n---\nBody\n";

    $document = Frontmatter::split($content);

    expect($document->frontmatter)->toBe('')
        ->and($document->body)->toBe($content);
});

it('normalizes trailing whitespace on the delimiter lines', function () {
    $document = Frontmatter::split("---  \ntitle: x\n---\t\nBody");

    expect($document->frontmatter)->toBe("---\ntitle: x\n---\n")
        ->and($document->body)->toBe('Body');
});

it('stops at the first closing delimiter', function () {
    $document = Frontmatter::split("---\na: 1\n---\nBody\n---\nMore\n");

    expect($document->frontmatter)->toBe("---\na: 1\n---\n")
        ->and($document->body)->toBe("Body\n---\nMore\n");
});

it('requires a newline after the closing delimiter', function () {
    $content = "---\na: 1\n---";

    expect(Frontmatter::split($content)->frontmatter)->toBe('');
});

it('joins frontmatter and body back together', function () {
    $document = Frontmatter::split("---\na: 1\n---\nBody\n");

    expect($document->join())->toBe("---\na: 1\n---\nBody\n")
        ->and($document->withBody('New')->join())->toBe("---\na: 1\n---\nNew");
});
