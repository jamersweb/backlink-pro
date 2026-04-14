import { AlertTriangle, AlertCircle, CheckCircle2 } from "lucide-react";
import { PieChart, Pie, Cell, ResponsiveContainer } from "recharts";

const issueData = [
  { name: "Critical", value: 4, color: "hsl(0, 70%, 55%)" },
  { name: "Warnings", value: 12, color: "hsl(38, 90%, 55%)" },
  { name: "Passed", value: 38, color: "hsl(150, 55%, 42%)" },
];

const topIssues = [
  { severity: "critical", icon: AlertCircle, text: "Missing meta descriptions on 8 pages", color: "text-danger" },
  { severity: "critical", icon: AlertCircle, text: "Broken internal links found (3 URLs)", color: "text-danger" },
  { severity: "warning", icon: AlertTriangle, text: "Images without ALT attributes (12 images)", color: "text-warning" },
  { severity: "warning", icon: AlertTriangle, text: "Duplicate H1 tags on 5 pages", color: "text-warning" },
  { severity: "passed", icon: CheckCircle2, text: "SSL certificate is valid and secure", color: "text-success" },
];

const IssuesOverview = () => {
  const total = issueData.reduce((sum, d) => sum + d.value, 0);

  return (
    <div className="rounded-xl bg-card p-6 card-shadow">
      <h2 className="text-lg font-semibold text-foreground mb-6">SEO Issues Overview</h2>

      <div className="flex flex-col md:flex-row gap-8">
        <div className="flex-shrink-0 flex flex-col items-center">
          <div className="relative w-44 h-44">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={issueData}
                  cx="50%"
                  cy="50%"
                  innerRadius={55}
                  outerRadius={80}
                  paddingAngle={3}
                  dataKey="value"
                  strokeWidth={0}
                >
                  {issueData.map((entry, index) => (
                    <Cell key={index} fill={entry.color} />
                  ))}
                </Pie>
              </PieChart>
            </ResponsiveContainer>
            <div className="absolute inset-0 flex flex-col items-center justify-center">
              <span className="text-2xl font-bold text-foreground">{total}</span>
              <span className="text-xs text-muted-foreground">Total Checks</span>
            </div>
          </div>

          <div className="flex gap-4 mt-3">
            {issueData.map((d) => (
              <div key={d.name} className="flex items-center gap-1.5 text-xs">
                <div className="w-2.5 h-2.5 rounded-full" style={{ backgroundColor: d.color }} />
                <span className="text-muted-foreground">{d.name} ({d.value})</span>
              </div>
            ))}
          </div>
        </div>

        <div className="flex-1 space-y-3">
          <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">Top Issues</h3>
          {topIssues.map((issue, i) => {
            const Icon = issue.icon;
            return (
              <div key={i} className="flex items-start gap-3 py-2 border-b border-border last:border-0">
                <Icon className={`w-4 h-4 mt-0.5 flex-shrink-0 ${issue.color}`} />
                <span className="text-sm text-foreground">{issue.text}</span>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
};

export default IssuesOverview;
