import { Users, MousePointerClick, Eye, Clock, Plug, BarChart3 } from "lucide-react";
import { LineChart, Line, BarChart, Bar, XAxis, YAxis, ResponsiveContainer, Tooltip } from "recharts";

const trafficTrend = [
  { month: "Sep", users: 8200 },
  { month: "Oct", users: 9100 },
  { month: "Nov", users: 9800 },
  { month: "Dec", users: 10500 },
  { month: "Jan", users: 11200 },
  { month: "Feb", users: 12450 },
];

const topPages = [
  { page: "/seo-tools", views: 3200 },
  { page: "/blog/seo-guide", views: 2800 },
  { page: "/pricing", views: 2100 },
  { page: "/backlink-checker", views: 1900 },
  { page: "/about", views: 1400 },
];

const stats = [
  { label: "Users", value: "12,450", icon: Users },
  { label: "Sessions", value: "18,320", icon: MousePointerClick },
  { label: "Page Views", value: "52,140", icon: Eye },
  { label: "Engagement Rate", value: "64.2%", icon: BarChart3 },
  { label: "Avg. Engagement", value: "2m 34s", icon: Clock },
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

const GA4Section = () => {
  if (!connected) {
    return (
      <div className="rounded-xl bg-card p-6 card-shadow-md">
        <h2 className="text-lg font-semibold text-foreground mb-4">Google Analytics 4</h2>
        <div className="flex flex-col items-center justify-center py-12 text-center">
          <div className="w-16 h-16 rounded-2xl bg-accent flex items-center justify-center mb-4">
            <Plug className="w-7 h-7 text-accent-foreground" />
          </div>
          <h3 className="font-semibold text-foreground mb-1">Connect Google Analytics</h3>
          <p className="text-sm text-muted-foreground mb-4 max-w-sm">
            Connect your GA4 property to view real-time traffic analytics, user behavior, and engagement metrics.
          </p>
          <button className="px-5 py-2.5 rounded-lg bg-primary text-primary-foreground text-sm font-semibold hover:opacity-90 transition-opacity">
            Connect Google Analytics
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="rounded-xl bg-card p-6 card-shadow-md">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-lg font-semibold text-foreground">Google Analytics 4</h2>
        <span className="text-xs font-medium text-success bg-success/10 px-2.5 py-0.5 rounded-full">Connected</span>
      </div>

      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
        {stats.map((stat) => {
          const Icon = stat.icon;
          return (
            <div key={stat.label} className="p-3 rounded-lg bg-muted">
              <div className="flex items-center gap-1.5 mb-1">
                <Icon className="w-3.5 h-3.5 text-primary" />
                <span className="text-xs text-muted-foreground font-medium">{stat.label}</span>
              </div>
              <span className="text-lg font-bold text-foreground">{stat.value}</span>
            </div>
          );
        })}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div>
          <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3">Traffic Trend</h3>
          <div className="h-44">
            <ResponsiveContainer width="100%" height="100%">
              <LineChart data={trafficTrend}>
                <XAxis dataKey="month" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: "hsl(215, 15%, 55%)" }} />
                <YAxis hide />
                <Tooltip contentStyle={tooltipStyle} />
                <Line type="monotone" dataKey="users" stroke="hsl(200, 100%, 50%)" strokeWidth={2.5} dot={{ fill: "hsl(200, 100%, 50%)", r: 3 }} />
              </LineChart>
            </ResponsiveContainer>
          </div>
        </div>

        <div>
          <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3">Top Pages</h3>
          <div className="h-44">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={topPages} layout="vertical" barSize={16}>
                <XAxis type="number" hide />
                <YAxis type="category" dataKey="page" axisLine={false} tickLine={false} tick={{ fontSize: 11, fill: "hsl(215, 15%, 55%)" }} width={120} />
                <Tooltip contentStyle={tooltipStyle} />
                <Bar dataKey="views" fill="hsl(200, 100%, 50%)" radius={[0, 4, 4, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>
      </div>
    </div>
  );
};

export default GA4Section;
