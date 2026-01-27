/**
 * Analytics tracking composable
 * Tracks CTA clicks, form submissions, and scroll depth
 */
export function useAnalytics() {
    const trackEvent = (eventName, eventData = {}) => {
        if (typeof window === 'undefined') return;
        
        // Google Analytics 4 (if configured)
        if (window.gtag) {
            window.gtag('event', eventName, eventData);
        }
        
        // Custom analytics endpoint (optional)
        if (window.analytics && typeof window.analytics.track === 'function') {
            window.analytics.track(eventName, eventData);
        }
        
        // Console log for debugging (remove in production)
        if (process.env.NODE_ENV === 'development') {
            console.log('Analytics Event:', eventName, eventData);
        }
    };

    const trackCTAClick = (ctaText, destination) => {
        trackEvent('cta_click', {
            cta_text: ctaText,
            destination: destination,
            page: window.location.pathname,
        });
    };

    const trackFormSubmit = (formName, success = true) => {
        trackEvent('form_submit', {
            form_name: formName,
            success: success,
            page: window.location.pathname,
        });
    };

    const trackScrollDepth = (depth) => {
        trackEvent('scroll_depth', {
            depth: depth,
            page: window.location.pathname,
        });
    };

    const initScrollTracking = () => {
        if (typeof window === 'undefined') return;
        
        const depths = [25, 50, 75, 100];
        const tracked = new Set();
        
        const handleScroll = () => {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const scrollPercent = Math.round((scrollTop / docHeight) * 100);
            
            depths.forEach((depth) => {
                if (scrollPercent >= depth && !tracked.has(depth)) {
                    tracked.add(depth);
                    trackScrollDepth(depth);
                }
            });
        };
        
        let ticking = false;
        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    handleScroll();
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });
    };

    return {
        trackEvent,
        trackCTAClick,
        trackFormSubmit,
        trackScrollDepth,
        initScrollTracking,
    };
}
