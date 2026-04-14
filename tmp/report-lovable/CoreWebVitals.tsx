import { Monitor, Smartphone } from "lucide-react";

interface Metric {
  name: string;
  value: string;
  score: number;
  status: "good" | "fair" | "poor";
}

const metrics: Metric[] = [
  { name: "LCP (Largest Contentful Paint)", value: "2.1s", score: 78, status: "fair" },
  { name: "CLS (Cumulative Layout Shift)", value: "0.05", score: 92, status: "good" },
  { name: "INP (Interaction to Next Paint)", value: "180ms", score: 65, status: "fair" },
];

const statusConfig = {
  good: { label: "Good", className: "bg-success/10 text-success" },
  fair: { label: "Needs Work", className: "bg-warning/10 text-warning" },
  poor: { label: "Poor", className: "bg-danger/10 text-danger" },
};

const getBarColor = (status: string) => {
  if (status === "good") return "bg-success";
  if (status === "fair") return "bg-warning";
  return "bg-danger";
};

const CoreWebVitals = () => {
  return (
    <div className="rounded-xl bg-card p-6 card-shadow">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-lg font-semibold text-foreground">Performance & Core Web Vitals</h2>
        <div className="flex items-center gap-3">
          <div className="flex items-center gap-1.5 text-sm text-muted-foreground">
            <Smartphone className="w-4 h-4" />
            <span className="font-medium">Mobile: <span className="text-warning">68</span></span>
          </div>
          <div className="w-px h-4 bg-border" />
          <div className="flex items-center gap-1.5 text-sm text-muted-foreground">
            <Monitor className="w-4 h-4" />
            <span className="font-medium">Desktop: <span className="text-success">89</span></span>
          </div>
        </div>
      </div>

      <div className="space-y-5">
        {metrics.map((metric) => {
          const config = statusConfig[metric.status];
          return (
            <div key={metric.name} className="space-y-2">
              <div className="flex items-center justify-between">
                <span className="text-sm font-medium text-foreground">{metric.name}</span>
                <div className="flex items-center gap-3">
                  <span className="text-sm font-semibold text-foreground">{metric.value}</span>
                  <span className={`text-xs font-medium px-2 py-0.5 rounded-full ${config.className}`}>
                    {config.label}
                  </span>
                </div>
              </div>
              <div className="w-full h-2 bg-muted rounded-full overflow-hidden">
                <div
                  className={`h-full rounded-full ${getBarColor(metric.status)} transition-all duration-700`}
                  style={{ width: `${metric.score}%` }}
                />
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};

export default CoreWebVitals;
