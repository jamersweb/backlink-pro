import { onMounted, onUnmounted } from 'vue';

export function useReveal() {
    let observer = null;

    const init = () => {
        if (typeof window === 'undefined') return;

        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        // Optionally unobserve after reveal
                        observer.unobserve(entry.target);
                    }
                });
            },
            {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px',
            }
        );

        // Observe all elements with data-reveal
        document.querySelectorAll('[data-reveal]').forEach((el) => {
            observer.observe(el);
        });
    };

    onMounted(() => {
        init();
    });

    onUnmounted(() => {
        if (observer) {
            observer.disconnect();
        }
    });

    return { init };
}
