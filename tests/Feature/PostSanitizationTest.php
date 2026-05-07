<?php

declare(strict_types=1);

use App\Models\Post;

it('strips script tags from body on save', function () {
    $post = Post::factory()->create([
        'body' => '<p>Hello</p><script>alert("xss")</script>',
    ]);

    expect($post->fresh()->body)
        ->not->toContain('<script>')
        ->not->toContain('alert(')
        ->toContain('Hello');
});

it('strips event handlers from body on save', function () {
    $post = Post::factory()->create([
        'body' => '<p onclick="alert(1)">Click</p><img src=x onerror="alert(2)">',
    ]);

    $body = $post->fresh()->body;

    expect($body)
        ->not->toContain('onclick')
        ->not->toContain('onerror')
        ->toContain('Click');
});

it('strips javascript: URLs from anchors', function () {
    $post = Post::factory()->create([
        'body' => '<a href="javascript:alert(1)">link</a>',
    ]);

    expect($post->fresh()->body)->not->toContain('javascript:');
});

it('keeps safe rich-text markup in body', function () {
    $html = '<h2>Title</h2>'
        .'<p><strong>Bold</strong> and <em>italic</em>.</p>'
        .'<ul><li>one</li><li>two</li></ul>'
        .'<a href="https://example.com" target="_blank">external</a>';

    $post = Post::factory()->create(['body' => $html]);
    $body = $post->fresh()->body;

    expect($body)
        ->toContain('<h2>')
        ->toContain('<strong>')
        ->toContain('<em>')
        ->toContain('<ul>')
        ->toContain('href="https://example.com"');
});

it('auto-adds rel="noopener noreferrer" on target="_blank" anchors', function () {
    $post = Post::factory()->create([
        'body' => '<a href="https://example.com" target="_blank">x</a>',
    ]);

    $body = $post->fresh()->body;

    expect($body)
        ->toContain('noopener')
        ->toContain('noreferrer');
});

it('restricts iframes to known media providers', function () {
    $post = Post::factory()->create([
        'body' => '<iframe src="https://evil.example.com/x"></iframe>'
            .'<iframe src="https://www.youtube.com/embed/abc"></iframe>',
    ]);

    $body = $post->fresh()->body;

    expect($body)
        ->not->toContain('evil.example.com')
        ->toContain('youtube.com/embed/abc');
});

it('strips script tags from excerpt on save', function () {
    $post = Post::factory()->create([
        'excerpt' => 'Summary <script>alert(1)</script> here',
    ]);

    expect($post->fresh()->excerpt)
        ->not->toContain('<script>')
        ->toContain('Summary')
        ->toContain('here');
});

it('strips disallowed tags from excerpt', function () {
    $post = Post::factory()->create([
        'excerpt' => '<p>Hi</p><table><tr><td>x</td></tr></table><h1>big</h1>',
    ]);

    $excerpt = $post->fresh()->excerpt;

    expect($excerpt)
        ->not->toContain('<table')
        ->not->toContain('<h1>')
        ->toContain('Hi');
});
