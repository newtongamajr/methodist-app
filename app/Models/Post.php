<?php

namespace App\Models;

use App\Enums\CommentStatus;
use App\Enums\PostScope;
use App\Enums\PostStatus;
use App\Support\GenerateUniqueSlug;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mews\Purifier\Facades\Purifier;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Post extends Model implements HasMedia
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'author_id',
        'church_id',
        'scope',
        'status',
        'title',
        'slug',
        'excerpt',
        'body',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'scope' => PostScope::class,
            'status' => PostStatus::class,
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Post $post) {
            if (empty($post->slug)) {
                $post->slug = (new GenerateUniqueSlug)(
                    $post->title,
                    static::query()->whereKeyNot($post->getKey() ?? 0),
                );
            }
        });
    }

    protected function body(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value === null ? null : Purifier::clean($value, 'post.body'),
        );
    }

    protected function excerpt(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value === null ? null : Purifier::clean($value, 'post.excerpt'),
        );
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function legacyMedia(): HasMany
    {
        return $this->hasMany(PostMedia::class)->orderBy('display_order');
    }

    public function embeds(): HasMany
    {
        return $this->hasMany(PostEmbed::class)->orderBy('display_order');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(PostLike::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class)->orderBy('created_at', 'desc');
    }

    public function approvedComments(): HasMany
    {
        return $this->comments()->where('status', CommentStatus::Approved);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        $managingChurchId = $user?->person?->managing_church_id;

        return $query->where(function (Builder $q) use ($managingChurchId) {
            $q->where('scope', PostScope::Shared);

            if ($managingChurchId) {
                $q->orWhere(fn ($qq) => $qq
                    ->where('scope', PostScope::Local)
                    ->where('church_id', $managingChurchId));
            }
        });
    }

    public function coverUrl(string $conversion = 'card'): ?string
    {
        $url = $this->getFirstMediaUrl('cover', $conversion);

        return $url !== '' ? $url : null;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

        $this->addMediaCollection('videos')
            ->acceptsMimeTypes(['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime']);

        $this->addMediaCollection('audios')
            ->acceptsMimeTypes(['audio/mpeg', 'audio/mp4', 'audio/ogg', 'audio/wav', 'audio/webm']);

        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->performOnCollections('cover', 'images')
            ->fit(Fit::Crop, 320, 200);

        $this->addMediaConversion('card')
            ->performOnCollections('cover', 'images')
            ->fit(Fit::Crop, 800, 500);

        $this->addMediaConversion('hero')
            ->performOnCollections('cover', 'images')
            ->fit(Fit::Max, 1600, 900);
    }
}
