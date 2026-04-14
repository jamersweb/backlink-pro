import { FileText, AlertTriangle, CheckCircle2, Search } from "lucide-react";

const contentIssues = [
  { label: "Thin Content Pages", count: 6, status: "warning" as const, detail: "Pages with less than 300 words of meaningful content" },
  { label: "Duplicate Content", count: 2, status: "warning" as const, detail: "Near-duplicate pages detected that may cause ranking dilution" },
  { label: "Content Freshness", count: 0, status: "pass" as const, detail: "85% of key pages updated within last 6 months" },
  { label: "Keyword Cannibalization", count: 3, status: "warning" as const, detail: "Multiple pages competing for the same target keywords" },
];

const keywordVisibility = [
  { range: "Top 3", count: 8, color: "bg-success" },
  { range: "Top 10", count: 24, color: "bg-primary" },
  { range: "Top 20", count: 42, color: "bg-accent-foreground/20" },
  { range: "Top 50", count: 78, color: "bg-muted-foreground/20" },
];

const statusConfig = {
  pass: { icon: CheckCircle2, className: "text-success", bgClassName: "bg-success/10" },
  warning: { icon: AlertTriangle, className: "text-warning", bgClassName: "bg-warning/10" },
};

const ContentKeywords = () => {
  return (
    <div className="rounded-xl bg-card p-6 card-shadow-md">
      <div className="flex items-center gap-2 mb-6">
        <FileText className="w-5 h-5 text-primary" />
        <h2 className="text-lg font-semibold text-foreground">Content & Keyword Insights</h2>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div>
          <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3">Content Quality</h3>
          <div className="space-y-3">
            {contentIssues.map((item) => {
              const config = statusConfig[item.status];
              const Icon = config.icon;
              return (
                <div key={item.label} className={`flex items-start gap-3 p-3 rounded-lg ${config.bgClassName}`}>
                  <Icon className={`w-4 h-4 mt-0.5 flex-shrink-0 ${config.className}`} />
                  <div className="flex-1">
                    <div className="flex items-center justify-between">
                      <span className="text-sm font-medium text-foreground">{item.label}</span>
                      {item.count > 0 && (
                        <span className="text-xs font-bold text-foreground bg-card px-2 py-0.5 rounded-full">{item.count}</span>
                      )}
                    </div>
                    <p className="text-xs text-muted-foreground mt-0.5">{item.detail}</p>
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        <div>
          <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3">Keyword Visibility</h3>
          <div className="space-y-4">
            {keywordVisibility.map((item) => (
              <div key={item.range} className="space-y-1.5">
                <div className="flex items-center justify-between">
                  <span className="text-sm font-medium text-foreground">{item.range}</span>
                  <span className="text-sm font-bold text-foreground">{item.count} keywords</span>
                </div>
                <div className="w-full h-2 bg-muted rounded-full overflow-hidden">
                  <div className={`h-full rounded-full ${item.color}`} style={{ width: `${(item.count / 78) * 100}%` }} />
                </div>
              </div>
            ))}
          </div>

          <div className="mt-6 p-4 rounded-lg bg-muted">
            <div className="flex items-center gap-2 mb-1">
              <Search className="w-4 h-4 text-primary" />
              <span className="text-xs text-muted-foreground font-medium">Total Tracked Keywords</span>
            </div>
            <span className="text-2xl font-bold text-foreground">152</span>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ContentKeywords;
