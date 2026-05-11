<?php

namespace App\Policies;

use App\Enums\PostScope;
use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Post $post): bool
    {
        if ($post->scope === PostScope::Shared) {
            return true;
        }

        if (! $user) {
            return false;
        }

        // A user can read a local post if they're a member of that church OR
        // an admin of it.
        return $user->person?->managing_church_id === $post->church_id
            || $user->canManageChurch($post->church_id ?? 0);
    }

    public function create(User $user): bool
    {
        return $user->can('posts.create.shared') || $user->can('posts.create.local');
    }

    public function update(User $user, Post $post): bool
    {
        if ($user->can('posts.update.any')) {
            return true;
        }

        if ($post->author_id === $user->id) {
            return true;
        }

        if ($post->scope === PostScope::Local && $user->can('posts.create.local') && $post->church_id && $user->canManageChurch($post->church_id)) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Post $post): bool
    {
        return $this->update($user, $post) || $user->can('posts.delete.any');
    }
}
