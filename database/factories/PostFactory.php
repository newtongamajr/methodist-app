<?php

namespace Database\Factories;

use App\Enums\PostScope;
use App\Enums\PostStatus;
use App\Models\Church;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'author_id' => User::factory(),
            'church_id' => null,
            'scope' => PostScope::Shared->value,
            'status' => PostStatus::Draft->value,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'excerpt' => fake()->sentence(15),
            'body' => '<p>'.fake()->paragraphs(4, true).'</p>',
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => PostStatus::Published->value,
            'published_at' => now(),
        ]);
    }

    public function local(?Church $church = null): static
    {
        return $this->state(fn () => [
            'scope' => PostScope::Local->value,
            'church_id' => $church?->id ?? Church::factory(),
        ]);
    }
}
