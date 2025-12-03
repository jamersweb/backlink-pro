<?php

namespace App\Services;

use Illuminate\Support\Facades\Response;

class ExportService
{
    /**
     * Export data as CSV
     */
    public static function exportCsv(array $data, array $headers, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $headers_response = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($data, $headers) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header row
            fputcsv($file, $headers);
            
            // Data rows
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        };

        return Response::stream($callback, 200, $headers_response);
    }

    /**
     * Export data as JSON
     */
    public static function exportJson(array $data, string $filename): \Illuminate\Http\JsonResponse
    {
        return Response::json($data, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export data as Excel (CSV with better formatting)
     */
    public static function exportExcel(array $data, array $headers, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return self::exportCsv($data, $headers, str_replace('.csv', '.xls', $filename));
    }
}


