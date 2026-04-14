import { Smartphone, CheckCircle2, AlertTriangle, XCircle, Monitor } from "lucide-react";

interface MobileItem {
  label: string;
  status: "pass" | "warning" | "fail";
  detail: string;
}

const mobileChecks: MobileItem[] = [
  { label: "Mobile Friendliness", status: "pass", detail: "Site passes Google's mobile-friendly test" },
  { label: "Responsive Design", status: "pass", detail: "All pages adapt correctly to mobile viewports" },
  { label: "Touch Target Sizing", status: "warning", detail: "4 interactive elements are too small (< 48px tap target)" },
  { label: "CLS on Mobile", status: "pass", detail: "Cumulative Layout Shift is 0.05 — within good threshold" },
  { label: "Layout Stability", status: "pass", detail: "No significant layout shifts during page load" },
  { label: "Viewport Meta Tag", status: "pass", detail: "Properly configured viewport meta tag on all pages" },
  { label: "Font Legibility", status: "pass", detail: "Base font size ≥ 16px; readable without zooming" },
  { label: "Content Width", status: "warning", detail: "2 pages have horizontal scrolling on small screens" },
];

const statusConfig = {
  pass: { icon: CheckCircle2, className: "text-success", bgClassName: "bg-success/10" },
  warning: { icon: AlertTriangle, className: "text-warning", bgClassName: "bg-warning/10" },
  fail: { icon: XCircle, className: "text-danger", bgClassName: "bg-danger/10" },
};

const MobileUX = () => {
  const mobileScore = 91;
  const circumference = 2 * Math.PI * 36;
  const offset = circumference - (mobileScore / 100) * circumference;
  const color = mobileScore >= 80 ? "text-success" : mobileScore >= 60 ? "text-warning" : "text-danger";

  return (
    <div className="rounded-xl bg-card p-6 card-shadow-md">
      <div className="flex items-center gap-2 mb-6">
        <Smartphone className="w-5 h-5 text-primary" />
        <h2 className="text-lg font-semibold text-foreground">Mobile & UX Analysis</h2>
      </div>

      <div className="flex flex-col md:flex-row gap-6">
        <div className="flex flex-col items-center gap-2 flex-shrink-0">
          <div className="relative w-28 h-28">
            <svg className="w-28 h-28 -rotate-90" viewBox="0 0 80 80">
              <circle cx="40" cy="40" r="36" fill="none" stroke="hsl(var(--border))" strokeWidth="5" />
              <circle
                cx="40" cy="40" r="36" fill="none" stroke="currentColor" strokeWidth="5"
                strokeLinecap="round" strokeDasharray={circumference} strokeDashoffset={offset}
                className={`${color} transition-all duration-1000`}
              />
            </svg>
            <div className="absolute inset-0 flex flex-col items-center justify-center">
              <span className={`text-2xl font-bold ${color}`}>{mobileScore}</span>
              <span className="text-xs text-muted-foreground">/ 100</span>
            </div>
          </div>
          <span className="text-xs font-medium text-muted-foreground">Mobile Score</span>
        </div>

        <div className="flex-1 space-y-2.5">
          {mobileChecks.map((item) => {
            const config = statusConfig[item.status];
            const Icon = config.icon;
            return (
              <div key={item.label} className={`flex items-start gap-3 p-3 rounded-lg ${config.bgClassName}`}>
                <Icon className={`w-4 h-4 mt-0.5 flex-shrink-0 ${config.className}`} />
                <div className="flex-1 min-w-0">
                  <span className="text-sm font-semibold text-foreground">{item.label}</span>
                  <p className="text-xs text-muted-foreground mt-0.5">{item.detail}</p>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
};

export default MobileUX;
