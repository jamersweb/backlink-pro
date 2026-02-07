<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class WidgetController extends Controller
{
    /**
     * Serve widget JavaScript
     */
    public function js(Request $request)
    {
        $apiKey = $request->query('key');
        $orgSlug = $request->query('org');

        // Verify API key
        if (!$apiKey) {
            abort(400, 'API key required');
        }

        $key = ApiKey::verify($apiKey);
        if (!$key || !$key->is_active) {
            abort(401, 'Invalid API key');
        }

        $organization = $key->organization;
        $branding = $organization->brandingProfile;

        // Generate widget JavaScript
        $js = $this->generateWidgetJs($organization, $branding, $apiKey);

        return Response::make($js, 200, [
            'Content-Type' => 'application/javascript',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Generate widget JavaScript code
     */
    protected function generateWidgetJs(Organization $organization, $branding, string $apiKey): string
    {
        $primaryColor = $branding->primary_color ?? '#4299e1';
        $buttonText = 'Get Free SEO Audit';

        return <<<JS
(function() {
    'use strict';
    
    // Widget configuration
    const config = {
        apiKey: '{$apiKey}',
        apiUrl: '{$this->getApiUrl()}',
        primaryColor: '{$primaryColor}',
        buttonText: '{$buttonText}',
    };

    // Extract UTM parameters
    function getUtmParams() {
        const params = new URLSearchParams(window.location.search);
        return {
            utm_source: params.get('utm_source'),
            utm_medium: params.get('utm_medium'),
            utm_campaign: params.get('utm_campaign'),
            utm_term: params.get('utm_term'),
            utm_content: params.get('utm_content'),
        };
    }

    // Create widget HTML
    function createWidget() {
        const widgetId = 'blp-audit-widget-' + Date.now();
        const widget = document.createElement('div');
        widget.id = widgetId;
        widget.innerHTML = `
            <div style="max-width: 400px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                <h3 style="margin-top: 0; color: #1a202c;">Free SEO Audit</h3>
                <form id="blp-widget-form-${widgetId}">
                    <div style="margin-bottom: 15px;">
                        <input type="url" name="url" placeholder="Enter your website URL" required 
                               style="width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 4px; font-size: 14px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <input type="email" name="email" placeholder="Your email" required 
                               style="width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 4px; font-size: 14px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <input type="text" name="name" placeholder="Your name (optional)" 
                               style="width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 4px; font-size: 14px;">
                    </div>
                    <button type="submit" 
                            style="width: 100%; padding: 12px; background-color: ${config.primaryColor}; color: white; border: none; border-radius: 4px; font-size: 16px; font-weight: 600; cursor: pointer;">
                        ${config.buttonText}
                    </button>
                    <div id="blp-widget-message-${widgetId}" style="margin-top: 15px; display: none;"></div>
                </form>
            </div>
        `;

        // Handle form submission
        const form = widget.querySelector(`#blp-widget-form-${widgetId}`);
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const data = {
                url: formData.get('url'),
                email: formData.get('email'),
                name: formData.get('name'),
                utm: getUtmParams(),
                referrer: document.referrer,
            };

            const messageDiv = widget.querySelector(`#blp-widget-message-${widgetId}`);
            messageDiv.style.display = 'block';
            messageDiv.innerHTML = '<p style="color: #4299e1;">Processing your audit...</p>';

            try {
                const response = await fetch(config.apiUrl + '/public/widget/audit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-KEY': config.apiKey,
                    },
                    body: JSON.stringify(data),
                });

                const result = await response.json();

                if (response.ok) {
                    messageDiv.innerHTML = `
                        <p style="color: #48bb78;">✓ Audit started! Check your email for the report link.</p>
                        <p style="font-size: 12px; color: #718096;">Or <a href="${result.report_url}" target="_blank" style="color: ${config.primaryColor};">view it now</a></p>
                    `;
                    form.style.display = 'none';
                } else {
                    messageDiv.innerHTML = `<p style="color: #e53e3e;">Error: ${result.error || 'Failed to start audit'}</p>`;
                }
            } catch (error) {
                messageDiv.innerHTML = '<p style="color: #e53e3e;">Error: Failed to connect. Please try again.</p>';
            }
        });

        return widget;
    }

    // Auto-inject widget if container exists
    const container = document.querySelector('[data-blp-widget]');
    if (container) {
        container.appendChild(createWidget());
    }

    // Export for manual initialization
    window.BacklinkProWidget = {
        init: function(containerSelector) {
            const container = document.querySelector(containerSelector);
            if (container) {
                container.appendChild(createWidget());
            }
        }
    };
})();
JS;
    }

    /**
     * Get API URL
     */
    protected function getApiUrl(): string
    {
        return config('app.url') . '/api';
    }
}
