<?php

declare(strict_types=1);

namespace App\MediaLibrary;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * Generates paths for media library files.
 *
 * Path structure: media-library/{model}/{padded-id}/
 *
 * Numeric model ids are zero-padded to 9 chars (e.g. 42 → 000000042) so that
 * lexicographic sorting matches numeric sorting on disk.
 */
class CustomPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        $modelClass = Str::lower(class_basename($this->resolveModelType($media)));
        $modelId = $this->normalizeModelId($media->model_id);

        return "media-library/{$modelClass}/{$modelId}/";
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media).'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media).'responsive-images/';
    }

    protected function normalizeModelId(mixed $modelId): string
    {
        $modelId = (string) $modelId;

        if (is_numeric($modelId)) {
            return str_pad($modelId, 9, '0', STR_PAD_LEFT);
        }

        return $modelId;
    }

    protected function resolveModelType(Media $media): string
    {
        $modelType = $media->model_type;

        $morphedClass = Relation::getMorphedModel($modelType);
        if ($morphedClass && class_exists($morphedClass)) {
            return $morphedClass;
        }

        if (class_exists($modelType)) {
            return $modelType;
        }

        throw new RuntimeException(
            "Unable to resolve model class for media. Stored model_type: {$modelType}."
        );
    }
}
