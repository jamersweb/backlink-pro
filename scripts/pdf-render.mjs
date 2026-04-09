/**
 * HTML file → PDF via Puppeteer (A4, print backgrounds, fonts, Tailwind CDN).
 * Usage: node scripts/pdf-render.mjs <input.html> <output.pdf> [waitUntil]
 *
 * Env: PDF_PUPPETEER_WAIT_UNTIL=networkidle2|networkidle0|load (default networkidle2)
 */
import fs from 'fs';
import puppeteer from 'puppeteer';

const htmlPath  = process.argv[2];
const outPath   = process.argv[3];
const waitUntil = process.argv[4] || process.env.PDF_PUPPETEER_WAIT_UNTIL || 'networkidle2';
const executablePath = process.env.PUPPETEER_EXECUTABLE_PATH || undefined;

if (!htmlPath || !outPath) {
    console.error('Usage: node pdf-render.mjs <input.html> <output.pdf>');
    process.exit(1);
}

const html = fs.readFileSync(htmlPath, 'utf8');

const browser = await puppeteer.launch({
    headless: true,
    executablePath,
    args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-gpu',
        '--font-render-hinting=none',
        '--run-all-compositor-stages-before-draw',
    ],
});

try {
    const page = await browser.newPage();

    // Render at A4-ish CSS pixels so layout fills the page cleanly.
    await page.setViewport({ width: 794, height: 1123, deviceScaleFactor: 2 });

    // Load the page; networkidle2 waits for CDN + fonts to settle (max 2 concurrent requests).
    await page.setContent(html, { waitUntil: waitUntil, timeout: 180000 });

    // Wait for Google Fonts + Tailwind CDN to fully process.
    try {
        await page.evaluate(async () => {
            if (document.fonts?.ready) await document.fonts.ready;
        });
    } catch { /* ignore */ }

    // Extra buffer so Tailwind JIT finishes applying all utility classes.
    await new Promise((r) => setTimeout(r, 2000));

    await page.pdf({
        path: outPath,
        format: 'A4',
        printBackground: true,
        preferCSSPageSize: true,
        scale: 1,
    });

    console.log('PDF written:', outPath);
} finally {
    await browser.close();
}
