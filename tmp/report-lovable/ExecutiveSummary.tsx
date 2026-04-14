import { FileText } from "lucide-react";

const ExecutiveSummary = () => {
  return (
    <div className="rounded-xl bg-card p-6 card-shadow-md">
      <div className="flex items-center gap-2 mb-4">
        <div className="w-8 h-8 rounded-lg bg-accent flex items-center justify-center">
          <FileText className="w-4 h-4 text-accent-foreground" />
        </div>
        <h2 className="text-lg font-semibold text-foreground">Executive Summary</h2>
      </div>

      <div className="space-y-4 text-sm text-muted-foreground leading-relaxed">
        <p>
          The overall SEO health of <span className="font-semibold text-foreground">www.example.com</span> is rated at{" "}
          <span className="font-semibold text-warning">72 out of 100</span>, indicating the site has a solid foundation but requires targeted improvements to achieve optimal search visibility and organic performance.
        </p>
        <p>
          <span className="font-semibold text-foreground">Performance</span> is generally strong on desktop (89/100) but lags on mobile (68/100), primarily due to elevated Largest Contentful Paint (LCP) times. Core Web Vitals show room for improvement, particularly in interaction responsiveness (INP: 180ms).
        </p>
        <p>
          <span className="font-semibold text-foreground">Traffic & Visibility</span> have shown a positive upward trend over the past 6 months, with estimated organic traffic reaching 12,450 monthly visits. Search visibility improved by approximately 47% since September, driven by improved rankings for key commercial terms.
        </p>
        <p>
          <span className="font-semibold text-foreground">Indexing</span> is healthy with 94% of submitted pages successfully indexed. However, 18 pages remain non-indexed due to crawl anomalies and redirect issues that should be addressed promptly.
        </p>
        <p>
          <span className="font-semibold text-foreground">Authority & Backlinks</span> present a moderate profile with 458 total backlinks from 127 referring domains. The backlink profile is largely clean with a low spam score, though the domain would benefit from a focused link-building strategy to increase referring domain diversity.
        </p>
        <p>
          Critical action items include fixing 8 missing meta descriptions, resolving 3 broken internal links, and improving mobile page speed. Addressing these high-priority issues is expected to yield measurable ranking improvements within 4–8 weeks.
        </p>
      </div>
    </div>
  );
};

export default ExecutiveSummary;
