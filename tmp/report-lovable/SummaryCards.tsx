import { Search, Zap, FileText, Link2, Settings, Smartphone } from "lucide-react";

interface SummaryCard {
  label: string;
  score: number;
  icon: React.ElementType;
}

const cards: SummaryCard[] = [
  { label: "SEO Score", score: 72, icon: Search },
  { label: "Performance", score: 85, icon: Zap },
  { label: "On-Page SEO", score: 68, icon: FileText },
  { label: "Backlinks", score: 54, icon: Link2 },
  { label: "Technical Health", score: 78, icon: Settings },
  { label: "Mobile Usability", score: 91, icon: Smartphone },
];

const getBarColor = (score: number) => {
  if (score >= 80) return "bg-success";
  if (score >= 60) return "bg-warning";
  return "bg-danger";
};

const getScoreTextColor = (score: number) => {
  if (score >= 80) return "text-success";
  if (score >= 60) return "text-warning";
  return "text-danger";
};

const SummaryCards = () => {
  return (
    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
      {cards.map((card, index) => {
        const Icon = card.icon;
        return (
          <div
            key={card.label}
            className="rounded-xl bg-card p-5 card-shadow flex flex-col gap-3"
            style={{ animationDelay: `${index * 80}ms` }}
          >
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 rounded-lg bg-accent flex items-center justify-center">
                <Icon className="w-4 h-4 text-accent-foreground" />
              </div>
            </div>
            <div>
              <div className={`text-2xl font-bold ${getScoreTextColor(card.score)}`}>
                {card.score}
              </div>
              <div className="text-xs text-muted-foreground font-medium mt-0.5">
                {card.label}
              </div>
            </div>
            <div className="w-full h-1.5 bg-muted rounded-full overflow-hidden">
              <div
                className={`h-full rounded-full ${getBarColor(card.score)} transition-all duration-700 ease-out`}
                style={{ width: `${card.score}%` }}
              />
            </div>
          </div>
        );
      })}
    </div>
  );
};

export default SummaryCards;
