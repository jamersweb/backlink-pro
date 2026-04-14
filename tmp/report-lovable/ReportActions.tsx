import { FileDown, Share2 } from "lucide-react";

const ReportActions = () => {
  return (
    <div className="fixed right-4 top-1/3 z-50 flex flex-col gap-2">
      <button
        title="Download PDF"
        className="w-10 h-10 rounded-lg bg-card card-shadow-md flex items-center justify-center text-muted-foreground hover:text-primary hover:bg-accent transition-colors group relative"
      >
        <FileDown className="w-4 h-4" />
        <span className="absolute right-12 bg-foreground text-background text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
          Download PDF
        </span>
      </button>
      <button
        title="Share Report"
        className="w-10 h-10 rounded-lg bg-card card-shadow-md flex items-center justify-center text-muted-foreground hover:text-primary hover:bg-accent transition-colors group relative"
      >
        <Share2 className="w-4 h-4" />
        <span className="absolute right-12 bg-foreground text-background text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
          Share Report
        </span>
      </button>
    </div>
  );
};

export default ReportActions;
