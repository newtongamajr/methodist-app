<?php

declare(strict_types=1);

use App\MediaLibrary\CustomPathGenerator;
use App\Models\Post;
use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

it('zero-pads numeric model ids to 9 chars', function () {
    $media = new Media;
    $media->model_type = User::class;
    $media->model_id = 42;

    $path = (new CustomPathGenerator)->getPath($media);

    expect($path)->toBe('media-library/user/000000042/');
});

it('preserves non-numeric model ids verbatim', function () {
    $uuid = '550e8400-e29b-41d4-a716-446655440000';

    $media = new Media;
    $media->model_type = User::class;
    $media->model_id = $uuid;

    $path = (new CustomPathGenerator)->getPath($media);

    expect($path)->toBe("media-library/user/{$uuid}/");
});

it('lowercases the model basename in the path', function () {
    $media = new Media;
    $media->model_type = Post::class;
    $media->model_id = 7;

    $path = (new CustomPathGenerator)->getPath($media);

    expect($path)->toBe('media-library/post/000000007/');
});

it('appends conversions and responsive-images subpaths', function () {
    $media = new Media;
    $media->model_type = User::class;
    $media->model_id = 1;

    $generator = new CustomPathGenerator;

    expect($generator->getPathForConversions($media))
        ->toBe('media-library/user/000000001/conversions/')
        ->and($generator->getPathForResponsiveImages($media))
        ->toBe('media-library/user/000000001/responsive-images/');
});
