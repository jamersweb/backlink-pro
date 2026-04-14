interface ScoreItem {
  label: string;
  score: number;
  description: string;
}

const scores: ScoreItem[] = [
  { label: "Technical SEO", score: 78, description: "Strong HTTPS setup; minor canonical and redirect issues detected" },
  { label: "On-Page SEO", score: 68, description: "Missing meta descriptions and duplicate H1 tags need attention" },
  { label: "Performance", score: 85, description: "Desktop performance is excellent; mobile LCP needs optimization" },
  { label: "Content Quality", score: 62, description: "Some thin content pages identified; keyword optimization opportunities exist" },
  { label: "Authority / Backlinks", score: 54, description: "Moderate backlink profile; referring domain diversity should improve" },
  { label: "Mobile Usability", score: 91, description: "Mobile-friendly design with minor touch target sizing issues" },
];

const getBarColor = (score: number) => {
  if (score >= 80) return "bg-success";
  if (score >= 60) return "bg-warning";
  return "bg-danger";
};

const getStatusLabel = (score: number) => {
  if (score >= 80) return { label: "Good", className: "bg-success/10 text-success" };
  if (score >= 60) return { label: "Fair", className: "bg-warning/10 text-warning" };
  return { label: "Poor", className: "bg-danger/10 text-danger" };
};

const SEOScoreBreakdown = () => {
  return (
    <div className="rounded-xl bg-card p-6 card-shadow-md">
      <h2 className="text-lg font-semibold text-foreground mb-6">SEO Score Breakdown</h2>

      <div className="space-y-5">
        {scores.map((item) => {
          const status = getStatusLabel(item.score);
          return (
            <div key={item.label} className="space-y-2">
              <div className="flex items-center justify-between">
                <span className="text-sm font-semibold text-foreground">{item.label}</span>
                <div className="flex items-center gap-3">
                  <span className="text-sm font-bold text-foreground">{item.score}/100</span>
                  <span className={`text-xs font-medium px-2 py-0.5 rounded-full ${status.className}`}>
                    {status.label}
                  </span>
                </div>
              </div>
              <div className="w-full h-2 bg-muted rounded-full overflow-hidden">
                <div
                  className={`h-full rounded-full ${getBarColor(item.score)} transition-all duration-700`}
                  style={{ width: `${item.score}%` }}
                />
              </div>
              <p className="text-xs text-muted-foreground">{item.description}</p>
            </div>
          );
        })}
      </div>
    </div>
  );
};

export default SEOScoreBreakdown;
