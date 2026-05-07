<?php

declare(strict_types=1);

use App\Enums\EmbedProvider;
use App\Models\PostEmbed;

function makeEmbed(EmbedProvider $provider, string $url): PostEmbed
{
    $embed = new PostEmbed;
    $embed->provider = $provider;
    $embed->url = $url;

    return $embed;
}

it('extracts youtube ids from various url forms', function (string $url, ?string $expected) {
    expect(makeEmbed(EmbedProvider::YouTube, $url)->youtubeId())->toBe($expected);
})->with([
    'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
    'short' => ['https://youtu.be/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
    'embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
    'shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
    'live' => ['https://www.youtube.com/live/dQw4w9WgXcQ?feature=share', 'dQw4w9WgXcQ'],
    'unknown' => ['https://www.youtube.com/', null],
]);

it('extracts vimeo ids', function () {
    expect(makeEmbed(EmbedProvider::Vimeo, 'https://vimeo.com/123456789')->vimeoId())->toBe('123456789');
    expect(makeEmbed(EmbedProvider::Vimeo, 'https://vimeo.com/channels/foo/123456789')->vimeoId())->toBe('123456789');
});

it('builds the spotify embed url for tracks, albums, and playlists', function (string $url, string $expected) {
    expect(makeEmbed(EmbedProvider::Spotify, $url)->spotifyEmbedUrl())->toBe($expected);
})->with([
    'track' => ['https://open.spotify.com/track/4iV5W9uYEdYUVa79Axb7Rh', 'https://open.spotify.com/embed/track/4iV5W9uYEdYUVa79Axb7Rh'],
    'album' => ['https://open.spotify.com/album/2nKQ1sUuM7zRq6F11kZBlT?si=foo', 'https://open.spotify.com/embed/album/2nKQ1sUuM7zRq6F11kZBlT'],
    'playlist' => ['https://open.spotify.com/playlist/37i9dQZF1DXcBWIGoYBM5M', 'https://open.spotify.com/embed/playlist/37i9dQZF1DXcBWIGoYBM5M'],
]);

it('returns null for non-matching provider on each helper', function () {
    $vimeo = makeEmbed(EmbedProvider::Vimeo, 'https://vimeo.com/12345');

    expect($vimeo->youtubeId())->toBeNull()
        ->and($vimeo->spotifyEmbedUrl())->toBeNull();
});
