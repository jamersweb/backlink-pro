<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'tags',
        'is_published',
        'published_at',
        'views',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
            if ($post->is_published && !$post->published_at) {
                $post->published_at = now();
            }
        });

        static::updating(function ($post) {
            if ($post->is_published && !$post->published_at) {
                $post->published_at = now();
            }
        });
    }

    /**
     * Get the category this post belongs to
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class);
    }

    /**
     * Scope for published posts
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where('published_at', '<=', now());
    }

    /**
     * Increment views
     */
    public function incrementViews(): void
    {
        $this->increment('views');
    }
}
