<?php

namespace Database\Factories;

use App\Enums\CommentStatus;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostComment>
 */
class PostCommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
            'status' => CommentStatus::Pending->value,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => CommentStatus::Approved->value,
            'approved_at' => now(),
        ]);
    }
}
