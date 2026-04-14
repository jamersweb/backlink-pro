import { FileSearch, CheckCircle2, AlertTriangle, XCircle, FileText, Bot } from "lucide-react";

interface IndexItem {
  label: string;
  status: "pass" | "warning" | "fail";
  detail: string;
}

const indexData: IndexItem[] = [
  { label: "Indexed Pages", status: "pass", detail: "282 of 300 submitted pages are indexed (94%)" },
  { label: "Non-Indexed Pages", status: "warning", detail: "18 pages excluded — crawl anomalies and redirect issues" },
  { label: "Sitemap Status", status: "pass", detail: "sitemap.xml is valid and submitted to Google" },
  { label: "Robots.txt", status: "pass", detail: "robots.txt is properly configured with no blocking issues" },
  { label: "Crawl Errors", status: "warning", detail: "5 URLs returning 404 errors detected in crawl" },
  { label: "Redirect Issues", status: "fail", detail: "3 redirect chains found (3+ hops)" },
];

const statusConfig = {
  pass: { icon: CheckCircle2, className: "text-success", bgClassName: "bg-success/10" },
  warning: { icon: AlertTriangle, className: "text-warning", bgClassName: "bg-warning/10" },
  fail: { icon: XCircle, className: "text-danger", bgClassName: "bg-danger/10" },
};

const IndexingCrawl = () => {
  const indexed = 282;
  const total = 300;
  const percentage = Math.round((indexed / total) * 100);

  return (
    <div className="rounded-xl bg-card p-6 card-shadow-md">
      <div className="flex items-center gap-2 mb-6">
        <FileSearch className="w-5 h-5 text-primary" />
        <h2 className="text-lg font-semibold text-foreground">Indexing & Crawl Status</h2>
      </div>

      <div className="flex items-center gap-6 mb-6 p-4 rounded-lg bg-muted">
        <div className="flex-1">
          <div className="flex items-center justify-between mb-2">
            <span className="text-sm font-medium text-foreground">Index Coverage</span>
            <span className="text-sm font-bold text-foreground">{percentage}%</span>
          </div>
          <div className="w-full h-3 bg-border rounded-full overflow-hidden">
            <div className="h-full bg-success rounded-full" style={{ width: `${percentage}%` }} />
          </div>
          <div className="flex justify-between mt-1.5 text-xs text-muted-foreground">
            <span>{indexed} indexed</span>
            <span>{total - indexed} excluded</span>
          </div>
        </div>
      </div>

      <div className="space-y-3">
        {indexData.map((item) => {
          const config = statusConfig[item.status];
          const Icon = config.icon;
          return (
            <div key={item.label} className={`flex items-start gap-3 p-3.5 rounded-lg ${config.bgClassName}`}>
              <Icon className={`w-5 h-5 mt-0.5 flex-shrink-0 ${config.className}`} />
              <div className="flex-1 min-w-0">
                <span className="text-sm font-semibold text-foreground">{item.label}</span>
                <p className="text-xs text-muted-foreground mt-0.5">{item.detail}</p>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};

export default IndexingCrawl;
