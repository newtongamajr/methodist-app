<?php

declare(strict_types=1);

use App\Enums\EmbedProvider;

it('detects providers from common URL forms', function (string $url, EmbedProvider $expected) {
    expect(EmbedProvider::detect($url))->toBe($expected);
})->with([
    'youtube watch' => ['https://www.youtube.com/watch?v=abc123', EmbedProvider::YouTube],
    'youtube short' => ['https://youtu.be/abc123', EmbedProvider::YouTube],
    'spotify track' => ['https://open.spotify.com/track/abc123', EmbedProvider::Spotify],
    'vimeo' => ['https://vimeo.com/123456', EmbedProvider::Vimeo],
    'unknown' => ['https://example.com/video/foo', EmbedProvider::Other],
    'malformed' => ['not-a-url', EmbedProvider::Other],
]);
