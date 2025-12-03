<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BlogCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * Get all posts in this category
     */
    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    /**
     * Get published posts count
     */
    public function getPublishedPostsCountAttribute(): int
    {
        return $this->posts()->where('is_published', true)->count();
    }
}
