<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BacklinkOpportunity;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BacklinkOpportunityController extends Controller
{
    /**
     * Display a listing of backlink opportunities
     */
    public function index(Request $request)
    {
        $query = BacklinkOpportunity::with('categories');

        // Filter by category
        if ($request->has('category_id') && $request->category_id) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        // Filter by PA range
        if ($request->has('pa_min') && $request->pa_min !== null) {
            $query->where('pa', '>=', $request->pa_min);
        }
        if ($request->has('pa_max') && $request->pa_max !== null) {
            $query->where('pa', '<=', $request->pa_max);
        }

        // Filter by DA range
        if ($request->has('da_min') && $request->da_min !== null) {
            $query->where('da', '>=', $request->da_min);
        }
        if ($request->has('da_max') && $request->da_max !== null) {
            $query->where('da', '<=', $request->da_max);
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by site type
        if ($request->has('site_type') && $request->site_type) {
            $query->where('site_type', $request->site_type);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('url', 'like', "%{$search}%");
            });
        }

        $opportunities = $query->latest()->paginate(50)->withQueryString();

        // Get stats
        $stats = [
            'total' => BacklinkOpportunity::count(),
            'active' => BacklinkOpportunity::where('status', 'active')->count(),
            'inactive' => BacklinkOpportunity::where('status', 'inactive')->count(),
            'banned' => BacklinkOpportunity::where('status', 'banned')->count(),
        ];

        // Get filter options
        $categories = Category::where('status', 'active')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/BacklinkOpportunities/Index', [
            'opportunities' => $opportunities,
            'stats' => $stats,
            'categories' => $categories,
            'filters' => $request->only(['category_id', 'pa_min', 'pa_max', 'da_min', 'da_max', 'status', 'site_type', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new opportunity
     */
    public function create()
    {
        $categories = Category::where('status', 'active')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/BacklinkOpportunities/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created opportunity
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'pa' => 'nullable|integer|min:0|max:100',
            'da' => 'nullable|integer|min:0|max:100',
            'site_type' => 'required|in:comment,profile,forum,guestposting,other',
            'status' => 'required|in:active,inactive,banned',
            'daily_site_limit' => 'nullable|integer|min:0',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
        ]);

        DB::transaction(function() use ($validated) {
            $opportunity = BacklinkOpportunity::create([
                'url' => $validated['url'],
                'pa' => $validated['pa'] ?? null,
                'da' => $validated['da'] ?? null,
                'site_type' => $validated['site_type'],
                'status' => $validated['status'],
                'daily_site_limit' => $validated['daily_site_limit'] ?? null,
            ]);

            $opportunity->categories()->attach($validated['category_ids']);
        });

        return redirect()->route('admin.backlink-opportunities.index')
            ->with('success', 'Backlink opportunity created successfully.');
    }

    /**
     * Show the form for editing an opportunity
     */
    public function edit($id)
    {
        $opportunity = BacklinkOpportunity::with('categories')->findOrFail($id);
        $categories = Category::where('status', 'active')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/BacklinkOpportunities/Edit', [
            'opportunity' => $opportunity,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified opportunity
     */
    public function update(Request $request, $id)
    {
        $opportunity = BacklinkOpportunity::findOrFail($id);

        $validated = $request->validate([
            'url' => 'required|url',
            'pa' => 'nullable|integer|min:0|max:100',
            'da' => 'nullable|integer|min:0|max:100',
            'site_type' => 'required|in:comment,profile,forum,guestposting,other',
            'status' => 'required|in:active,inactive,banned',
            'daily_site_limit' => 'nullable|integer|min:0',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
        ]);

        DB::transaction(function() use ($opportunity, $validated) {
            $opportunity->update([
                'url' => $validated['url'],
                'pa' => $validated['pa'] ?? null,
                'da' => $validated['da'] ?? null,
                'site_type' => $validated['site_type'],
                'status' => $validated['status'],
                'daily_site_limit' => $validated['daily_site_limit'] ?? null,
            ]);

            $opportunity->categories()->sync($validated['category_ids']);
        });

        return redirect()->route('admin.backlink-opportunities.index')
            ->with('success', 'Backlink opportunity updated successfully.');
    }

    /**
     * Remove the specified opportunity
     */
    public function destroy($id)
    {
        $opportunity = BacklinkOpportunity::findOrFail($id);
        $opportunity->delete();

        return redirect()->route('admin.backlink-opportunities.index')
            ->with('success', 'Backlink opportunity deleted successfully.');
    }

    /**
     * Bulk import from CSV
     */
    public function bulkImport(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        
        if ($handle === false) {
            return back()->with('error', 'Failed to read CSV file.');
        }

        // Read header row
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return back()->with('error', 'CSV file is empty.');
        }

        // Normalize headers
        $headers = array_map(function($header) {
            return strtolower(trim($header));
        }, $headers);

        // Map columns
        $columnMap = [];
        $expectedColumns = ['url', 'pa', 'da', 'categories', 'site_type', 'status'];
        foreach ($expectedColumns as $col) {
            foreach ($headers as $index => $header) {
                if ($header === $col || strpos($header, $col) !== false) {
                    $columnMap[$col] = $index;
                    break;
                }
            }
        }

        if (!isset($columnMap['url'])) {
            fclose($handle);
            return back()->with('error', 'CSV file must contain a URL column.');
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            $rowNumber = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                
                if (empty(array_filter($row))) {
                    continue;
                }

                $url = isset($row[$columnMap['url']]) ? trim($row[$columnMap['url']]) : '';
                if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: Invalid or missing URL";
                    continue;
                }

                $pa = isset($columnMap['pa']) && isset($row[$columnMap['pa']]) 
                    ? (int)trim($row[$columnMap['pa']]) 
                    : null;
                $pa = ($pa >= 0 && $pa <= 100) ? $pa : null;

                $da = isset($columnMap['da']) && isset($row[$columnMap['da']]) 
                    ? (int)trim($row[$columnMap['da']]) 
                    : null;
                $da = ($da >= 0 && $da <= 100) ? $da : null;

                $siteType = isset($columnMap['site_type']) && isset($row[$columnMap['site_type']]) 
                    ? trim($row[$columnMap['site_type']]) 
                    : 'comment';
                if (!in_array($siteType, ['comment', 'profile', 'forum', 'guestposting', 'other'])) {
                    $siteType = 'comment';
                }

                $status = isset($columnMap['status']) && isset($row[$columnMap['status']]) 
                    ? trim($row[$columnMap['status']]) 
                    : 'active';
                if (!in_array($status, ['active', 'inactive', 'banned'])) {
                    $status = 'active';
                }

                // Parse categories (pipe-separated)
                $categoryNames = [];
                if (isset($columnMap['categories']) && isset($row[$columnMap['categories']])) {
                    $categoryString = trim($row[$columnMap['categories']]);
                    $categoryNames = array_filter(array_map('trim', explode('|', $categoryString)));
                }

                if (empty($categoryNames)) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: No categories specified";
                    continue;
                }

                // Find category IDs by name or slug
                $categoryIds = [];
                foreach ($categoryNames as $catName) {
                    $category = Category::where('name', $catName)
                        ->orWhere('slug', \Illuminate\Support\Str::slug($catName))
                        ->first();
                    if ($category) {
                        $categoryIds[] = $category->id;
                    }
                }

                if (empty($categoryIds)) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: Categories not found: " . implode(', ', $categoryNames);
                    continue;
                }

                try {
                    $opportunity = BacklinkOpportunity::create([
                        'url' => $url,
                        'pa' => $pa,
                        'da' => $da,
                        'site_type' => $siteType,
                        'status' => $status,
                    ]);

                    $opportunity->categories()->attach($categoryIds);
                    $imported++;
                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }

        fclose($handle);

        $message = "Successfully imported {$imported} opportunity(s).";
        if ($skipped > 0) {
            $message .= " {$skipped} row(s) skipped.";
        }

        return redirect()->route('admin.backlink-opportunities.index')
            ->with('success', $message)
            ->with('import_errors', array_slice($errors, 0, 20)); // Limit errors shown
    }

    /**
     * Export opportunities to CSV
     */
    public function export(Request $request)
    {
        $query = BacklinkOpportunity::with('categories');

        // Apply filters
        if ($request->has('category_id') && $request->category_id) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->has('site_type') && $request->site_type) {
            $query->where('site_type', $request->site_type);
        }

        $opportunities = $query->get();

        $filename = 'backlink_opportunities_export_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($opportunities) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, ['url', 'pa', 'da', 'categories', 'site_type', 'status']);

            // Data rows
            foreach ($opportunities as $opp) {
                $categoryNames = $opp->categories->pluck('name')->implode('|');
                fputcsv($file, [
                    $opp->url,
                    $opp->pa ?? '',
                    $opp->da ?? '',
                    $categoryNames,
                    $opp->site_type,
                    $opp->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

