<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\PostScope;
use App\Enums\PostStatus;
use App\Models\Post;
use Livewire\Form;

class PostForm extends Form
{
    public ?Post $post = null;

    public string $title = '';

    public string $excerpt = '';

    public string $body = '';

    public string $scope = '';

    public string $status = '';

    public ?int $church_id = null;

    public ?string $published_at = null;

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:5000'],
            'body' => ['required', 'string'],
            'scope' => ['required', 'string', 'in:'.implode(',', PostScope::values())],
            'status' => ['required', 'string', 'in:'.implode(',', PostStatus::values())],
            'church_id' => ['nullable', 'integer', 'exists:churches,id'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    public function setPost(Post $post): void
    {
        $this->post = $post;
        $this->title = $post->title;
        $this->excerpt = $post->excerpt ?? '';
        $this->body = $post->body;
        $this->scope = $post->scope->value;
        $this->status = $post->status->value;
        $this->church_id = $post->church_id;
        $this->published_at = $post->published_at?->format('Y-m-d\TH:i');
    }
}
