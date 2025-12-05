<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backlink;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BacklinkController extends Controller
{
    public function index(Request $request)
    {
        $query = Backlink::with(['campaign:id,name,user_id', 'campaign.user:id,name,email', 'siteAccount:id,site_domain'])
            ->latest();

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Filter by campaign
        if ($request->has('campaign_id') && $request->campaign_id) {
            $query->where('campaign_id', $request->campaign_id);
        }

        // Filter by user
        if ($request->has('user_id') && $request->user_id) {
            $query->whereHas('campaign', function($q) use ($request) {
                $q->where('user_id', $request->user_id);
            });
        }

        // Date range filter
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                  ->orWhere('keyword', 'like', "%{$search}%")
                  ->orWhere('anchor_text', 'like', "%{$search}%");
            });
        }

        $backlinks = $query->paginate(50)->withQueryString();

        // Get stats
        $stats = [
            'total' => Backlink::count(),
            'verified' => Backlink::where('status', 'verified')->count(),
            'pending' => Backlink::where('status', 'pending')->count(),
            'submitted' => Backlink::where('status', 'submitted')->count(),
            'error' => Backlink::where('status', 'error')->count(),
            'today' => Backlink::whereDate('created_at', today())->count(),
            'this_week' => Backlink::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => Backlink::whereMonth('created_at', now()->month)->count(),
        ];

        // Get filter options
        $campaigns = Campaign::select('id', 'name', 'user_id')
            ->with('user:id,name')
            ->orderBy('name')
            ->get();
        
        $users = User::select('id', 'name', 'email')->orderBy('name')->get();

        return Inertia::render('Admin/Backlinks/Index', [
            'backlinks' => $backlinks,
            'stats' => $stats,
            'campaigns' => $campaigns,
            'users' => $users,
            'filters' => $request->only(['status', 'type', 'campaign_id', 'user_id', 'date_from', 'date_to', 'search']),
        ]);
    }

    public function export(Request $request)
    {
        $query = Backlink::with(['campaign:id,name', 'campaign.user:id,name,email']);

        // Apply same filters as index
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }
        if ($request->has('campaign_id') && $request->campaign_id) {
            $query->where('campaign_id', $request->campaign_id);
        }
        if ($request->has('user_id') && $request->user_id) {
            $query->whereHas('campaign', function($q) use ($request) {
                $q->where('user_id', $request->user_id);
            });
        }

        $backlinks = $query->get();

        $filename = 'backlinks_export_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($backlinks) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, [
                'ID',
                'Campaign',
                'User',
                'URL',
                'Type',
                'Keyword',
                'Anchor Text',
                'PA',
                'DA',
                'Status',
                'Verified At',
                'Error Message',
                'Created At',
            ]);

            // Data rows
            foreach ($backlinks as $backlink) {
                fputcsv($file, [
                    $backlink->id,
                    $backlink->campaign->name ?? 'N/A',
                    $backlink->campaign->user->name ?? 'N/A',
                    $backlink->url,
                    $backlink->type,
                    $backlink->keyword,
                    $backlink->anchor_text,
                    $backlink->pa ?? '',
                    $backlink->da ?? '',
                    $backlink->status,
                    $backlink->verified_at ? $backlink->verified_at->format('Y-m-d H:i:s') : '',
                    $backlink->error_message,
                    $backlink->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Store a single backlink
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'campaign_id' => 'nullable|exists:campaigns,id',
            'url' => 'required|url',
            'type' => 'required|in:comment,profile,forum,guestposting',
            'keyword' => 'nullable|string|max:255',
            'anchor_text' => 'nullable|string|max:255',
            'pa' => 'nullable|integer|min:0|max:100',
            'da' => 'nullable|integer|min:0|max:100',
            'status' => 'nullable|in:pending,submitted,verified,error',
        ]);

        $backlink = Backlink::create([
            'campaign_id' => $validated['campaign_id'] ?? null,
            'url' => $validated['url'],
            'type' => $validated['type'],
            'keyword' => $validated['keyword'] ?? null,
            'anchor_text' => $validated['anchor_text'] ?? null,
            'pa' => !empty($validated['pa']) ? (int)$validated['pa'] : null,
            'da' => !empty($validated['da']) ? (int)$validated['da'] : null,
            'status' => $validated['status'] ?? 'pending',
            'verified_at' => $validated['status'] === 'verified' ? now() : null,
        ]);

        return redirect()->route('admin.backlinks.index')
            ->with('success', 'Backlink added successfully.');
    }

    /**
     * Bulk import backlinks from CSV
     */
    public function bulkImport(Request $request)
    {
        $request->validate([
            'campaign_id' => 'nullable|exists:campaigns,id',
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ]);

        $file = $request->file('csv_file');
        $campaignId = $request->campaign_id;
        
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

        // Store original headers for debugging, but normalize for matching
        $originalHeaders = $headers;
        $normalizedHeaders = array_map(function($header) {
            // Remove BOM, trim, lowercase, remove special chars
            $header = trim($header);
            $header = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header); // Remove BOM and special chars
            return strtolower($header);
        }, $headers);

        // Expected columns (flexible mapping)
        $expectedColumns = ['url', 'type', 'keyword', 'anchor_text', 'pa', 'da', 'status'];
        $columnMap = [];
        
        foreach ($expectedColumns as $col) {
            foreach ($normalizedHeaders as $index => $normalizedHeader) {
                // Exact match
                if ($normalizedHeader === $col) {
                    $columnMap[$col] = $index;
                    break;
                }
                
                // Partial match (for variations like "page authority" for "pa")
                if ($col === 'pa' && (
                    strpos($normalizedHeader, 'page authority') !== false || 
                    strpos($normalizedHeader, 'page_authority') !== false ||
                    strpos($normalizedHeader, 'pa') !== false
                )) {
                    $columnMap[$col] = $index;
                    break;
                }
                
                if ($col === 'da' && (
                    strpos($normalizedHeader, 'domain authority') !== false || 
                    strpos($normalizedHeader, 'domain_authority') !== false ||
                    strpos($normalizedHeader, 'da') !== false
                )) {
                    $columnMap[$col] = $index;
                    break;
                }
                
                // For URL, check various formats
                if ($col === 'url' && (
                    $normalizedHeader === 'url' ||
                    $normalizedHeader === 'link' ||
                    $normalizedHeader === 'website' ||
                    strpos($normalizedHeader, 'url') !== false
                )) {
                    $columnMap[$col] = $index;
                    break;
                }
            }
        }

        // URL is required - check with better error message
        if (!isset($columnMap['url'])) {
            fclose($handle);
            $foundHeaders = implode(', ', $originalHeaders);
            return back()->with('error', "CSV file must contain a URL column. Found columns: {$foundHeaders}");
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        // Read data rows
        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Get URL (required)
            $url = isset($row[$columnMap['url']]) ? trim($row[$columnMap['url']]) : '';
            
            // Skip rows that look like headers or section separators
            $headerKeywords = ['main url', 'url', 'link', 'website', 'http', 'https', 'www'];
            $urlLower = strtolower($url);
            if (empty($url) || in_array($urlLower, $headerKeywords) || 
                strpos($urlLower, 'main url') !== false ||
                strpos($urlLower, 'column') !== false ||
                strlen($url) < 10) { // URLs should be at least 10 chars
                $skipped++;
                if (!empty($url)) {
                    $errors[] = "Row {$rowNumber}: Skipped header/section row - '{$url}'";
                } else {
                    $errors[] = "Row {$rowNumber}: URL is required";
                }
                continue;
            }

            // Validate URL - be more lenient
            $url = trim($url);
            
            // Skip if it's clearly not a URL (contains spaces, looks like text)
            if (strpos($url, ' ') !== false && !preg_match('/^https?:\/\//i', $url)) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: Invalid URL format (contains spaces) - '{$url}'";
                continue;
            }
            
            // Add http:// if no scheme
            if (!preg_match('/^https?:\/\//i', $url)) {
                $url = 'https://' . $url;
            }
            
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: Invalid URL format - '{$url}'";
                continue;
            }

            // Get other fields
            $type = isset($columnMap['type']) && isset($row[$columnMap['type']]) 
                ? strtolower(trim($row[$columnMap['type']])) 
                : 'comment';
            
            // Validate and normalize type
            $validTypes = ['comment', 'profile', 'forum', 'guestposting'];
            if (!in_array($type, $validTypes)) {
                // Try to match common variations
                $typeMap = [
                    'profile' => 'profile',
                    'profiles' => 'profile',
                    'comment' => 'comment',
                    'comments' => 'comment',
                    'forum' => 'forum',
                    'forums' => 'forum',
                    'guest' => 'guestposting',
                    'guestpost' => 'guestposting',
                    'guestposting' => 'guestposting',
                    'guest post' => 'guestposting',
                ];
                $type = $typeMap[strtolower($type)] ?? 'comment'; // Default fallback
            }

            $keyword = isset($columnMap['keyword']) && isset($row[$columnMap['keyword']]) 
                ? trim($row[$columnMap['keyword']]) 
                : null;
            
            $anchorText = isset($columnMap['anchor_text']) && isset($row[$columnMap['anchor_text']]) 
                ? trim($row[$columnMap['anchor_text']]) 
                : null;

            $pa = null;
            if (isset($columnMap['pa']) && isset($row[$columnMap['pa']])) {
                $paValue = trim($row[$columnMap['pa']]);
                $pa = !empty($paValue) && is_numeric($paValue) ? (int)$paValue : null;
                // Validate range
                if ($pa !== null && ($pa < 0 || $pa > 100)) {
                    $pa = null;
                }
            }
            
            $da = null;
            if (isset($columnMap['da']) && isset($row[$columnMap['da']])) {
                $daValue = trim($row[$columnMap['da']]);
                $da = !empty($daValue) && is_numeric($daValue) ? (int)$daValue : null;
                // Validate range
                if ($da !== null && ($da < 0 || $da > 100)) {
                    $da = null;
                }
            }

            $status = isset($columnMap['status']) && isset($row[$columnMap['status']]) 
                ? strtolower(trim($row[$columnMap['status']])) 
                : 'pending';
            
            // Validate and normalize status
            $validStatuses = ['pending', 'submitted', 'verified', 'error'];
            if (!in_array($status, $validStatuses)) {
                // Map common status values
                $statusMap = [
                    'live' => 'verified',
                    'active' => 'verified',
                    'published' => 'verified',
                    'pending' => 'pending',
                    'submitted' => 'submitted',
                    'verified' => 'verified',
                    'error' => 'error',
                    'failed' => 'error',
                ];
                $status = $statusMap[strtolower($status)] ?? 'pending'; // Default fallback
            }

            try {
                Backlink::create([
                    'campaign_id' => $campaignId ?: null,
                    'url' => $url,
                    'type' => $type,
                    'keyword' => $keyword,
                    'anchor_text' => $anchorText,
                    'pa' => $pa,
                    'da' => $da,
                    'status' => $status,
                    'verified_at' => $status === 'verified' ? now() : null,
                ]);
                $imported++;
            } catch (\Exception $e) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: " . $e->getMessage();
            }
        }

        fclose($handle);

        if ($imported > 0) {
            $message = "Successfully imported {$imported} backlink(s).";
            if ($skipped > 0) {
                $message .= " {$skipped} row(s) skipped.";
                if (!empty($errors) && count($errors) <= 10) {
                    $message .= " Errors: " . implode('; ', array_slice($errors, 0, 10));
                } elseif (!empty($errors)) {
                    $message .= " First 10 errors: " . implode('; ', array_slice($errors, 0, 10)) . " (and " . (count($errors) - 10) . " more)";
                }
            }
            return redirect()->route('admin.backlinks.index')
                ->with('success', $message)
                ->with('import_errors', $errors);
        } else {
            $errorMessage = "No backlinks imported. {$skipped} row(s) skipped.";
            if (!empty($errors)) {
                $errorMessage .= " Common errors: " . implode('; ', array_slice($errors, 0, 10));
                if (count($errors) > 10) {
                    $errorMessage .= " (and " . (count($errors) - 10) . " more errors)";
                }
            }
            return redirect()->route('admin.backlinks.index')
                ->with('error', $errorMessage)
                ->with('import_errors', $errors);
        }
    }
}

