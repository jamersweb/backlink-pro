<?php

namespace App\Services\Audit;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ChromiumPdfRenderer
{
    public function htmlToPdf(string $html): string
    {
        $dir = storage_path('app/tmp/pdf');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $base     = uniqid('audit_pdf_', true);
        $htmlPath = $dir . DIRECTORY_SEPARATOR . $base . '.html';
        $pdfPath  = $dir . DIRECTORY_SEPARATOR . $base . '.pdf';

        File::put($htmlPath, $html);

        $node    = (string) config('audit_pdf.node_binary', 'node');
        $script  = base_path('scripts/pdf-render.mjs');
        $timeout = (int) config('audit_pdf.timeout', 120);

        if (! File::exists($script)) {
            @unlink($htmlPath);
            throw new \RuntimeException('PDF renderer script missing: scripts/pdf-render.mjs');
        }

        $waitUntil = (string) config('audit_pdf.wait_until', 'networkidle2');
        $puppeteerCacheDir = storage_path('app/puppeteer');
        if (! File::isDirectory($puppeteerCacheDir)) {
            File::makeDirectory($puppeteerCacheDir, 0755, true);
        }

        /*
         * Build a clean environment for Node.js.
         * XAMPP sets OPENSSL_CONF to its own config which causes Node.js v18+ to crash
         * with "Assertion failed: ncrypto::CSPRNG(nullptr, 0)" on Windows.
         * We get the full inherited env, strip the XAMPP OpenSSL vars, then pass it explicitly.
         */
        $inheritedEnv = getenv();
        if (! is_array($inheritedEnv)) {
            $inheritedEnv = [];
        }

        // Remove variables that break Node.js crypto on XAMPP Windows.
        $blockedVars = ['OPENSSL_CONF', 'RANDFILE', 'SSL_CERT_FILE', 'OPENSSL_ENGINES'];
        foreach ($blockedVars as $var) {
            unset($inheritedEnv[$var]);
        }

        // Add NODE_OPTIONS to use legacy OpenSSL provider as an extra safety net.
        $existingNodeOpts = trim($inheritedEnv['NODE_OPTIONS'] ?? '');
        if (! str_contains($existingNodeOpts, '--openssl-legacy-provider')) {
            $inheritedEnv['NODE_OPTIONS'] = trim('--openssl-legacy-provider ' . $existingNodeOpts);
        }
        $inheritedEnv['PUPPETEER_CACHE_DIR'] = $puppeteerCacheDir;

        $process = new Process(
            [$node, $script, $htmlPath, $pdfPath, $waitUntil],
            base_path(),
            $inheritedEnv,
            null,
            $timeout
        );

        $process->run();

        if (! $process->isSuccessful()) {
            Log::error('Chromium PDF failed', [
                'exit' => $process->getExitCode(),
                'err'  => $process->getErrorOutput(),
                'out'  => $process->getOutput(),
            ]);
            @unlink($htmlPath);
            @unlink($pdfPath);
            throw new \RuntimeException(
                trim($process->getErrorOutput() ?: $process->getOutput()) ?: 'PDF generation failed.'
            );
        }

        if (! File::exists($pdfPath)) {
            @unlink($htmlPath);
            throw new \RuntimeException('PDF file was not created.');
        }

        $binary = File::get($pdfPath);
        @unlink($htmlPath);
        @unlink($pdfPath);

        return $binary;
    }
}
