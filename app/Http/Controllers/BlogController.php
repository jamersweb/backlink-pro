<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BlogController extends Controller
{
    /**
     * List all blog posts
     */
    public function index(Request $request)
    {
        try {
            $query = BlogPost::published()
                ->with('category')
                ->latest('published_at');

            // Filter by category
            if ($request->has('category') && $request->category) {
                $query->whereHas('category', function($q) use ($request) {
                    $q->where('slug', $request->category);
                });
            }

            // Search
            if ($request->has('search') && $request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->search . '%')
                      ->orWhere('excerpt', 'like', '%' . $request->search . '%')
                      ->orWhere('content', 'like', '%' . $request->search . '%');
                });
            }

            $posts = $query->paginate(12)->withQueryString();
            
            // Get categories with timeout protection
            try {
                $categories = BlogCategory::withCount('posts')->get();
            } catch (\Exception $e) {
                \Log::warning('Failed to load blog categories', ['error' => $e->getMessage()]);
                $categories = collect([]);
            }

            return Inertia::render('Blog/Index', [
                'posts' => $posts,
                'categories' => $categories,
                'filters' => $request->only(['category', 'search']),
            ]);
        } catch (\Exception $e) {
            \Log::error('Blog index error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            // Return empty results on error
            return Inertia::render('Blog/Index', [
                'posts' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 12),
                'categories' => collect([]),
                'filters' => $request->only(['category', 'search']),
            ]);
        }
    }

    /**
     * Show single blog post
     */
    public function show($slug)
    {
        $post = BlogPost::published()
            ->where('slug', $slug)
            ->with('category')
            ->firstOrFail();

        // Increment views
        $post->incrementViews();

        // Get related posts (same category)
        $relatedPosts = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->where('category_id', $post->category_id)
            ->latest('published_at')
            ->limit(3)
            ->get();

        return Inertia::render('Blog/Show', [
            'post' => $post,
            'relatedPosts' => $relatedPosts,
        ]);
    }
}
