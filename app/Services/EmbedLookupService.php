<?php

declare(strict_types=1);

namespace App\Services;

use Embed\Embed;
use Embed\Http\Crawler;
use Embed\Http\CurlClient;
use Throwable;

class EmbedLookupService
{
    public function __construct(
        protected int $connectTimeout = 5,
        protected int $totalTimeout = 8,
        protected int $maxRedirects = 3,
    ) {}

    /**
     * Resolve a URL's display metadata via oEmbed / OpenGraph.
     *
     * Returns nulls (not exceptions) for unreachable, unparseable, or unsafe
     * URLs so the caller can persist the embed regardless of network state.
     *
     * @return array{title: ?string, thumbnail_url: ?string}
     */
    public function lookup(string $url): array
    {
        if (! $this->isSafeUrl($url)) {
            return ['title' => null, 'thumbnail_url' => null];
        }

        try {
            $info = $this->makeEmbed()->get($url);

            return [
                'title' => $info->title ?: null,
                'thumbnail_url' => $info->image ? (string) $info->image : null,
            ];
        } catch (Throwable) {
            return ['title' => null, 'thumbnail_url' => null];
        }
    }

    /**
     * Reject non-http(s) schemes, empty hosts, and any host that resolves
     * to a private, reserved, or loopback address (SSRF pre-flight).
     *
     * Note: this does not defeat DNS rebinding — the underlying HTTP client
     * resolves the host again at fetch time. Rely on egress firewalling at
     * the infra layer for that residual risk.
     */
    protected function isSafeUrl(string $url): bool
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return false;
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $host = $parts['host'] ?? '';
        if ($host === '') {
            return false;
        }

        // parse_url keeps the brackets on IPv6 hosts (e.g. "[::1]"). Strip
        // them so we can validate as a plain IP literal.
        $bareHost = trim($host, '[]');
        if (filter_var($bareHost, FILTER_VALIDATE_IP)) {
            return $this->isPublicIp($bareHost);
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if ($records === false || $records === []) {
            return false;
        }

        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if ($ip === null) {
                continue;
            }
            if (! $this->isPublicIp($ip)) {
                return false;
            }
        }

        return true;
    }

    protected function isPublicIp(string $ip): bool
    {
        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    protected function makeEmbed(): Embed
    {
        $client = new CurlClient;
        $client->setSettings([
            'connect_timeout' => $this->connectTimeout,
            'timeout' => $this->totalTimeout,
            'max_redirs' => $this->maxRedirects,
        ]);

        return new Embed(new Crawler($client));
    }
}
