import { Monitor, Smartphone, Plug, Gauge } from "lucide-react";

interface Metric {
  name: string;
  value: string;
  score: number;
  status: "good" | "fair" | "poor";
}

const mobileMetrics: Metric[] = [
  { name: "LCP (Largest Contentful Paint)", value: "2.1s", score: 78, status: "fair" },
  { name: "CLS (Cumulative Layout Shift)", value: "0.05", score: 92, status: "good" },
  { name: "INP (Interaction to Next Paint)", value: "180ms", score: 65, status: "fair" },
  { name: "FCP (First Contentful Paint)", value: "1.4s", score: 82, status: "good" },
  { name: "TTFB (Time to First Byte)", value: "0.6s", score: 88, status: "good" },
];

const desktopMetrics: Metric[] = [
  { name: "LCP (Largest Contentful Paint)", value: "1.2s", score: 94, status: "good" },
  { name: "CLS (Cumulative Layout Shift)", value: "0.02", score: 98, status: "good" },
  { name: "INP (Interaction to Next Paint)", value: "85ms", score: 90, status: "good" },
  { name: "FCP (First Contentful Paint)", value: "0.8s", score: 95, status: "good" },
  { name: "TTFB (Time to First Byte)", value: "0.3s", score: 96, status: "good" },
];

const statusConfig = {
  good: { label: "Good", className: "bg-success/10 text-success", barColor: "bg-success" },
  fair: { label: "Needs Work", className: "bg-warning/10 text-warning", barColor: "bg-warning" },
  poor: { label: "Poor", className: "bg-danger/10 text-danger", barColor: "bg-danger" },
};

const connected = true;

const ScoreRing = ({ score, label }: { score: number; label: string }) => {
  const circumference = 2 * Math.PI * 36;
  const offset = circumference - (score / 100) * circumference;
  const color = score >= 80 ? "text-success" : score >= 60 ? "text-warning" : "text-danger";

  return (
    <div className="flex flex-col items-center gap-2">
      <div className="relative w-24 h-24">
        <svg className="w-24 h-24 -rotate-90" viewBox="0 0 80 80">
          <circle cx="40" cy="40" r="36" fill="none" stroke="hsl(var(--border))" strokeWidth="5" />
          <circle
            cx="40" cy="40" r="36" fill="none" stroke="currentColor" strokeWidth="5"
            strokeLinecap="round" strokeDasharray={circumference} strokeDashoffset={offset}
            className={`${color} transition-all duration-1000`}
          />
        </svg>
        <div className="absolute inset-0 flex flex-col items-center justify-center">
          <span className={`text-xl font-bold ${color}`}>{score}</span>
        </div>
      </div>
      <span className="text-xs font-medium text-muted-foreground">{label}</span>
    </div>
  );
};

const MetricsList = ({ metrics }: { metrics: Metric[] }) => (
  <div className="space-y-4">
    {metrics.map((metric) => {
      const config = statusConfig[metric.status];
      return (
        <div key={metric.name} className="space-y-1.5">
          <div className="flex items-center justify-between">
            <span className="text-sm font-medium text-foreground">{metric.name}</span>
            <div className="flex items-center gap-2">
              <span className="text-sm font-semibold text-foreground">{metric.value}</span>
              <span className={`text-xs font-medium px-2 py-0.5 rounded-full ${config.className}`}>{config.label}</span>
            </div>
          </div>
          <div className="w-full h-1.5 bg-muted rounded-full overflow-hidden">
            <div className={`h-full rounded-full ${config.barColor}`} style={{ width: `${metric.score}%` }} />
          </div>
        </div>
      );
    })}
  </div>
);

const PageSpeedInsights = () => {
  if (!connected) {
    return (
      <div className="rounded-xl bg-card p-6 card-shadow-md">
        <div className="flex items-center gap-2 mb-4">
          <Gauge className="w-5 h-5 text-primary" />
          <h2 className="text-lg font-semibold text-foreground">PageSpeed Insights</h2>
        </div>
        <div className="flex flex-col items-center justify-center py-12 text-center">
          <div className="w-16 h-16 rounded-2xl bg-accent flex items-center justify-center mb-4">
            <Plug className="w-7 h-7 text-accent-foreground" />
          </div>
          <h3 className="font-semibold text-foreground mb-1">Connect PageSpeed Insights</h3>
          <p className="text-sm text-muted-foreground mb-4 max-w-sm">
            Connect to Google PageSpeed Insights API to get real-time performance data and Core Web Vitals.
          </p>
          <button className="px-5 py-2.5 rounded-lg bg-primary text-primary-foreground text-sm font-semibold hover:opacity-90 transition-opacity">
            Connect PageSpeed Insights
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="rounded-xl bg-card p-6 card-shadow-md">
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center gap-2">
          <Gauge className="w-5 h-5 text-primary" />
          <h2 className="text-lg font-semibold text-foreground">PageSpeed Insights</h2>
        </div>
        <span className="text-xs font-medium text-success bg-success/10 px-2.5 py-0.5 rounded-full">Connected</span>
      </div>

      <div className="flex justify-center gap-12 mb-8">
        <ScoreRing score={68} label="Mobile" />
        <ScoreRing score={89} label="Desktop" />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div>
          <div className="flex items-center gap-2 mb-4">
            <Smartphone className="w-4 h-4 text-muted-foreground" />
            <h3 className="text-sm font-semibold text-foreground">Mobile Metrics</h3>
          </div>
          <MetricsList metrics={mobileMetrics} />
        </div>
        <div>
          <div className="flex items-center gap-2 mb-4">
            <Monitor className="w-4 h-4 text-muted-foreground" />
            <h3 className="text-sm font-semibold text-foreground">Desktop Metrics</h3>
          </div>
          <MetricsList metrics={desktopMetrics} />
        </div>
      </div>
    </div>
  );
};

export default PageSpeedInsights;
