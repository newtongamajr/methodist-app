<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class CommentPolicy
{
    public function create(User $user, Post $post): bool
    {
        return (bool) $user->email_verified_at || ! ($user instanceof MustVerifyEmail);
    }

    public function moderate(User $user, PostComment $comment): bool
    {
        if ($user->can('posts.update.any')) {
            return true;
        }

        if (! $user->can('comments.moderate')) {
            return false;
        }

        $post = $comment->post->loadMissing('scopes');

        // National posts can only be moderated by global moderators (above).
        // For scoped posts: a moderator can act if EVERY scope row falls
        // inside their manageable region/district/church set.
        $regions = $user->manageableRegionIds();
        $districts = $user->manageableDistrictIds();
        $churches = $user->manageableChurchIds();

        foreach ($post->scopes as $s) {
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

    public function delete(User $user, PostComment $comment): bool
    {
        if ($comment->user_id === $user->id) {
            return true;
        }

        return $this->moderate($user, $comment);
    }
}
