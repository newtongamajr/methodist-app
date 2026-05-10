<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\Church;
use App\Models\Post;
use App\Models\PostScope;
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
            'status' => PostStatus::Draft->value,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'excerpt' => fake()->sentence(15),
            'body' => '<p>'.fake()->paragraphs(4, true).'</p>',
            'published_at' => null,
        ];
    }

    /**
     * Every post needs at least one scope row to be visible. Default to a
     * national scope so factory-created posts behave like the old "shared"
     * default — tests that need a local scope opt in via ->local($church).
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Post $post) {
            if ($post->scopes()->count() === 0) {
                PostScope::create([
                    'post_id' => $post->id,
                    'national_post' => true,
                ]);
            }
        });
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => PostStatus::Published->value,
            'published_at' => now(),
        ]);
    }

    /**
     * Post-create state: attach a local-scope row pointing at the given
     * church (creating one if not provided). Region/district are inferred
     * from the church so the resulting row is shaped correctly. The
     * default national scope from configure() is wiped first so the test
     * gets exactly one local row, not local + national.
     */
    public function local(?Church $church = null): static
    {
        return $this->afterCreating(function (Post $post) use ($church) {
            $church = $church ?: Church::factory()->create();
            $post->scopes()->delete();
            PostScope::create([
                'post_id' => $post->id,
                'region_id' => $church->ecclesiastical_region_id,
                'district_id' => $church->district_id,
                'church_id' => $church->id,
            ]);
        });
    }
}
