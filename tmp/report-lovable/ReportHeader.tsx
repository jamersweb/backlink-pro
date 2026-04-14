import { Globe, Calendar, TrendingUp, CheckCircle2, Plug, Clock } from "lucide-react";

const getScoreColor = (score: number) => {
  if (score >= 80) return "text-success";
  if (score >= 60) return "text-warning";
  return "text-danger";
};

const getStatusBadge = (score: number) => {
  if (score >= 80) return { label: "Good", className: "bg-success/10 text-success" };
  if (score >= 60) return { label: "Needs Improvement", className: "bg-warning/10 text-warning" };
  return { label: "Critical", className: "bg-danger/10 text-danger" };
};

const apis = [
  { name: "PageSpeed", connected: true },
  { name: "GA4", connected: true },
  { name: "GSC", connected: true },
  { name: "Lighthouse", connected: false },
  { name: "Ahrefs", connected: false },
];

const ReportHeader = () => {
  const score = 72;
  const status = getStatusBadge(score);
  const circumference = 2 * Math.PI * 45;
  const offset = circumference - (score / 100) * circumference;

  return (
    <div className="rounded-xl bg-card p-8 card-shadow-md">
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div className="space-y-3">
          <div className="flex items-center gap-2 text-muted-foreground text-sm">
            <Globe className="w-4 h-4" />
            <span className="font-medium">www.example.com</span>
          </div>
          <h1 className="text-2xl font-bold text-foreground">SEO Audit Report</h1>
          <div className="flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
            <span className="flex items-center gap-1.5">
              <Calendar className="w-3.5 h-3.5" />
              Generated: February 10, 2026
            </span>
            <span className="flex items-center gap-1.5">
              <Clock className="w-3.5 h-3.5" />
              Domain Age: 4 years, 7 months
            </span>
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ${status.className}`}>
              {status.label}
            </span>
          </div>
          <div className="flex flex-wrap items-center gap-2 pt-1">
            {apis.map((api) => (
              <span
                key={api.name}
                className={`inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full ${
                  api.connected ? "bg-success/10 text-success" : "bg-muted text-muted-foreground"
                }`}
              >
                {api.connected ? <CheckCircle2 className="w-3 h-3" /> : <Plug className="w-3 h-3" />}
                {api.name}
              </span>
            ))}
          </div>
        </div>

        <div className="flex items-center gap-4">
          <div className="relative w-28 h-28">
            <svg className="w-28 h-28 -rotate-90" viewBox="0 0 100 100">
              <circle cx="50" cy="50" r="45" fill="none" stroke="hsl(var(--border))" strokeWidth="6" />
              <circle
                cx="50" cy="50" r="45" fill="none" stroke="currentColor" strokeWidth="6"
                strokeLinecap="round" strokeDasharray={circumference} strokeDashoffset={offset}
                className={`${getScoreColor(score)} transition-all duration-1000 ease-out`}
              />
            </svg>
            <div className="absolute inset-0 flex flex-col items-center justify-center">
              <span className={`text-3xl font-bold ${getScoreColor(score)}`}>{score}</span>
              <span className="text-xs text-muted-foreground">/ 100</span>
            </div>
          </div>
          <div className="text-sm text-muted-foreground">
            <div className="flex items-center gap-1">
              <TrendingUp className="w-3.5 h-3.5 text-success" />
              <span className="text-success font-medium">+5</span>
              <span>vs last month</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ReportHeader;
