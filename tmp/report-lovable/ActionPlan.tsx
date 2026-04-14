import { ArrowRight, Zap } from "lucide-react";

interface ActionItem {
  priority: "high" | "medium" | "low";
  text: string;
  impact: string;
  difficulty: string;
  benefit: string;
}

const actions: ActionItem[] = [
  { priority: "high", text: "Add missing meta descriptions to 8 pages", impact: "High", difficulty: "Easy", benefit: "Improved CTR by ~15-20%" },
  { priority: "high", text: "Fix 3 broken internal links", impact: "High", difficulty: "Easy", benefit: "Better crawlability & user experience" },
  { priority: "high", text: "Resolve 3 redirect chains (3+ hops)", impact: "High", difficulty: "Medium", benefit: "Faster page loads & link equity flow" },
  { priority: "medium", text: "Add ALT attributes to 12 images", impact: "Medium", difficulty: "Easy", benefit: "Image search visibility & accessibility" },
  { priority: "medium", text: "Resolve duplicate H1 tags on 5 pages", impact: "Medium", difficulty: "Easy", benefit: "Clearer content hierarchy for crawlers" },
  { priority: "medium", text: "Improve mobile page speed (LCP: 2.1s → under 1.5s)", impact: "High", difficulty: "Medium", benefit: "Better mobile rankings & UX" },
  { priority: "medium", text: "Add structured data markup to key pages", impact: "Medium", difficulty: "Medium", benefit: "Rich snippets in search results" },
  { priority: "low", text: "Optimize image file sizes for faster loading", impact: "Low", difficulty: "Easy", benefit: "Marginal speed improvements" },
  { priority: "low", text: "Address 6 thin content pages", impact: "Medium", difficulty: "Hard", benefit: "Improved topical authority" },
  { priority: "low", text: "Increase referring domain diversity", impact: "High", difficulty: "Hard", benefit: "Stronger domain authority over time" },
];

const priorityConfig = {
  high: {
    label: "High Priority",
    dotClassName: "bg-danger",
    badgeClassName: "bg-danger/10 text-danger",
  },
  medium: {
    label: "Medium Priority",
    dotClassName: "bg-warning",
    badgeClassName: "bg-warning/10 text-warning",
  },
  low: {
    label: "Low Priority",
    dotClassName: "bg-primary",
    badgeClassName: "bg-primary/10 text-primary",
  },
};

const ActionPlan = () => {
  const grouped = {
    high: actions.filter((a) => a.priority === "high"),
    medium: actions.filter((a) => a.priority === "medium"),
    low: actions.filter((a) => a.priority === "low"),
  };

  return (
    <div className="rounded-xl bg-card p-6 card-shadow-md">
      <div className="flex items-center gap-2 mb-6">
        <Zap className="w-5 h-5 text-primary" />
        <h2 className="text-lg font-semibold text-foreground">Final Verdict & Action Plan</h2>
      </div>

      <div className="space-y-6">
        {(["high", "medium", "low"] as const).map((priority) => {
          const config = priorityConfig[priority];
          const items = grouped[priority];
          return (
            <div key={priority}>
              <div className="flex items-center gap-2 mb-3">
                <span className={`text-xs font-semibold px-2.5 py-0.5 rounded-full ${config.badgeClassName}`}>
                  {config.label}
                </span>
              </div>
              <div className="space-y-2">
                {items.map((item, i) => (
                  <div key={i} className="flex items-start gap-3 py-3 px-3 rounded-lg hover:bg-muted/50 transition-colors">
                    <div className={`w-2 h-2 rounded-full flex-shrink-0 mt-1.5 ${config.dotClassName}`} />
                    <div className="flex-1 min-w-0">
                      <span className="text-sm text-foreground font-medium">{item.text}</span>
                      <div className="flex flex-wrap gap-3 mt-1.5">
                        <span className="text-xs text-muted-foreground">
                          Impact: <span className="font-semibold text-foreground">{item.impact}</span>
                        </span>
                        <span className="text-xs text-muted-foreground">
                          Difficulty: <span className="font-semibold text-foreground">{item.difficulty}</span>
                        </span>
                        <span className="text-xs text-muted-foreground">
                          Benefit: <span className="font-semibold text-foreground">{item.benefit}</span>
                        </span>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          );
        })}
      </div>

      <div className="mt-8 pt-6 border-t border-border flex items-center justify-center">
        <button className="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-primary text-primary-foreground font-semibold text-sm hover:opacity-90 transition-opacity card-shadow-md">
          Fix All SEO Issues For Me
          <ArrowRight className="w-4 h-4" />
        </button>
      </div>
    </div>
  );
};

export default ActionPlan;
