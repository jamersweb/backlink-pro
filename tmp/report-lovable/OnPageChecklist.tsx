import { CheckCircle2, AlertTriangle, XCircle } from "lucide-react";

type Status = "pass" | "warning" | "fail";

interface CheckItem {
  label: string;
  status: Status;
  detail: string;
}

const checks: CheckItem[] = [
  { label: "Title Tags", status: "pass", detail: "All pages have unique title tags under 60 characters" },
  { label: "Meta Descriptions", status: "fail", detail: "8 pages are missing meta descriptions" },
  { label: "Headings Structure", status: "warning", detail: "5 pages have duplicate H1 tags" },
  { label: "Image ALT Tags", status: "warning", detail: "12 images missing ALT attributes" },
  { label: "Internal Links", status: "fail", detail: "3 broken internal links detected" },
  { label: "Canonical Tags", status: "pass", detail: "Canonical tags properly configured" },
];

const statusConfig: Record<Status, { icon: React.ElementType; className: string; bgClassName: string }> = {
  pass: { icon: CheckCircle2, className: "text-success", bgClassName: "bg-success/10" },
  warning: { icon: AlertTriangle, className: "text-warning", bgClassName: "bg-warning/10" },
  fail: { icon: XCircle, className: "text-danger", bgClassName: "bg-danger/10" },
};

const OnPageChecklist = () => {
  return (
    <div className="rounded-xl bg-card p-6 card-shadow">
      <h2 className="text-lg font-semibold text-foreground mb-6">On-Page SEO Checklist</h2>
      <div className="space-y-3">
        {checks.map((check) => {
          const config = statusConfig[check.status];
          const Icon = config.icon;
          return (
            <div
              key={check.label}
              className={`flex items-start gap-3 p-3.5 rounded-lg ${config.bgClassName}`}
            >
              <Icon className={`w-5 h-5 mt-0.5 flex-shrink-0 ${config.className}`} />
              <div className="flex-1 min-w-0">
                <div className="flex items-center justify-between">
                  <span className="text-sm font-semibold text-foreground">{check.label}</span>
                </div>
                <p className="text-xs text-muted-foreground mt-0.5">{check.detail}</p>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};

export default OnPageChecklist;
