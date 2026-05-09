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

        $post = $comment->post;

        if ($post->church_id) {
            return $user->person?->managing_church_id === $post->church_id
                || $user->canManageChurch($post->church_id);
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
