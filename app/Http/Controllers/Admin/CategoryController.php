<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories
     */
    public function index(Request $request)
    {
        $query = Category::with('parent', 'children');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by type (parent or subcategory)
        if ($request->has('type') && $request->type === 'parent') {
            $query->whereNull('parent_id');
        } elseif ($request->has('type') && $request->type === 'subcategory') {
            $query->whereNotNull('parent_id');
        }

        $categories = $query->orderBy('parent_id')->orderBy('name')->get();

        // Group by parent for tree view
        $parentCategories = $categories->whereNull('parent_id');
        $subcategories = $categories->whereNotNull('parent_id')->groupBy('parent_id');

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
            'parentCategories' => $parentCategories,
            'subcategories' => $subcategories,
            'filters' => [
                'status' => $request->status ?? 'all',
                'type' => $request->type ?? 'all',
            ],
        ]);
    }

    /**
     * Show the form for creating a new category
     */
    public function create()
    {
        $parentCategories = Category::whereNull('parent_id')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Categories/Create', [
            'parentCategories' => $parentCategories,
        ]);
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string|max:1000',
        ]);

        // Generate slug
        $validated['slug'] = Str::slug($validated['name']);
        
        // Ensure slug is unique
        $baseSlug = $validated['slug'];
        $counter = 1;
        while (Category::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $baseSlug . '-' . $counter;
            $counter++;
        }

        Category::create($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Show the form for editing a category
     */
    public function edit($id)
    {
        $category = Category::with('parent')->findOrFail($id);
        $parentCategories = Category::whereNull('parent_id')
            ->where('status', 'active')
            ->where('id', '!=', $id) // Don't allow self as parent
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Categories/Edit', [
            'category' => $category,
            'parentCategories' => $parentCategories,
        ]);
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id|different:' . $id, // Can't be own parent
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string|max:1000',
        ]);

        // Prevent making a category its own descendant
        if ($validated['parent_id']) {
            $descendants = Category::where('parent_id', $id)->pluck('id')->toArray();
            if (in_array($validated['parent_id'], $descendants)) {
                return back()->withErrors(['parent_id' => 'Cannot set a subcategory as parent.']);
            }
        }

        // Update slug if name changed
        if ($category->name !== $validated['name']) {
            $validated['slug'] = Str::slug($validated['name']);
            $baseSlug = $validated['slug'];
            $counter = 1;
            while (Category::where('slug', $validated['slug'])->where('id', '!=', $id)->exists()) {
                $validated['slug'] = $baseSlug . '-' . $counter;
                $counter++;
            }
        }

        $category->update($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified category
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Check if category has subcategories
        if ($category->children()->count() > 0) {
            return back()->with('error', 'Cannot delete category with subcategories. Please delete or reassign subcategories first.');
        }

        // Check if category is used by opportunities
        if ($category->backlinkOpportunities()->count() > 0) {
            return back()->with('error', 'Cannot delete category that is assigned to backlink opportunities. Please remove assignments first.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}

