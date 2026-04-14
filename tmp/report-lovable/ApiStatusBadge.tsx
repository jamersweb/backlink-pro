import { Plug, CheckCircle2 } from "lucide-react";

interface ApiStatusBadgeProps {
  name: string;
  connected: boolean;
  onConnect?: () => void;
}

const ApiStatusBadge = ({ name, connected, onConnect }: ApiStatusBadgeProps) => {
  if (connected) {
    return (
      <div className="flex items-center gap-1.5 text-xs text-success font-medium">
        <CheckCircle2 className="w-3.5 h-3.5" />
        <span>{name}</span>
      </div>
    );
  }

  return (
    <button
      onClick={onConnect}
      className="flex items-center gap-1.5 text-xs text-muted-foreground hover:text-primary transition-colors font-medium"
    >
      <Plug className="w-3.5 h-3.5" />
      <span>Connect {name}</span>
    </button>
  );
};

export default ApiStatusBadge;
