/**
 * Forms login for SEO audit — stdin JSON -> stdout JSON with session cookies.
 * Never writes credentials to disk.
 *
 * Input: {
 *   login_url, username, password,
 *   username_selector?, password_selector?, submit_selector?, success_indicator?,
 *   navigation_timeout_ms?, settle_after_login_ms?
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

function splitSelectors(sel) {
  if (!sel || typeof sel !== 'string') return [];
  return sel
    .split(',')
    .map((s) => s.trim())
    .filter(Boolean);
}

async function fillFirst(page, selectors, value, timeout, defaults) {
  const list = selectors.length ? selectors : defaults;
  let lastErr = null;
  for (const s of list) {
    try {
      const loc = page.locator(s).first();
      await loc.waitFor({ state: 'visible', timeout: Math.min(timeout, 15000) });
      await loc.fill(String(value));
      return true;
    } catch (e) {
      lastErr = e;
    }
  }
  if (lastErr) throw lastErr;
  return false;
}

async function clickFirst(page, selectors, timeout) {
  const list = selectors.length ? selectors : ['button[type="submit"]', 'input[type="submit"]', '[type="submit"]'];
  for (const s of list) {
    try {
      const loc = page.locator(s).first();
      await loc.waitFor({ state: 'visible', timeout: Math.min(timeout, 15000) });
      await loc.click();
      return true;
    } catch {
      /* try next */
    }
  }
  return false;
}

function parseIndicator(raw) {
  if (!raw || typeof raw !== 'string') return null;
  const t = raw.trim();
  if (t.startsWith('url_contains:')) {
    return { type: 'url_contains', value: t.slice('url_contains:'.length).trim() };
  }
  if (t.startsWith('selector:')) {
    return { type: 'selector', value: t.slice('selector:'.length).trim() };
  }
  if (t.startsWith('text:')) {
    return { type: 'text', value: t.slice('text:'.length).trim() };
  }
  return { type: 'text', value: t };
}

async function checkSuccess(page, indicator, loginUrl, settleMs) {
  await new Promise((r) => setTimeout(r, settleMs));
  const url = page.url();
  if (indicator?.type === 'url_contains' && indicator.value) {
    return url.includes(indicator.value);
  }
  if (indicator?.type === 'selector' && indicator.value) {
    try {
      return await page.locator(indicator.value).first().isVisible();
    } catch {
      return false;
    }
  }
  if (indicator?.type === 'text' && indicator.value) {
    try {
      const html = await page.content();
      return html.includes(indicator.value);
    } catch {
      return false;
    }
  }
  /* default: URL changed away from login path, or password field gone */
  try {
    let loginPath = '';
    try {
      loginPath = new URL(loginUrl).pathname.replace(/\/$/, '') || '';
    } catch {
      loginPath = '';
    }
    const curPath = new URL(url).pathname.replace(/\/$/, '') || '';
    if (loginPath && curPath !== loginPath && !curPath.startsWith(loginPath + '/')) {
      return true;
    }
    const pwd = await page.locator('input[type="password"]').count();
    return pwd === 0;
  } catch {
    return true;
  }
}

async function runAuth(opts) {
  const loginUrl = opts.login_url;
  const username = opts.username;
  const password = opts.password;
  const navTimeout = Number(opts.navigation_timeout_ms) || 45000;
  const settleMs = Number(opts.settle_after_login_ms) || 2500;

  const browser = await chromium.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu'],
  });

  let ctx = null;
  try {
    ctx = await browser.newContext({
      userAgent:
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 BacklinkProBot/1.0',
      viewport: { width: 1280, height: 800 },
    });
    const page = await ctx.newPage();

    await page.goto(loginUrl, { waitUntil: 'domcontentloaded', timeout: navTimeout });

    const uSel = splitSelectors(opts.username_selector);
    const pSel = splitSelectors(opts.password_selector);
    await fillFirst(page, uSel, username, navTimeout, [
      'input[type="email"]',
      'input[name="username"]',
      'input[name="email"]',
      '#username',
      'input[name="user"]',
    ]);
    await fillFirst(page, pSel, password, navTimeout, [
      'input[type="password"]',
      'input[name="password"]',
      '#password',
    ]);

    const subSel = splitSelectors(opts.submit_selector);
    const clicked = await clickFirst(page, subSel, navTimeout);
    if (!clicked) {
      await page.keyboard.press('Enter');
    }

    try {
      await page.waitForLoadState('networkidle', { timeout: Math.min(navTimeout, 30000) });
    } catch {
      /* soft */
    }

    const indicator = parseIndicator(opts.success_indicator);
    const ok = await checkSuccess(page, indicator, loginUrl, settleMs);
    const cookies = await ctx.cookies();
    const finalUrl = page.url();

    return {
      ok,
      error: ok ? null : 'success_indicator_not_met',
      cookies,
      final_url: finalUrl,
    };
  } finally {
    if (ctx) await ctx.close().catch(() => {});
    await browser.close().catch(() => {});
  }
}

const raw = await readStdin();
let inputJson;
try {
  inputJson = JSON.parse(raw || '{}');
} catch {
  stdout.write(JSON.stringify({ ok: false, error: 'invalid_json', cookies: [] }));
  process.exit(1);
}

try {
  const out = await runAuth(inputJson);
  stdout.write(JSON.stringify({ ok: out.ok, error: out.error, cookies: out.cookies, final_url: out.final_url }));
} catch (e) {
  stdout.write(JSON.stringify({ ok: false, error: e?.message || String(e), cookies: [] }));
  process.exit(1);
}
