<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * A post is viewable to anyone if any of its post_scopes rows match
     * the visibility check below. Owner / admin always sees their own.
     */
    public function view(?User $user, Post $post): bool
    {
        $scopes = $post->scopes;

        // National posts are visible to everyone, including guests.
        if ($scopes->contains(fn ($s) => (bool) $s->national_post)) {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($user->id === $post->author_id || $user->can('posts.update.any')) {
            return true;
        }

        // Pull the user's primary church row (if any) for the membership match.
        $primary = $user->churches()->wherePivot('is_primary', true)->first();
        $churchId = $primary?->id;
        $districtId = $primary?->pivot?->district_id;
        $regionId = $primary?->pivot?->region_id;

        foreach ($scopes as $s) {
            if ($s->church_id && $churchId && $s->church_id === $churchId) {
                return true;
            }
            if ($s->district_id && ! $s->church_id && $districtId && $s->district_id === $districtId) {
                return true;
            }
            if ($s->region_id && ! $s->district_id && ! $s->church_id && $regionId && $s->region_id === $regionId) {
                return true;
            }
        }

        // Admins can see any post that targets a scope they manage.
        if ($user->isAdminUser()) {
            foreach ($scopes as $s) {
                if ($s->church_id && $user->canManageChurch($s->church_id)) {
                    return true;
                }
                if ($s->district_id && ! $s->church_id && $user->canManageDistrict($s->district_id)) {
                    return true;
                }
                if ($s->region_id && ! $s->district_id && ! $s->church_id && $user->canManageRegion($s->region_id)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('posts.create.shared') || $user->can('posts.create.local');
    }

    /**
     * Authors can update their own posts. Anyone with the global "update
     * any" permission can update any post. Otherwise an admin can update
     * a post only when EVERY scope row falls inside their manageable
     * region/district/church set — partial overlap doesn't grant edit.
     */
    public function update(User $user, Post $post): bool
    {
        if ($user->can('posts.update.any')) {
            return true;
        }
        if ($post->author_id === $user->id) {
            return true;
        }

        if (! $user->isAdminUser()) {
            return false;
        }

        $regions = $user->manageableRegionIds();
        $districts = $user->manageableDistrictIds();
        $churches = $user->manageableChurchIds();

        foreach ($post->scopes as $s) {
            // National posts can only be edited by national_admin (covered
            // by posts.update.any). Anyone reaching here can't touch them.
            if ($s->national_post) {
                return false;
            }
            if ($s->church_id && ! in_array($s->church_id, $churches, true)) {
                return false;
            }
            if (! $s->church_id && $s->district_id && ! in_array($s->district_id, $districts, true)) {
                return false;
            }
            if (! $s->church_id && ! $s->district_id && $s->region_id && ! in_array($s->region_id, $regions, true)) {
                return false;
            }
        }

        return true;
    }

    public function delete(User $user, Post $post): bool
    {
        return $this->update($user, $post) || $user->can('posts.delete.any');
    }
}
