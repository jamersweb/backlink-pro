/**
 * Lightweight analytics tracking helper
 * Requires GTM dataLayer to be initialized
 */
export function track(event, payload = {}) {
    if (typeof window !== 'undefined' && window.dataLayer) {
        window.dataLayer.push({
            event,
            ...payload,
        });
    }
}

/**
 * Track CTA clicks
 */
export function trackCTA(label, location) {
    track('cta_click', {
        cta_label: label,
        cta_location: location,
    });
}

/**
 * Track form submissions
 */
export function trackFormSubmit(formName, success = true) {
    track('form_submit', {
        form_name: formName,
        success,
    });
}

/**
 * Track page views (if needed for SPA navigation)
 */
export function trackPageView(url, title) {
    track('page_view', {
        page_url: url,
        page_title: title,
    });
}
