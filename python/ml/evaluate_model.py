"""
Evaluate ML Model for Backlink Action Prediction

Reports:
- Precision per class
- Confusion matrix
- Failure rate reduction
"""

import pandas as pd
import numpy as np
import pickle
import os
import sys
import logging
from pathlib import Path
import json
import matplotlib.pyplot as plt
import seaborn as sns
from typing import Dict, Optional

# Add parent directory to path
sys.path.insert(0, str(Path(__file__).parent.parent))

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Try to import matplotlib
try:
    import matplotlib
    matplotlib.use('Agg')  # Non-interactive backend
    MATPLOTLIB_AVAILABLE = True
except ImportError:
    MATPLOTLIB_AVAILABLE = False
    logger.warning("Matplotlib not available, skipping plots")

try:
    import seaborn as sns
    SEABORN_AVAILABLE = True
except ImportError:
    SEABORN_AVAILABLE = False
    logger.warning("Seaborn not available, using basic plots")

from sklearn.metrics import (
    precision_score, recall_score, f1_score, accuracy_score,
    confusion_matrix, classification_report
)

ACTION_CLASSES = ['comment', 'profile', 'forum', 'guest']


class ModelEvaluator:
    """Evaluate trained action prediction model"""
    
    def __init__(self, model_path: str, dataset_dir: str = "ml/datasets"):
        """
        Initialize evaluator
        
        Args:
            model_path: Path to saved model file
            dataset_dir: Directory containing test dataset
        """
        self.model_path = Path(model_path)
        self.dataset_dir = Path(dataset_dir)
        
        self.model = None
        self.model_type = None
        self.label_encoder = None
        self.feature_names = None
        self.action_classes = None
        
        self.y_test = None
        self.y_pred = None
        self.y_test_original = None  # Original action types if available
    
    def load_model(self):
        """Load trained model"""
        logger.info(f"Loading model from {self.model_path}")
        
        if not self.model_path.exists():
            raise FileNotFoundError(f"Model file not found: {self.model_path}")
        
        with open(self.model_path, 'rb') as f:
            model_data = pickle.load(f)
        
        self.model = model_data['model']
        self.model_type = model_data.get('model_type', 'unknown')
        self.label_encoder = model_data.get('label_encoder')
        self.feature_names = model_data.get('feature_names', [])
        self.action_classes = model_data.get('action_classes', ACTION_CLASSES)
        
        logger.info(f"Loaded {self.model_type} model")
        logger.info(f"Features: {len(self.feature_names)}")
        logger.info(f"Classes: {self.action_classes}")
    
    def load_test_data(self):
        """Load test dataset"""
        logger.info(f"Loading test data from {self.dataset_dir}")
        
        X_test = pd.read_csv(self.dataset_dir / 'X_test.csv')
        y_test = pd.read_csv(self.dataset_dir / 'y_test.csv')['target'].values
        
        # Ensure feature order matches training
        if self.feature_names:
            X_test = X_test[self.feature_names]
        
        self.X_test = X_test
        self.y_test_original = y_test.copy()
        
        # Helper function to normalize action types
        def normalize_action_type(action_str):
            """Normalize action type to one of ACTION_CLASSES"""
            if pd.isna(action_str):
                return 'comment'  # Default
            action_str = str(action_str).lower().strip()
            # Map variations
            if action_str in ACTION_CLASSES:
                return action_str
            elif action_str in ['guestposting', 'guestpost', 'guest_post']:
                return 'guest'
            elif action_str in ['other', 'unknown', '']:
                return 'comment'  # Map 'other' to 'comment' as default
            else:
                return 'comment'  # Default fallback
        
        # If targets are action types (strings), normalize and encode them
        if len(y_test) > 0 and isinstance(y_test[0], str) and self.label_encoder:
            # Normalize all values first to handle 'other' and variations
            y_test_normalized = [normalize_action_type(x) for x in y_test]
            try:
                y_test = self.label_encoder.transform(y_test_normalized)
            except ValueError as e:
                # If encoding fails, log warning and use default
                logger.warning(f"Some labels couldn't be encoded: {e}")
                # Try to encode only the valid ones, use 0 (comment) as default
                y_test_encoded = []
                for label in y_test_normalized:
                    try:
                        y_test_encoded.append(self.label_encoder.transform([label])[0])
                    except ValueError:
                        y_test_encoded.append(0)  # Default to comment
                y_test = np.array(y_test_encoded, dtype=int)
        # Otherwise assume they're already encoded
        
        self.y_test = y_test
        
        logger.info(f"Test set: {len(X_test)} samples")
        # Only log bincount if y_test is numeric
        if pd.api.types.is_numeric_dtype(self.y_test) or isinstance(self.y_test, np.ndarray) and np.issubdtype(self.y_test.dtype, np.number):
            try:
                logger.info(f"Class distribution: {np.bincount(self.y_test)}")
            except (ValueError, TypeError):
                # If bincount fails, just log unique values
                unique, counts = np.unique(self.y_test, return_counts=True)
                logger.info(f"Class distribution: {dict(zip(unique, counts))}")
        else:
            # For non-numeric, use value_counts
            if hasattr(self.y_test, 'value_counts'):
                logger.info(f"Class distribution: {self.y_test.value_counts().to_dict()}")
            else:
                unique, counts = np.unique(self.y_test, return_counts=True)
                logger.info(f"Class distribution: {dict(zip(unique, counts))}")
        
        return X_test, y_test
    
    def predict(self):
        """Make predictions on test set"""
        logger.info("Making predictions...")
        
        self.y_pred = self.model.predict(self.X_test)
        
        logger.info(f"Predictions: {len(self.y_pred)} samples")
        logger.info(f"Predicted class distribution: {np.bincount(self.y_pred)}")
        
        return self.y_pred
    
    def calculate_metrics(self) -> Dict:
        """Calculate evaluation metrics"""
        logger.info("Calculating metrics...")
        
        # Overall metrics
        accuracy = accuracy_score(self.y_test, self.y_pred)
        precision_macro = precision_score(self.y_test, self.y_pred, average='macro', zero_division=0)
        recall_macro = recall_score(self.y_test, self.y_pred, average='macro', zero_division=0)
        f1_macro = f1_score(self.y_test, self.y_pred, average='macro', zero_division=0)
        
        # Per-class precision
        precision_per_class = precision_score(
            self.y_test, self.y_pred, 
            average=None, zero_division=0
        )
        
        # Per-class recall
        recall_per_class = recall_score(
            self.y_test, self.y_pred,
            average=None, zero_division=0
        )
        
        # Per-class F1
        f1_per_class = f1_score(
            self.y_test, self.y_pred,
            average=None, zero_division=0
        )
        
        # Confusion matrix
        cm = confusion_matrix(self.y_test, self.y_pred)
        
        metrics = {
            'accuracy': float(accuracy),
            'precision_macro': float(precision_macro),
            'recall_macro': float(recall_macro),
            'f1_macro': float(f1_macro),
            'precision_per_class': {
                self.action_classes[i]: float(precision_per_class[i])
                for i in range(len(self.action_classes))
            },
            'recall_per_class': {
                self.action_classes[i]: float(recall_per_class[i])
                for i in range(len(self.action_classes))
            },
            'f1_per_class': {
                self.action_classes[i]: float(f1_per_class[i])
                for i in range(len(self.action_classes))
            },
            'confusion_matrix': cm.tolist(),
        }
        
        return metrics
    
    def calculate_failure_rate_reduction(self) -> Dict:
        """
        Calculate failure rate reduction
        
        Compares model predictions vs baseline (random or most common class)
        """
        logger.info("Calculating failure rate reduction...")
        
        # Baseline: Most common class
        baseline_pred = np.full_like(self.y_test, np.bincount(self.y_test).argmax())
        
        # For failure rate, we need to know which actions actually failed
        # This assumes we have success/failure data
        # For now, we'll calculate accuracy improvement
        
        model_accuracy = accuracy_score(self.y_test, self.y_pred)
        baseline_accuracy = accuracy_score(self.y_test, baseline_pred)
        
        accuracy_improvement = model_accuracy - baseline_accuracy
        relative_improvement = (accuracy_improvement / baseline_accuracy * 100) if baseline_accuracy > 0 else 0
        
        # If we have success/failure data, calculate actual failure rate
        # For now, use misclassification as proxy
        model_error_rate = 1 - model_accuracy
        baseline_error_rate = 1 - baseline_accuracy
        
        failure_rate_reduction = baseline_error_rate - model_error_rate
        failure_rate_reduction_pct = (failure_rate_reduction / baseline_error_rate * 100) if baseline_error_rate > 0 else 0
        
        reduction_metrics = {
            'baseline_accuracy': float(baseline_accuracy),
            'model_accuracy': float(model_accuracy),
            'accuracy_improvement': float(accuracy_improvement),
            'relative_improvement_pct': float(relative_improvement),
            'baseline_error_rate': float(baseline_error_rate),
            'model_error_rate': float(model_error_rate),
            'failure_rate_reduction': float(failure_rate_reduction),
            'failure_rate_reduction_pct': float(failure_rate_reduction_pct),
        }
        
        return reduction_metrics
    
    def plot_confusion_matrix(self, output_path: Optional[str] = None):
        """Plot confusion matrix"""
        if not MATPLOTLIB_AVAILABLE:
            logger.warning("Matplotlib not available, skipping confusion matrix plot")
            return
        
        cm = confusion_matrix(self.y_test, self.y_pred)
        
        plt.figure(figsize=(10, 8))
        
        if SEABORN_AVAILABLE:
            sns.heatmap(
                cm, annot=True, fmt='d', cmap='Blues',
                xticklabels=self.action_classes,
                yticklabels=self.action_classes
            )
        else:
            plt.imshow(cm, interpolation='nearest', cmap='Blues')
            plt.colorbar()
            tick_marks = np.arange(len(self.action_classes))
            plt.xticks(tick_marks, self.action_classes, rotation=45)
            plt.yticks(tick_marks, self.action_classes)
            plt.ylabel('True Label')
            plt.xlabel('Predicted Label')
            
            # Add text annotations
            thresh = cm.max() / 2.
            for i in range(cm.shape[0]):
                for j in range(cm.shape[1]):
                    plt.text(j, i, format(cm[i, j], 'd'),
                            horizontalalignment="center",
                            color="white" if cm[i, j] > thresh else "black")
        
        plt.title('Confusion Matrix')
        plt.tight_layout()
        
        if output_path:
            plt.savefig(output_path, dpi=150, bbox_inches='tight')
            logger.info(f"Confusion matrix saved to {output_path}")
        else:
            plt.savefig(self.dataset_dir.parent / 'confusion_matrix.png', dpi=150, bbox_inches='tight')
            logger.info("Confusion matrix saved to ml/confusion_matrix.png")
        
        plt.close()
    
    def generate_report(self, output_path: Optional[str] = None) -> str:
        """Generate evaluation report"""
        logger.info("Generating evaluation report...")
        
        metrics = self.calculate_metrics()
        reduction = self.calculate_failure_rate_reduction()
        
        # Classification report
        class_report = classification_report(
            self.y_test, self.y_pred,
            target_names=self.action_classes,
            zero_division=0
        )
        
        # Build report
        report_lines = [
            "=" * 70,
            "BACKLINK ACTION PREDICTION MODEL - EVALUATION REPORT",
            "=" * 70,
            "",
            f"Model Type: {self.model_type}",
            f"Test Samples: {len(self.y_test)}",
            f"Number of Features: {len(self.feature_names)}",
            "",
            "=" * 70,
            "OVERALL METRICS",
            "=" * 70,
            f"Accuracy: {metrics['accuracy']:.4f}",
            f"Macro Precision: {metrics['precision_macro']:.4f}",
            f"Macro Recall: {metrics['recall_macro']:.4f}",
            f"Macro F1-Score: {metrics['f1_macro']:.4f}",
            "",
            "=" * 70,
            "PRECISION PER CLASS",
            "=" * 70,
        ]
        
        for action in self.action_classes:
            precision = metrics['precision_per_class'][action]
            recall = metrics['recall_per_class'][action]
            f1 = metrics['f1_per_class'][action]
            report_lines.append(f"{action:15s} | Precision: {precision:.4f} | Recall: {recall:.4f} | F1: {f1:.4f}")
        
        report_lines.extend([
            "",
            "=" * 70,
            "CONFUSION MATRIX",
            "=" * 70,
            "",
        ])
        
        # Confusion matrix as table
        cm = np.array(metrics['confusion_matrix'])
        header = " " * 15 + " | " + " | ".join(f"{action:10s}" for action in self.action_classes)
        report_lines.append(header)
        report_lines.append("-" * len(header))
        
        for i, action in enumerate(self.action_classes):
            row = f"{action:15s} | " + " | ".join(f"{cm[i,j]:10d}" for j in range(len(self.action_classes)))
            report_lines.append(row)
        
        report_lines.extend([
            "",
            "=" * 70,
            "FAILURE RATE REDUCTION",
            "=" * 70,
            f"Baseline Accuracy (Most Common Class): {reduction['baseline_accuracy']:.4f}",
            f"Model Accuracy: {reduction['model_accuracy']:.4f}",
            f"Accuracy Improvement: {reduction['accuracy_improvement']:.4f}",
            f"Relative Improvement: {reduction['relative_improvement_pct']:.2f}%",
            "",
            f"Baseline Error Rate: {reduction['baseline_error_rate']:.4f}",
            f"Model Error Rate: {reduction['model_error_rate']:.4f}",
            f"Failure Rate Reduction: {reduction['failure_rate_reduction']:.4f}",
            f"Failure Rate Reduction: {reduction['failure_rate_reduction_pct']:.2f}%",
            "",
            "=" * 70,
            "DETAILED CLASSIFICATION REPORT",
            "=" * 70,
            "",
            class_report,
            "",
            "=" * 70,
        ])
        
        report_text = "\n".join(report_lines)
        
        # Save report
        if output_path:
            report_file = Path(output_path)
        else:
            report_file = self.dataset_dir.parent / 'evaluation_report.txt'
        
        with open(report_file, 'w') as f:
            f.write(report_text)
        
        logger.info(f"Evaluation report saved to {report_file}")
        
        # Also save metrics as JSON
        metrics_file = self.dataset_dir.parent / 'evaluation_metrics.json'
        with open(metrics_file, 'w') as f:
            json.dump({
                'metrics': metrics,
                'failure_rate_reduction': reduction,
            }, f, indent=2)
        
        logger.info(f"Metrics saved to {metrics_file}")
        
        return report_text


def main():
    """Main evaluation function"""
    import argparse
    
    parser = argparse.ArgumentParser(description='Evaluate action prediction model')
    parser.add_argument('--model', default='ml/export_model.pkl', help='Model file path')
    parser.add_argument('--dataset-dir', default='ml/datasets', help='Dataset directory')
    parser.add_argument('--output', help='Output report path (optional)')
    
    args = parser.parse_args()
    
    # Create evaluator
    evaluator = ModelEvaluator(args.model, args.dataset_dir)
    
    # Load model
    evaluator.load_model()
    
    # Load test data
    evaluator.load_test_data()
    
    # Make predictions
    evaluator.predict()
    
    # Generate report
    report = evaluator.generate_report(args.output)
    
    # Plot confusion matrix
    evaluator.plot_confusion_matrix()
    
    # Print report to console
    print("\n" + report)
    
    logger.info("\nEvaluation complete!")


if __name__ == "__main__":
    main()

