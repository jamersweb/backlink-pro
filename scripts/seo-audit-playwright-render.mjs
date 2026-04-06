/**
 * Batch headless render for SEO audit (stdin JSON -> stdout JSON).
 * Reuses one browser per batch; one context per URL for isolation.
 *
 * Input JSON:
 * {
 *   "urls": ["https://..."],
 *   "internal_host": "example.com",
 *   "navigation_timeout_ms": 30000,
 *   "settle_after_load_ms": 1500,
 *   "block_heavy_assets": true,
 *   "emit_body_html": false,
 *   "body_html_max_chars": 250000
 * }
 */

import { chromium } from 'playwright';
import { stdin as input, stdout } from 'node:process';
import { Buffer } from 'node:buffer';

function readStdin() {
  return new Promise((resolve, reject) => {
    const chunks = [];
    input.on('data', (c) => chunks.push(c));
    input.on('end', () => resolve(Buffer.concat(chunks).toString('utf8')));
    input.on('error', reject);
  });
}

function normHost(h) {
  if (!h) return '';
  return h.replace(/^www\./i, '').toLowerCase();
}

async function extractDom(page, internalHostNorm) {
  return page.evaluate((hostNorm) => {
    function normHost(h) {
      if (!h) return '';
      return h.replace(/^www\./i, '').toLowerCase();
    }

    const title = document.title || '';
    const metaNode = document.querySelector('meta[name="description"]');
    const metaDescription = (metaNode?.getAttribute('content') || '').trim();
    const canonNode = document.querySelector('link[rel="canonical"]');
    const canonicalUrl = (canonNode?.getAttribute('href') || '').trim() || null;
    const robotsNode = document.querySelector('meta[name="robots"]');
    const robotsMeta = (robotsNode?.getAttribute('content') || '').trim();

    const body = document.body?.innerText || '';
    const collapsed = body.replace(/\s+/g, ' ').trim();
    const visibleTextLength = collapsed.length;
    const wordCount = collapsed ? collapsed.split(/\s+/).filter(Boolean).length : 0;

    let internalLinksCount = 0;
    try {
      const originHostNorm = normHost(window.location.hostname);
      for (const a of document.querySelectorAll('a[href]')) {
        const href = (a.getAttribute('href') || '').trim();
        if (!href || href.startsWith('#')) continue;
        let u;
        try {
          u = new URL(href, window.location.href);
        } catch {
          continue;
        }
        const linkHost = normHost(u.hostname);
        if (!linkHost || linkHost === originHostNorm || linkHost === hostNorm) {
          internalLinksCount++;
        }
      }
    } catch (e) {
      /* keep internalLinksCount best-effort */
    }

    const rl = robotsMeta.toLowerCase();
    const noindex = rl.includes('noindex');
    const nofollow = rl.includes('nofollow');

    return {
      title,
      meta_description: metaDescription,
      canonical_url: canonicalUrl,
      robots_meta: robotsMeta,
      visible_text_length: visibleTextLength,
      word_count: wordCount,
      internal_links_count: internalLinksCount,
      indexability: { noindex, nofollow },
    };
  }, internalHostNorm);
}

