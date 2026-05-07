<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmbedProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostEmbed extends Model
{
    protected $fillable = [
        'post_id',
        'provider',
        'url',
        'title',
        'thumbnail_url',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'provider' => EmbedProvider::class,
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function youtubeId(): ?string
    {
        if ($this->provider !== EmbedProvider::YouTube) {
            return null;
        }

        $host = strtolower((string) parse_url($this->url, PHP_URL_HOST));
        $path = (string) parse_url($this->url, PHP_URL_PATH);
        parse_str((string) parse_url($this->url, PHP_URL_QUERY), $query);

        if ($host === 'youtu.be') {
            return ltrim($path, '/') ?: null;
        }

        if (! empty($query['v'])) {
            return $query['v'];
        }

        if (preg_match('~^/(embed|shorts|live)/([^/?#]+)~', $path, $m)) {
            return $m[2];
        }

        return null;
    }

    public function vimeoId(): ?string
    {
        if ($this->provider !== EmbedProvider::Vimeo) {
            return null;
        }

        $path = (string) parse_url($this->url, PHP_URL_PATH);

        if (preg_match('~/(\d+)~', $path, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Convert a Spotify share URL into the embed-friendly form.
     * `https://open.spotify.com/track/<id>` → `https://open.spotify.com/embed/track/<id>`
     */
    public function spotifyEmbedUrl(): ?string
    {
        if ($this->provider !== EmbedProvider::Spotify) {
            return null;
        }

        $path = (string) parse_url($this->url, PHP_URL_PATH);

        if (preg_match('~^/(track|album|playlist|episode|show|artist)/([A-Za-z0-9]+)~', $path, $m)) {
            return "https://open.spotify.com/embed/{$m[1]}/{$m[2]}";
        }

        return null;
    }
}
