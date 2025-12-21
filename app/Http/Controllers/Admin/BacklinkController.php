<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backlink;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BacklinkController extends Controller
{
    /**
     * Display a listing of backlinks (global store)
     */
    public function index(Request $request)
    {
        $query = Backlink::with('categories');

        // Filter by status
        if ($request->has('status') && $request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by site type
        if ($request->has('site_type') && $request->site_type) {
            $query->where('site_type', $request->site_type);
        }

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

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('url', 'like', "%{$search}%");
            });
        }

        $backlinks = $query->latest()->paginate(50)->withQueryString();

        // Get stats
        $stats = [
            'total' => Backlink::count(),
            'active' => Backlink::where('status', Backlink::STATUS_ACTIVE)->count(),
            'inactive' => Backlink::where('status', Backlink::STATUS_INACTIVE)->count(),
            'banned' => Backlink::where('status', Backlink::STATUS_BANNED)->count(),
        ];

        // Get filter options
        $categories = Category::where('status', 'active')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Backlinks/Index', [
            'backlinks' => $backlinks,
            'stats' => $stats,
            'categories' => $categories,
            'filters' => $request->only(['status', 'site_type', 'category_id', 'pa_min', 'pa_max', 'da_min', 'da_max', 'search']),
        ]);
    }

    /**
     * Store a single backlink (add to global store)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url|unique:backlinks,url',
            'pa' => 'nullable|integer|min:0|max:100',
            'da' => 'nullable|integer|min:0|max:100',
            'site_type' => 'required|in:comment,profile,forum,guestposting,other',
            'status' => 'required|in:active,inactive,banned',
            'daily_site_limit' => 'nullable|integer|min:0',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
        ]);

        DB::transaction(function() use ($validated) {
            $backlink = Backlink::create([
                'url' => $validated['url'],
                'pa' => $validated['pa'] ?? null,
                'da' => $validated['da'] ?? null,
                'site_type' => $validated['site_type'],
                'status' => $validated['status'],
                'daily_site_limit' => $validated['daily_site_limit'] ?? null,
            ]);

            // Attach categories
            $backlink->categories()->attach($validated['category_ids']);
        });

        return redirect()->route('admin.backlinks.index')
            ->with('success', 'Backlink added to store successfully.');
    }

    /**
     * Bulk import backlinks from CSV (add to global store)
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
        $normalizedHeaders = array_map(function($header) {
            return strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header)));
        }, $headers);

        // Expected columns
        $expectedColumns = ['url', 'pa', 'da', 'site_type', 'status', 'daily_site_limit', 'categories'];
        $columnMap = [];
        
        foreach ($expectedColumns as $col) {
            foreach ($normalizedHeaders as $index => $normalizedHeader) {
                if ($normalizedHeader === $col || strpos($normalizedHeader, $col) !== false) {
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
                    continue;
                }

                // Check if already exists
                if (Backlink::where('url', $url)->exists()) {
                    $skipped++;
                    continue;
                }

                $pa = isset($columnMap['pa']) && isset($row[$columnMap['pa']]) 
                    ? (int)trim($row[$columnMap['pa']]) : null;
                $da = isset($columnMap['da']) && isset($row[$columnMap['da']]) 
                    ? (int)trim($row[$columnMap['da']]) : null;
                $siteType = isset($columnMap['site_type']) && isset($row[$columnMap['site_type']]) 
                    ? strtolower(trim($row[$columnMap['site_type']])) : 'comment';
                $status = isset($columnMap['status']) && isset($row[$columnMap['status']]) 
                    ? strtolower(trim($row[$columnMap['status']])) : 'active';
                $dailyLimit = isset($columnMap['daily_site_limit']) && isset($row[$columnMap['daily_site_limit']]) 
                    ? (int)trim($row[$columnMap['daily_site_limit']]) : null;

                // Validate site_type
                $validSiteTypes = ['comment', 'profile', 'forum', 'guestposting', 'other'];
                if (!in_array($siteType, $validSiteTypes)) {
                    $siteType = 'comment';
                }

                // Validate status
                $validStatuses = ['active', 'inactive', 'banned'];
                if (!in_array($status, $validStatuses)) {
                    $status = 'active';
                }

                $backlink = Backlink::create([
                    'url' => $url,
                    'pa' => $pa,
                    'da' => $da,
                    'site_type' => $siteType,
                    'status' => $status,
                    'daily_site_limit' => $dailyLimit,
                ]);

                // Handle categories if provided
                if (isset($columnMap['categories']) && isset($row[$columnMap['categories']])) {
                    $categoryNames = array_map('trim', explode(',', $row[$columnMap['categories']]));
                    $categoryIds = Category::whereIn('name', $categoryNames)->pluck('id');
                    if ($categoryIds->isNotEmpty()) {
                        $backlink->categories()->attach($categoryIds);
                    }
                }

                $imported++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }

        fclose($handle);

        $message = "Successfully imported {$imported} backlink(s) to store.";
        if ($skipped > 0) {
            $message .= " {$skipped} row(s) skipped.";
        }

        return redirect()->route('admin.backlinks.index')
            ->with('success', $message);
    }

    /**
     * Export backlinks (global store)
     */
    public function export(Request $request)
    {
        $query = Backlink::with('categories');

        // Apply filters
        if ($request->has('status') && $request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->has('site_type') && $request->site_type) {
            $query->where('site_type', $request->site_type);
        }
        if ($request->has('category_id') && $request->category_id) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        $backlinks = $query->get();

        $filename = 'backlinks_store_export_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($backlinks) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, [
                'ID',
                'URL',
                'PA',
                'DA',
                'Site Type',
                'Status',
                'Daily Site Limit',
                'Categories',
                'Created At',
            ]);

            // Data rows
            foreach ($backlinks as $backlink) {
                fputcsv($file, [
                    $backlink->id,
                    $backlink->url,
                    $backlink->pa ?? '',
                    $backlink->da ?? '',
                    $backlink->site_type,
                    $backlink->status,
                    $backlink->daily_site_limit ?? '',
                    $backlink->categories->pluck('name')->join(', '),
                    $backlink->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
