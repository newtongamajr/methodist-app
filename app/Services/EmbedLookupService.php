<?php

declare(strict_types=1);

namespace App\Services;

use Embed\Embed;
use Throwable;

class EmbedLookupService
{
    /**
     * Resolve a URL's display metadata via oEmbed / OpenGraph.
     *
     * Returns nulls (not exceptions) for unreachable or unparseable URLs so
     * the caller can persist the embed regardless of network availability.
     *
     * @return array{title: ?string, thumbnail_url: ?string}
     */
    public function lookup(string $url): array
    {
        try {
            $info = (new Embed)->get($url);

            return [
                'title' => $info->title ?: null,
                'thumbnail_url' => $info->image ? (string) $info->image : null,
            ];
        } catch (Throwable) {
            return ['title' => null, 'thumbnail_url' => null];
        }
    }
}
