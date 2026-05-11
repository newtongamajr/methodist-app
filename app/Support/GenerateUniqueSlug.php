<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Iteratively pick a slug that is not yet taken inside the given query scope.
 *
 * The query is expected to already exclude the current record (e.g. via
 * whereKeyNot($id)) so updating an existing row doesn't collide with itself.
 *
 * Falls back to a random 8-char string if the source name produces an empty
 * slug (e.g. all non-alphanumeric input).
 */
class GenerateUniqueSlug
{
    public function __invoke(string $name, Builder $scope, string $column = 'slug'): string
    {
        $base = Str::slug($name) ?: Str::lower(Str::random(8));
        $candidate = $base;
        $i = 1;

        while ((clone $scope)->where($column, $candidate)->exists()) {
            $candidate = $base.'-'.++$i;
        }

        return $candidate;
    }
}
