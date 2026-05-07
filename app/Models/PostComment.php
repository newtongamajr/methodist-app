<?php

namespace App\Models;

use App\Enums\CommentStatus;
use Database\Factories\PostCommentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostComment extends Model
{
    /** @use HasFactory<PostCommentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'post_id',
        'user_id',
        'body',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CommentStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approve(User $by): void
    {
        $this->forceFill([
            'status' => CommentStatus::Approved,
            'approved_by' => $by->id,
            'approved_at' => now(),
        ])->save();
    }

    public function reject(User $by): void
    {
        $this->forceFill([
            'status' => CommentStatus::Rejected,
            'approved_by' => $by->id,
            'approved_at' => now(),
        ])->save();
    }
}
