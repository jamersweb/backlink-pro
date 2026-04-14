import { Link2, ExternalLink, ShieldAlert } from "lucide-react";
import { BarChart, Bar, XAxis, YAxis, ResponsiveContainer, Tooltip } from "recharts";

const backlinkGrowth = [
  { month: "Sep", count: 320 },
  { month: "Oct", count: 340 },
  { month: "Nov", count: 365 },
  { month: "Dec", count: 390 },
  { month: "Jan", count: 420 },
  { month: "Feb", count: 458 },
];

const tooltipStyle = {
  backgroundColor: "hsl(222, 25%, 14%)",
  border: "1px solid hsl(220, 20%, 22%)",
  borderRadius: "8px",
  fontSize: "12px",
  color: "hsl(210, 20%, 85%)",
  boxShadow: "0 4px 6px -1px rgba(0,0,0,0.3)",
};

const BacklinkOverview = () => {
  const doFollow = 72;

  return (
    <div className="rounded-xl bg-card p-6 card-shadow">
      <h2 className="text-lg font-semibold text-foreground mb-6">Backlink Overview</h2>

      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div className="p-4 rounded-lg bg-muted">
          <div className="flex items-center gap-2 mb-1">
            <Link2 className="w-4 h-4 text-primary" />
            <span className="text-xs text-muted-foreground font-medium">Total Backlinks</span>
          </div>
          <span className="text-xl font-bold text-foreground">458</span>
        </div>
        <div className="p-4 rounded-lg bg-muted">
          <div className="flex items-center gap-2 mb-1">
            <ExternalLink className="w-4 h-4 text-primary" />
            <span className="text-xs text-muted-foreground font-medium">Referring Domains</span>
          </div>
          <span className="text-xl font-bold text-foreground">127</span>
        </div>
        <div className="p-4 rounded-lg bg-muted">
          <div className="flex items-center gap-2 mb-1">
            <span className="text-xs text-muted-foreground font-medium">Do-Follow</span>
          </div>
          <div className="flex items-center gap-2">
            <span className="text-xl font-bold text-foreground">{doFollow}%</span>
            <div className="flex-1 h-2 bg-border rounded-full overflow-hidden">
              <div className="h-full bg-success rounded-full" style={{ width: `${doFollow}%` }} />
            </div>
          </div>
        </div>
        <div className="p-4 rounded-lg bg-muted">
          <div className="flex items-center gap-2 mb-1">
            <ShieldAlert className="w-4 h-4 text-warning" />
            <span className="text-xs text-muted-foreground font-medium">Spam Score</span>
          </div>
          <span className="text-xl font-bold text-success">Low</span>
        </div>
      </div>

      <div>
        <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3">
          Backlink Growth
        </h3>
        <div className="h-40">
          <ResponsiveContainer width="100%" height="100%">
            <BarChart data={backlinkGrowth} barSize={24}>
              <XAxis
                dataKey="month"
                axisLine={false}
                tickLine={false}
                tick={{ fontSize: 12, fill: "hsl(215, 15%, 55%)" }}
              />
              <YAxis hide />
              <Tooltip contentStyle={tooltipStyle} />
              <Bar dataKey="count" fill="hsl(200, 100%, 50%)" radius={[4, 4, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </div>
      </div>
    </div>
  );
};

export default BacklinkOverview;
