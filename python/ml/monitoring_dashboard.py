"""
Model Performance Monitoring Dashboard

This module provides monitoring and visualization of model performance over time.
"""

import json
import pandas as pd
import numpy as np
import logging
from pathlib import Path
from typing import Dict, List, Optional
from datetime import datetime
import matplotlib
matplotlib.use('Agg')  # Non-interactive backend
import matplotlib.pyplot as plt
import seaborn as sns

logger = logging.getLogger(__name__)

# Try to import visualization libraries
try:
    import matplotlib.pyplot as plt
    import seaborn as sns
    MATPLOTLIB_AVAILABLE = True
except ImportError:
    MATPLOTLIB_AVAILABLE = False
    logger.warning("Matplotlib/Seaborn not available for visualization")


class ModelMonitor:
    """Monitor model performance over time"""
    
    def __init__(self, log_dir: str = "ml/monitoring"):
        """
        Initialize model monitor
        
        Args:
            log_dir: Directory to store monitoring logs and plots
        """
        self.log_dir = Path(log_dir)
        self.log_dir.mkdir(parents=True, exist_ok=True)
        self.metrics_file = self.log_dir / 'metrics_history.json'
        self.metrics_history = self._load_history()
    
    def _load_history(self) -> List[Dict]:
        """Load metrics history from file"""
        if self.metrics_file.exists():
            try:
                with open(self.metrics_file, 'r') as f:
                    return json.load(f)
            except Exception as e:
                logger.warning(f"Could not load metrics history: {e}")
                return []
        return []
    
    def _save_history(self):
        """Save metrics history to file"""
        try:
            with open(self.metrics_file, 'w') as f:
                json.dump(self.metrics_history, f, indent=2)
        except Exception as e:
            logger.warning(f"Could not save metrics history: {e}")
    
    def log_metrics(
        self,
        metrics: Dict,
        model_version: str = "unknown",
        timestamp: Optional[str] = None
    ):
        """
        Log model metrics
        
        Args:
            metrics: Dictionary with metrics (accuracy, f1_macro, etc.)
            model_version: Model version identifier
            timestamp: Optional timestamp (defaults to now)
        """
        if timestamp is None:
            timestamp = datetime.now().isoformat()
        
        entry = {
            'timestamp': timestamp,
            'version': model_version,
            'metrics': metrics
        }
        
        self.metrics_history.append(entry)
        self._save_history()
        
        logger.info(f"Logged metrics for version {model_version}")
    
    def get_latest_metrics(self) -> Optional[Dict]:
        """Get latest metrics entry"""
        if not self.metrics_history:
            return None
        return self.metrics_history[-1]
    
    def get_metrics_trend(self, metric_name: str) -> pd.DataFrame:
        """
        Get trend for a specific metric
        
        Args:
            metric_name: Name of metric (e.g., 'accuracy', 'f1_macro')
        
        Returns:
            DataFrame with timestamp and metric values
        """
        data = []
        for entry in self.metrics_history:
            if metric_name in entry.get('metrics', {}):
                data.append({
                    'timestamp': entry['timestamp'],
                    'version': entry['version'],
                    'value': entry['metrics'][metric_name]
                })
        
        if not data:
            return pd.DataFrame()
        
        df = pd.DataFrame(data)
        df['timestamp'] = pd.to_datetime(df['timestamp'])
        df = df.sort_values('timestamp')
        
        return df
    
    def plot_metrics_trend(
        self,
        metric_names: List[str] = None,
        output_path: Optional[str] = None
    ):
        """
        Plot metrics trends over time
        
        Args:
            metric_names: List of metric names to plot (default: ['accuracy', 'f1_macro'])
            output_path: Optional output path for plot
        """
        if not MATPLOTLIB_AVAILABLE:
            logger.warning("Matplotlib not available. Cannot create plots.")
            return
        
        if metric_names is None:
            metric_names = ['accuracy', 'f1_macro']
        
        if not self.metrics_history:
            logger.warning("No metrics history to plot")
            return
        
        # Create figure
        n_metrics = len(metric_names)
        fig, axes = plt.subplots(n_metrics, 1, figsize=(12, 4 * n_metrics))
        
        if n_metrics == 1:
            axes = [axes]
        
        for idx, metric_name in enumerate(metric_names):
            df = self.get_metrics_trend(metric_name)
            
            if df.empty:
                axes[idx].text(0.5, 0.5, f'No data for {metric_name}',
                              ha='center', va='center', transform=axes[idx].transAxes)
                axes[idx].set_title(f'{metric_name} Trend')
                continue
            
            axes[idx].plot(df['timestamp'], df['value'], marker='o', linewidth=2, markersize=8)
            axes[idx].set_title(f'{metric_name} Trend Over Time', fontsize=14, fontweight='bold')
            axes[idx].set_xlabel('Date', fontsize=12)
            axes[idx].set_ylabel(metric_name, fontsize=12)
            axes[idx].grid(True, alpha=0.3)
            axes[idx].tick_params(axis='x', rotation=45)
            
            # Add version labels
            for _, row in df.iterrows():
                axes[idx].annotate(
                    row['version'],
                    (row['timestamp'], row['value']),
                    textcoords="offset points",
                    xytext=(0, 10),
                    ha='center',
                    fontsize=8,
                    alpha=0.7
                )
        
        plt.tight_layout()
        
        if output_path:
            plt.savefig(output_path, dpi=300, bbox_inches='tight')
            logger.info(f"Saved metrics plot to {output_path}")
        else:
            output_path = self.log_dir / 'metrics_trend.png'
            plt.savefig(output_path, dpi=300, bbox_inches='tight')
            logger.info(f"Saved metrics plot to {output_path}")
        
        plt.close()
    
    def generate_report(self) -> str:
        """
        Generate monitoring report
        
        Returns:
            Report text
        """
        if not self.metrics_history:
            return "No metrics history available."
        
        report_lines = [
            "=" * 70,
            "MODEL PERFORMANCE MONITORING REPORT",
            "=" * 70,
            f"Total model versions tracked: {len(self.metrics_history)}",
            "",
        ]
        
        # Latest metrics
        latest = self.get_latest_metrics()
        if latest:
            report_lines.extend([
                "LATEST METRICS",
                "-" * 70,
                f"Version: {latest['version']}",
                f"Timestamp: {latest['timestamp']}",
                "",
            ])
            
            for metric_name, value in latest['metrics'].items():
                if isinstance(value, (int, float)):
                    report_lines.append(f"  {metric_name}: {value:.4f}")
                else:
                    report_lines.append(f"  {metric_name}: {value}")
            
            report_lines.append("")
        
        # Trends
        report_lines.extend([
            "METRICS TRENDS",
            "-" * 70,
        ])
        
        for metric_name in ['accuracy', 'f1_macro', 'precision_macro', 'recall_macro']:
            df = self.get_metrics_trend(metric_name)
            if not df.empty:
                first_val = df['value'].iloc[0]
                last_val = df['value'].iloc[-1]
                change = last_val - first_val
                change_pct = (change / first_val * 100) if first_val != 0 else 0
                
                report_lines.append(
                    f"{metric_name}: {first_val:.4f} → {last_val:.4f} "
                    f"({change:+.4f}, {change_pct:+.2f}%)"
                )
        
        report_lines.extend([
            "",
            "=" * 70,
        ])
        
        return "\n".join(report_lines)
    
    def save_report(self, output_path: Optional[str] = None):
        """Save monitoring report to file"""
        report = self.generate_report()
        
        if output_path:
            report_file = Path(output_path)
        else:
            report_file = self.log_dir / 'monitoring_report.txt'
        
        with open(report_file, 'w') as f:
            f.write(report)
        
        logger.info(f"Saved monitoring report to {report_file}")
    
    def compare_versions(self, version1: str, version2: str) -> Dict:
        """
        Compare metrics between two versions
        
        Args:
            version1: First version identifier
            version2: Second version identifier
        
        Returns:
            Dictionary with comparison
        """
        v1_metrics = None
        v2_metrics = None
        
        for entry in self.metrics_history:
            if entry['version'] == version1:
                v1_metrics = entry['metrics']
            if entry['version'] == version2:
                v2_metrics = entry['metrics']
        
        if v1_metrics is None or v2_metrics is None:
            return {'error': 'One or both versions not found'}
        
        comparison = {}
        all_metrics = set(v1_metrics.keys()) | set(v2_metrics.keys())
        
        for metric in all_metrics:
            v1_val = v1_metrics.get(metric, 0)
            v2_val = v2_metrics.get(metric, 0)
            
            if isinstance(v1_val, (int, float)) and isinstance(v2_val, (int, float)):
                comparison[metric] = {
                    'version1': v1_val,
                    'version2': v2_val,
                    'difference': v2_val - v1_val,
                    'improvement_pct': ((v2_val - v1_val) / v1_val * 100) if v1_val != 0 else 0
                }
        
        return comparison


def create_dashboard(
    metrics_history_file: str = "ml/monitoring/metrics_history.json",
    output_dir: str = "ml/monitoring"
):
    """
    Create complete monitoring dashboard
    
    Args:
        metrics_history_file: Path to metrics history JSON
        output_dir: Output directory for dashboard files
    """
    monitor = ModelMonitor(log_dir=output_dir)
    
    # Generate plots
    monitor.plot_metrics_trend()
    
    # Generate report
    monitor.save_report()
    
    logger.info("Monitoring dashboard created successfully")


if __name__ == "__main__":
    # Example usage
    monitor = ModelMonitor()
    
    # Example metrics
    example_metrics = {
        'accuracy': 0.65,
        'f1_macro': 0.58,
        'precision_macro': 0.62,
        'recall_macro': 0.55
    }
    
    monitor.log_metrics(example_metrics, model_version="v1.0.0")
    monitor.plot_metrics_trend()
    monitor.save_report()
    
    print(monitor.generate_report())


