<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Exceptions\InvalidBase64Data;
use Spatie\Permission\Exceptions\UnauthorizedException;

it('does not report authorization exceptions', function () {
    Exceptions::fake();

    report(new AuthorizationException('not allowed'));

    Exceptions::assertNotReported(AuthorizationException::class);
});

it('does not report Spatie permission UnauthorizedException', function () {
    Exceptions::fake();

    report(UnauthorizedException::forRoles(['global_manager']));

    Exceptions::assertNotReported(UnauthorizedException::class);
});

it('downgrades MediaLibrary FileCannotBeAdded to a warning log', function () {
    Log::spy();

    report(InvalidBase64Data::create());

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('MediaLibrary rejected an upload', Mockery::on(fn ($ctx) => str_contains($ctx['message'] ?? '', 'base64')));
});
