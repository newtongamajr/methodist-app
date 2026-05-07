<?php

declare(strict_types=1);

namespace App\Enums;

enum EmbedProvider: string
{
    case YouTube = 'youtube';
    case Spotify = 'spotify';
    case Vimeo = 'vimeo';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::YouTube => 'YouTube',
            self::Spotify => 'Spotify',
            self::Vimeo => 'Vimeo',
            self::Other => __('Other'),
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }

    public static function detect(string $url): self
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        $host = strtolower($host);

        return match (true) {
            str_contains($host, 'youtube.com') || str_contains($host, 'youtu.be') => self::YouTube,
            str_contains($host, 'spotify.com') => self::Spotify,
            str_contains($host, 'vimeo.com') => self::Vimeo,
            default => self::Other,
        };
    }
}
