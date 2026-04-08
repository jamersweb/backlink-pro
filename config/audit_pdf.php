<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Chromium / Puppeteer PDF (HTML → PDF)
    |--------------------------------------------------------------------------
    |
    | Audit report exports use scripts/pdf-render.mjs with Puppeteer.
    | Requires: npm install (installs puppeteer + Chromium).
    |
    */

    'node_binary' => env('PDF_NODE_BINARY', 'node'),

    'timeout' => (int) env('PDF_CHROMIUM_TIMEOUT', 120),

    /**
     * waitUntil passed to page.setContent() — networkidle0 loads remote fonts.
     */
    'wait_until' => env('PDF_PUPPETEER_WAIT_UNTIL', 'networkidle2'),
];
