import { Shield, CheckCircle2, AlertTriangle, XCircle, Lock, Link2, ArrowRightLeft, FileCode } from "lucide-react";

interface AuditItem {
  label: string;
  status: "pass" | "warning" | "critical";
  detail: string;
  icon: React.ElementType;
}

const auditItems: AuditItem[] = [
  { label: "HTTPS & Security", status: "pass", detail: "SSL certificate is valid; all pages served over HTTPS", icon: Lock },
  { label: "Canonical Tags", status: "warning", detail: "2 pages have conflicting canonical URLs", icon: FileCode },
  { label: "Broken Links", status: "critical", detail: "3 broken internal links detected across the site", icon: Link2 },
  { label: "Redirect Chains", status: "warning", detail: "3 redirect chains with 3+ hops found", icon: ArrowRightLeft },
  { label: "Sitemap Issues", status: "pass", detail: "XML sitemap is valid and includes all indexable pages", icon: FileCode },
  { label: "Robots.txt", status: "pass", detail: "No critical directives blocking important pages", icon: FileCode },
  { label: "Structured Data", status: "warning", detail: "Missing schema markup on 15 key pages", icon: FileCode },
  { label: "Hreflang Tags", status: "pass", detail: "Not applicable — single language site", icon: FileCode },
];

const statusConfig = {
  pass: { icon: CheckCircle2, className: "text-success", bgClassName: "bg-success/10", label: "Passed", badgeClass: "bg-success/10 text-success" },
  warning: { icon: AlertTriangle, className: "text-warning", bgClassName: "bg-warning/10", label: "Warning", badgeClass: "bg-warning/10 text-warning" },
  critical: { icon: XCircle, className: "text-danger", bgClassName: "bg-danger/10", label: "Critical", badgeClass: "bg-danger/10 text-danger" },
};

const TechnicalSEO = () => {
  const passed = auditItems.filter((i) => i.status === "pass").length;
  const warnings = auditItems.filter((i) => i.status === "warning").length;
  const critical = auditItems.filter((i) => i.status === "critical").length;

  return (
    <div className="rounded-xl bg-card p-6 card-shadow-md">
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center gap-2">
          <Shield className="w-5 h-5 text-primary" />
          <h2 className="text-lg font-semibold text-foreground">Technical SEO Audit</h2>
        </div>
        <div className="flex items-center gap-3">
          <span className="text-xs font-medium px-2 py-0.5 rounded-full bg-danger/10 text-danger">{critical} Critical</span>
          <span className="text-xs font-medium px-2 py-0.5 rounded-full bg-warning/10 text-warning">{warnings} Warnings</span>
          <span className="text-xs font-medium px-2 py-0.5 rounded-full bg-success/10 text-success">{passed} Passed</span>
        </div>
      </div>

      <div className="space-y-3">
        {auditItems.map((item) => {
          const config = statusConfig[item.status];
          const StatusIcon = config.icon;
          const ItemIcon = item.icon;
          return (
            <div key={item.label} className={`flex items-start gap-3 p-3.5 rounded-lg ${config.bgClassName}`}>
              <StatusIcon className={`w-5 h-5 mt-0.5 flex-shrink-0 ${config.className}`} />
              <div className="flex-1 min-w-0">
                <div className="flex items-center justify-between">
                  <span className="text-sm font-semibold text-foreground">{item.label}</span>
                  <span className={`text-xs font-medium px-2 py-0.5 rounded-full ${config.badgeClass}`}>{config.label}</span>
                </div>
                <p className="text-xs text-muted-foreground mt-0.5">{item.detail}</p>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};

export default TechnicalSEO;
