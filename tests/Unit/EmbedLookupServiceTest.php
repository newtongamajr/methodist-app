<?php

declare(strict_types=1);

use App\Services\EmbedLookupService;

function probe(): object
{
    return new class extends EmbedLookupService
    {
        public function safe(string $url): bool
        {
            return $this->isSafeUrl($url);
        }
    };
}

it('rejects unsafe URLs', function (string $url) {
    expect(probe()->safe($url))->toBeFalse();
})->with([
    'file scheme' => ['file:///etc/passwd'],
    'gopher scheme' => ['gopher://internal/'],
    'no scheme' => ['example.com/foo'],
    'empty host' => ['http:///path'],
    'malformed' => ['not-a-url'],
    'ipv4 loopback' => ['http://127.0.0.1/'],
    'ipv4 loopback alt' => ['http://127.1.2.3/'],
    'ipv4 private 10/8' => ['http://10.0.0.5/'],
    'ipv4 private 192.168/16' => ['http://192.168.1.1/'],
    'ipv4 private 172.16/12' => ['http://172.16.0.1/'],
    'ipv4 link-local' => ['http://169.254.169.254/'],
    'ipv6 loopback' => ['http://[::1]/'],
    'ipv6 unique-local' => ['http://[fc00::1]/'],
]);

it('accepts public hostnames that resolve to public IPs', function () {
    // Use a stable, low-volatility public host. If DNS is unavailable in CI,
    // skip rather than fail — this test asserts the policy, not connectivity.
    $records = @dns_get_record('one.one.one.one', DNS_A);
    if ($records === false || $records === []) {
        $this->markTestSkipped('DNS resolution unavailable.');
    }

    expect(probe()->safe('https://one.one.one.one/'))->toBeTrue();
});
