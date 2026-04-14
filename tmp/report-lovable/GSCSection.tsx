import { Search, MousePointerClick, Eye, TrendingUp, Plug } from "lucide-react";
import { LineChart, Line, XAxis, YAxis, ResponsiveContainer, Tooltip, Legend } from "recharts";

const performanceData = [
  { month: "Sep", clicks: 4200, impressions: 68000 },
  { month: "Oct", clicks: 4800, impressions: 72000 },
  { month: "Nov", clicks: 5100, impressions: 78000 },
  { month: "Dec", clicks: 5400, impressions: 82000 },
  { month: "Jan", clicks: 6100, impressions: 91000 },
  { month: "Feb", clicks: 6800, impressions: 98000 },
];

const topQueries = [
  { query: "seo audit tool", clicks: 820, impressions: 12400, ctr: "6.6%", position: 4.2 },
  { query: "backlink checker free", clicks: 640, impressions: 18200, ctr: "3.5%", position: 8.1 },
  { query: "website seo analysis", clicks: 510, impressions: 9800, ctr: "5.2%", position: 6.4 },
  { query: "seo report generator", clicks: 380, impressions: 7200, ctr: "5.3%", position: 5.8 },
  { query: "domain authority checker", clicks: 290, impressions: 11400, ctr: "2.5%", position: 14.7 },
];

const stats = [
  { label: "Total Clicks", value: "6,800", icon: MousePointerClick, change: "+11.5%" },
  { label: "Impressions", value: "98K", icon: Eye, change: "+7.7%" },
  { label: "Avg CTR", value: "4.8%", icon: TrendingUp, change: "+0.3%" },
  { label: "Avg Position", value: "12.4", icon: Search, change: "-1.2" },
];

const connected = true;

const tooltipStyle = {
  backgroundColor: "hsl(222, 25%, 14%)",
  border: "1px solid hsl(220, 20%, 22%)",
  borderRadius: "8px",
  fontSize: "12px",
  color: "hsl(210, 20%, 85%)",
  boxShadow: "0 4px 6px -1px rgba(0,0,0,0.3)",
};

const GSCSection = () => {
  if (!connected) {
    return (
      <div className="rounded-xl bg-card p-6 card-shadow-md">
        <h2 className="text-lg font-semibold text-foreground mb-4">Google Search Console</h2>
        <div className="flex flex-col items-center justify-center py-12 text-center">
          <div className="w-16 h-16 rounded-2xl bg-accent flex items-center justify-center mb-4">
            <Plug className="w-7 h-7 text-accent-foreground" />
          </div>
          <h3 className="font-semibold text-foreground mb-1">Connect Search Console</h3>
          <p className="text-sm text-muted-foreground mb-4 max-w-sm">
            Connect Google Search Console to view search performance, queries, and indexing data.
          </p>
          <button className="px-5 py-2.5 rounded-lg bg-primary text-primary-foreground text-sm font-semibold hover:opacity-90 transition-opacity">
            Connect Google Search Console
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="rounded-xl bg-card p-6 card-shadow-md">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-lg font-semibold text-foreground">Google Search Console</h2>
        <span className="text-xs font-medium text-success bg-success/10 px-2.5 py-0.5 rounded-full">Connected</span>
      </div>

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        {stats.map((stat) => {
          const Icon = stat.icon;
          return (
            <div key={stat.label} className="p-3 rounded-lg bg-muted">
              <div className="flex items-center gap-1.5 mb-1">
                <Icon className="w-3.5 h-3.5 text-primary" />
                <span className="text-xs text-muted-foreground font-medium">{stat.label}</span>
              </div>
              <div className="flex items-center gap-2">
                <span className="text-lg font-bold text-foreground">{stat.value}</span>
                <span className="text-xs font-medium text-success">{stat.change}</span>
              </div>
            </div>
          );
        })}
      </div>

      <div className="mb-6">
        <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3">Clicks vs Impressions</h3>
        <div className="h-48">
          <ResponsiveContainer width="100%" height="100%">
            <LineChart data={performanceData}>
              <XAxis dataKey="month" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: "hsl(215, 15%, 55%)" }} />
              <YAxis yAxisId="left" hide />
              <YAxis yAxisId="right" orientation="right" hide />
              <Tooltip contentStyle={tooltipStyle} />
              <Legend wrapperStyle={{ fontSize: 12, color: "hsl(215, 15%, 55%)" }} />
              <Line yAxisId="left" type="monotone" dataKey="clicks" stroke="hsl(200, 100%, 50%)" strokeWidth={2.5} dot={{ r: 3 }} name="Clicks" />
              <Line yAxisId="right" type="monotone" dataKey="impressions" stroke="hsl(160, 60%, 45%)" strokeWidth={2.5} dot={{ r: 3 }} name="Impressions" />
            </LineChart>
          </ResponsiveContainer>
        </div>
      </div>

      <div>
        <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3">Top Search Queries</h3>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-border">
                <th className="text-left py-2 font-semibold text-muted-foreground">Query</th>
                <th className="text-right py-2 font-semibold text-muted-foreground">Clicks</th>
                <th className="text-right py-2 font-semibold text-muted-foreground">Impressions</th>
                <th className="text-right py-2 font-semibold text-muted-foreground">CTR</th>
                <th className="text-right py-2 font-semibold text-muted-foreground">Position</th>
              </tr>
            </thead>
            <tbody>
              {topQueries.map((q) => (
                <tr key={q.query} className="border-b border-border last:border-0">
                  <td className="py-2.5 font-medium text-foreground">{q.query}</td>
                  <td className="py-2.5 text-right text-foreground">{q.clicks.toLocaleString()}</td>
                  <td className="py-2.5 text-right text-muted-foreground">{q.impressions.toLocaleString()}</td>
                  <td className="py-2.5 text-right text-muted-foreground">{q.ctr}</td>
                  <td className="py-2.5 text-right text-muted-foreground">{q.position}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

export default GSCSection;