async function runBatch(opts) {
  const {
    urls = [],
    internal_host: internalHost = '',
    navigation_timeout_ms: navigationTimeoutMs = 30000,
    settle_after_load_ms: settleMs = 1500,
    block_heavy_assets: blockHeavyAssets = true,
    emit_body_html: emitBodyHtml = false,
    body_html_max_chars: bodyHtmlMaxChars = 250000,
    session_cookies: sessionCookies = [],
  } = opts;

  const internalHostNorm = normHost(internalHost);
  const results = [];
  let browser = null;

  try {
    browser = await chromium.launch({
      headless: true,
      args: ['--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu'],
    });

    for (const url of urls) {
      let ctx = null;
      let page = null;
      let blockedResourceAborts = 0;
      let failedRequestCount = 0;
      let navigationError = null;
      let navResponse = null;
      let finalUrl = url;
      let httpStatus = null;
      let xRobotsTag = null;

      try {
        ctx = await browser.newContext({
          userAgent:
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 BacklinkProBot/1.0',
          viewport: { width: 1280, height: 800 },
        });
        if (Array.isArray(sessionCookies) && sessionCookies.length > 0) {
          try {
            await ctx.addCookies(sessionCookies);
          } catch {
            /* continue without session */
          }
        }
        page = await ctx.newPage();

        page.on('requestfailed', () => {
          failedRequestCount += 1;
        });

        if (blockHeavyAssets) {
          await page.route('**/*', (route) => {
            const type = route.request().resourceType();
            if (type === 'image' || type === 'media' || type === 'font') {
              blockedResourceAborts += 1;
              return route.abort();
            }
            return route.continue();
          });
        }

        try {
          navResponse = await page.goto(url, {
            waitUntil: 'domcontentloaded',
            timeout: navigationTimeoutMs,
          });
          if (navResponse) {
            httpStatus = navResponse.status();
            const h = navResponse.headers();
            xRobotsTag = h['x-robots-tag'] ?? h['X-Robots-Tag'] ?? null;
          }
          finalUrl = page.url();
          await new Promise((resolve) => {
            setTimeout(resolve, settleMs);
          });
        } catch (e) {
          navigationError = e?.message || String(e);
          try {
            finalUrl = page.url();
          } catch {
            /* ignore */
          }
        }

        const extracted =
          navigationError && !(await page.content().catch(() => ''))
            ? {
                title: '',
                meta_description: '',
                canonical_url: null,
                robots_meta: '',
                visible_text_length: 0,
                word_count: 0,
                internal_links_count: 0,
                indexability: { noindex: false, nofollow: false },
              }
            : await extractDom(page, internalHostNorm);

        const hdrRobotsLower = (xRobotsTag || '').toLowerCase();
        const renderedIndexability = {
          noindex:
            extracted.indexability.noindex || hdrRobotsLower.includes('noindex'),
          nofollow:
            extracted.indexability.nofollow || hdrRobotsLower.includes('nofollow'),
          x_robots_tag: xRobotsTag,
        };

        let bodyHtml = null;
        if (emitBodyHtml && !navigationError) {
          try {
            const raw = await page.content();
            const cap = Math.max(0, Number(bodyHtmlMaxChars) || 250000);
            bodyHtml = typeof raw === 'string' ? raw.slice(0, cap) : '';
          } catch {
            bodyHtml = '';
          }
        }

        results.push({
          url,
          ok: !navigationError,
          error: navigationError,
          http_status: httpStatus,
          final_url: finalUrl,
          blocked_resource_aborts: blockedResourceAborts,
          failed_request_count: failedRequestCount,
          rendered: {
            title: extracted.title,
            meta_description: extracted.meta_description,
            canonical_url: extracted.canonical_url,
            robots_meta: extracted.robots_meta,
            x_robots_tag: xRobotsTag,
            visible_text_length: extracted.visible_text_length,
            word_count: extracted.word_count,
            internal_links_count: extracted.internal_links_count,
            indexability: renderedIndexability,
            body_html: bodyHtml,
          },
        });
      } catch (e) {
        results.push({
          url,
          ok: false,
          error: e?.message || String(e),
          http_status: null,
          final_url: url,
          blocked_resource_aborts: blockedResourceAborts,
          failed_request_count: failedRequestCount,
          rendered: null,
        });
      } finally {
        try {
          if (ctx) await ctx.close();
        } catch {
          /* ignore */
        }
      }
    }
  } finally {
    if (browser) {
      await browser.close().catch(() => {});
    }
  }

  return { results };
}

const raw = await readStdin();
let inputJson;
try {
  inputJson = JSON.parse(raw || '{}');
} catch (e) {
  stdout.write(JSON.stringify({ ok: false, error: 'invalid_json' }));
  process.exit(1);
}

try {
  const out = await runBatch(inputJson);
  stdout.write(JSON.stringify({ ok: true, ...out }));
} catch (e) {
  stdout.write(
    JSON.stringify({ ok: false, error: e?.message || String(e), results: [] }),
  );
  process.exit(1);
}
