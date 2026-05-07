<?php

declare(strict_types=1);

namespace App\MediaLibrary;

use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator;

/**
 * Generates URLs for media library files using the configured 'media' disk.
 *
 * URL structure: {app-url}/storage/media-library/{model}/{padded-id}/{filename}
 */
class CustomUrlGenerator extends DefaultUrlGenerator
{
    public function getUrl(): string
    {
        $url = Storage::disk('media')->url($this->getPathRelativeToRoot());

        return $this->versionUrl($url);
    }

    public function getPath(): string
    {
        return Storage::disk('media')->path($this->getPathRelativeToRoot());
    }

    public function getResponsiveImagesDirectoryUrl(): string
    {
        $path = $this->pathGenerator->getPathForResponsiveImages($this->media);

        return rtrim(Storage::disk('media')->url($path), '/').'/';
    }
}
