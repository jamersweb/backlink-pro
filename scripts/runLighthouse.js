#!/usr/bin/env node

/**
 * Lighthouse Runner Script
 * 
 * Runs Lighthouse audit on a URL and outputs JSON results.
 * Usage: node scripts/runLighthouse.js <url> <preset>
 * 
 * Preset: mobile|desktop
 * Output: JSON to stdout
 */

import lighthouse from 'lighthouse';
import chromeLauncher from 'chrome-launcher';
import * as fs from 'fs';

const url = process.argv[2];
const preset = process.argv[3] || 'mobile';

if (!url) {
    console.error('Usage: node scripts/runLighthouse.js <url> <preset>');
    process.exit(1);
}

if (!['mobile', 'desktop'].includes(preset)) {
    console.error('Preset must be "mobile" or "desktop"');
    process.exit(1);
}

async function runLighthouse() {
    let chrome = null;
    
    try {
        // Launch Chrome
        chrome = await chromeLauncher.launch({
            chromeFlags: [
                '--headless',
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
            ],
        });

        const options = {
            logLevel: 'error',
            output: 'json',
            onlyCategories: ['performance', 'accessibility'],
            port: chrome.port,
        };

        // Configure preset
        if (preset === 'mobile') {
            options.emulatedFormFactor = 'mobile';
            options.throttling = {
                rttMs: 150,
                throughputKbps: 1638.4,
                cpuSlowdownMultiplier: 4,
            };
        } else {
            options.emulatedFormFactor = 'desktop';
            options.throttling = {
                rttMs: 40,
                throughputKbps: 10240,
                cpuSlowdownMultiplier: 1,
            };
        }

        // Run Lighthouse
        const runnerResult = await lighthouse(url, options);

        // Extract metrics
        const metrics = runnerResult.lhr.audits;
        const performanceScore = runnerResult.lhr.categories.performance.score * 100;
        const accessibilityScore = runnerResult.lhr.categories.accessibility?.score !== undefined
            ? Math.round(runnerResult.lhr.categories.accessibility.score * 100)
            : null;

        // Extract Core Web Vitals and other metrics
        const result = {
            score: Math.round(performanceScore),
            fcp: metrics['first-contentful-paint']?.numericValue ? Math.round(metrics['first-contentful-paint'].numericValue) : null,
            lcp: metrics['largest-contentful-paint']?.numericValue ? Math.round(metrics['largest-contentful-paint'].numericValue) : null,
            cls: metrics['cumulative-layout-shift']?.numericValue ? Math.round(metrics['cumulative-layout-shift'].numericValue * 1000) / 1000 : null,
            tbt: metrics['total-blocking-time']?.numericValue ? Math.round(metrics['total-blocking-time'].numericValue) : null,
            tti: metrics['interactive']?.numericValue ? Math.round(metrics['interactive'].numericValue) : null,
            si: metrics['speed-index']?.numericValue ? Math.round(metrics['speed-index'].numericValue) : null,
            accessibility_score: accessibilityScore,
            tap_targets_ok: metrics['tap-targets']?.score !== undefined ? metrics['tap-targets'].score >= 1 : null,
            font_size_ok: metrics['font-size']?.score !== undefined ? metrics['font-size'].score >= 1 : null,
            opportunities: [],
        };

        // Extract top opportunities
        const opportunityKeys = [
            'render-blocking-resources',
            'unused-css-rules',
            'unused-javascript',
            'modern-image-formats',
            'offscreen-images',
            'unminified-css',
            'unminified-javascript',
            'efficient-animated-content',
        ];

        opportunityKeys.forEach(key => {
            const audit = metrics[key];
            if (audit && audit.score !== null && audit.score < 1) {
                result.opportunities.push({
                    id: key,
                    title: audit.title,
                    description: audit.description,
                    score: Math.round(audit.score * 100),
                    savings: audit.details?.overallSavingsMs || audit.details?.overallSavingsBytes || null,
                    savingsType: audit.details?.overallSavingsMs ? 'ms' : (audit.details?.overallSavingsBytes ? 'bytes' : null),
                });
            }
        });

        // Sort opportunities by score (worst first)
        result.opportunities.sort((a, b) => a.score - b.score);
        result.opportunities = result.opportunities.slice(0, 8); // Top 8

        // Output JSON
        console.log(JSON.stringify(result));

    } catch (error) {
        // Output error as JSON to stdout (so Laravel can read it)
        console.log(JSON.stringify({
            error: error.message,
            available: false,
        }));
        process.exit(1);
    } finally {
        if (chrome) {
            await chrome.kill();
        }
    }
}

runLighthouse().catch(error => {
    // Output error as JSON to stdout
    console.log(JSON.stringify({
        error: error.message,
        available: false,
    }));
    process.exit(1);
});
