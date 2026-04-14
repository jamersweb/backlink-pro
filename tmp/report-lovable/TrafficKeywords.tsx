import { TrendingUp, Eye } from "lucide-react";
import { LineChart, Line, XAxis, YAxis, ResponsiveContainer, Tooltip } from "recharts";

const visibilityData = [
  { month: "Sep", visibility: 32 },
  { month: "Oct", visibility: 35 },
  { month: "Nov", visibility: 38 },
  { month: "Dec", visibility: 36 },
  { month: "Jan", visibility: 42 },
  { month: "Feb", visibility: 47 },
];

const topKeywords = [
  { keyword: "seo audit tool", position: 4, volume: "2.4K" },
  { keyword: "backlink checker", position: 8, volume: "5.1K" },
  { keyword: "website analysis", position: 12, volume: "3.8K" },
  { keyword: "seo report generator", position: 6, volume: "1.9K" },
  { keyword: "domain authority checker", position: 15, volume: "4.2K" },
];

const tooltipStyle = {
  backgroundColor: "hsl(222, 25%, 14%)",
  border: "1px solid hsl(220, 20%, 22%)",
  borderRadius: "8px",
  fontSize: "12px",
  color: "hsl(210, 20%, 85%)",
  boxShadow: "0 4px 6px -1px rgba(0,0,0,0.3)",
};

const TrafficKeywords = () => {
  return (
    <div className="rounded-xl bg-card p-6 card-shadow">
      <h2 className="text-lg font-semibold text-foreground mb-6">Traffic & Keywords</h2>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <div className="flex items-center gap-3 mb-4">
            <div className="p-3 rounded-lg bg-accent">
              <Eye className="w-5 h-5 text-accent-foreground" />
            </div>
            <div>
              <div className="text-2xl font-bold text-foreground">12,450</div>
              <div className="text-xs text-muted-foreground">Est. Monthly Organic Traffic</div>
            </div>
          </div>

          <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3 flex items-center gap-1.5">
            <TrendingUp className="w-3.5 h-3.5" />
            Visibility Trend
          </h3>
          <div className="h-36">
            <ResponsiveContainer width="100%" height="100%">
              <LineChart data={visibilityData}>
                <XAxis
                  dataKey="month"
                  axisLine={false}
                  tickLine={false}
                  tick={{ fontSize: 12, fill: "hsl(215, 15%, 55%)" }}
                />
                <YAxis hide />
                <Tooltip contentStyle={tooltipStyle} />
                <Line
                  type="monotone"
                  dataKey="visibility"
                  stroke="hsl(200, 100%, 50%)"
                  strokeWidth={2.5}
                  dot={{ fill: "hsl(200, 100%, 50%)", r: 3.5 }}
                  activeDot={{ r: 5 }}
                />
              </LineChart>
            </ResponsiveContainer>
          </div>
        </div>

        <div>
          <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3">
            Top 5 Keywords
          </h3>
          <div className="space-y-2.5">
            {topKeywords.map((kw, i) => (
              <div key={kw.keyword} className="flex items-center justify-between py-2 border-b border-border last:border-0">
                <div className="flex items-center gap-3">
                  <span className="w-6 h-6 rounded-full bg-muted flex items-center justify-center text-xs font-semibold text-muted-foreground">
                    {i + 1}
                  </span>
                  <span className="text-sm font-medium text-foreground">{kw.keyword}</span>
                </div>
                <div className="flex items-center gap-4 text-xs text-muted-foreground">
                  <span>Pos: <span className="font-semibold text-foreground">{kw.position}</span></span>
                  <span>Vol: <span className="font-semibold text-foreground">{kw.volume}</span></span>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};

export default TrafficKeywords;
