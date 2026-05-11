<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\PostStatus;
use App\Models\Post;
use Livewire\Form;

class PostForm extends Form
{
    public ?Post $post = null;

    public string $title = '';

    public string $excerpt = '';

    public string $body = '';

    public string $status = '';

    public ?string $published_at = null;

    /**
     * Toggle for the "national" audience checkbox. Only national_admin
     * sees this in the UI; the editor enforces server-side too.
     */
    public bool $national_post = false;

    /**
     * Region IDs the post should be published to (region-only scope).
     *
     * @var array<int, int>
     */
    public array $region_ids = [];

    /**
     * District IDs the post should be published to (district-level scope).
     * Each implies a region — captured automatically on save.
     *
     * @var array<int, int>
     */
    public array $district_ids = [];

    /**
     * Church IDs the post should be published to (local scope). Each
     * implies a district + region.
     *
     * @var array<int, int>
     */
    public array $church_ids = [];

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:5000'],
            'body' => ['required', 'string'],
            'status' => ['required', 'string', 'in:'.implode(',', PostStatus::values())],
            'published_at' => ['nullable', 'date'],
            'national_post' => ['boolean'],
            'region_ids' => ['array'],
            'region_ids.*' => ['integer', 'exists:ecclesiastical_regions,id'],
            'district_ids' => ['array'],
            'district_ids.*' => ['integer', 'exists:districts,id'],
            'church_ids' => ['array'],
            'church_ids.*' => ['integer', 'exists:churches,id'],
        ];
    }

    public function setPost(Post $post): void
    {
        $this->post = $post;
        $this->title = $post->title;
        $this->excerpt = $post->excerpt ?? '';
        $this->body = $post->body;
        $this->status = $post->status->value;
        $this->published_at = $post->published_at?->format('Y-m-d\TH:i');

        // Hydrate the scope buckets from the existing post_scopes rows.
        $rows = $post->scopes;
        $this->national_post = (bool) $rows->contains(fn ($r) => (bool) $r->national_post);
        $this->church_ids = $rows->whereNotNull('church_id')->pluck('church_id')->all();
        $this->district_ids = $rows
            ->whereStrict('church_id', null)
            ->whereNotNull('district_id')
            ->pluck('district_id')->all();
        $this->region_ids = $rows
            ->whereStrict('church_id', null)
            ->whereStrict('district_id', null)
            ->whereNotNull('region_id')
            ->pluck('region_id')->all();
    }
}
