# Fix matplotlib home directory issue (must be first)
import os, tempfile
os.environ.setdefault("MPLCONFIGDIR", os.path.join(tempfile.gettempdir(), "mplconfig"))

"""
Weekly Retraining Job for Continuous Learning

Workflow:
1. Collect new feedback
2. Append to dataset
3. Retrain model
4. Evaluate new model
5. Version and deploy (if better)
"""

# Fix Windows asyncio issue before any other imports
import sys
import os
if sys.platform == 'win32':
    # Workaround for Windows asyncio _overlapped import error
    # This happens when Windows system files have issues
    try:
        # Try to import and patch asyncio before joblib uses it
        import asyncio
        # Set to use SelectorEventLoop which doesn't require _overlapped
        try:
            if hasattr(asyncio, 'WindowsSelectorEventLoopPolicy'):
                asyncio.set_event_loop_policy(asyncio.WindowsSelectorEventLoopPolicy())
        except Exception:
            # If setting policy fails, try to create a new event loop
            try:
                loop = asyncio.new_event_loop()
                asyncio.set_event_loop(loop)
            except Exception:
                pass
    except OSError as e:
        # If asyncio import fails due to _overlapped, monkey-patch it
        if '10106' in str(e) or '_overlapped' in str(e):
            # Create a dummy asyncio module to prevent import errors
            class DummyAsyncio:
                def __getattr__(self, name):
                    return None
            import sys
            sys.modules['asyncio'] = DummyAsyncio()
    except Exception:
        pass

import os
import logging
import traceback
from pathlib import Path
from datetime import datetime
import argparse

# Add user site-packages to path (for packages installed with --user)
# This is critical for finding pandas and other ML packages
user_site_added = False

# Method 1: Try using site module
try:
    import site
    user_site = site.getusersitepackages()
    if user_site and os.path.isdir(user_site) and user_site not in sys.path:
        sys.path.insert(0, user_site)
        user_site_added = True
except Exception:
    pass

# Method 2: Try common Windows user site-packages locations
if not user_site_added:
    # Get home directory from multiple possible sources
    home = (os.environ.get('USERPROFILE') or 
            os.environ.get('HOME') or 
            os.path.expanduser('~'))
    
    if home:
        # Try Python version-specific paths first
        python_version = f"{sys.version_info.major}{sys.version_info.minor}"
        common_paths = [
            os.path.join(home, 'AppData', 'Roaming', 'Python', f'Python{python_version}', 'site-packages'),
            os.path.join(home, 'AppData', 'Roaming', 'Python', 'Python312', 'site-packages'),
            os.path.join(home, 'AppData', 'Roaming', 'Python', 'Python311', 'site-packages'),
            os.path.join(home, 'AppData', 'Roaming', 'Python', 'Python310', 'site-packages'),
            os.path.join(home, 'AppData', 'Roaming', 'Python', 'Python39', 'site-packages'),
        ]
        for path in common_paths:
            if os.path.isdir(path) and path not in sys.path:
                sys.path.insert(0, path)
                user_site_added = True
                break

# Method 3: Try to find pandas in any existing sys.path location
if not user_site_added:
    for path in sys.path:
        pandas_path = os.path.join(path, 'pandas')
        if os.path.isdir(pandas_path):
            if path not in sys.path:
                sys.path.insert(0, path)
            break

# Method 4: Windows-specific hardcoded fallback (if on Windows)
if not user_site_added and sys.platform == 'win32':
    # Try common Windows paths directly
    win_paths = [
        r'C:\Users\Hp\AppData\Roaming\Python\Python312\site-packages',
        r'C:\Users\Hp\AppData\Roaming\Python\Python311\site-packages',
        r'C:\Users\Hp\AppData\Roaming\Python\Python310\site-packages',
    ]
    for path in win_paths:
        if os.path.isdir(path) and path not in sys.path:
            sys.path.insert(0, path)
            break

# Add parent directory to path
sys.path.insert(0, str(Path(__file__).parent.parent))

from ml.feedback_collector import FeedbackCollector
from ml.prepare_dataset import DatasetPreparator
from ml.train_action_model import ActionModelTrainer
from ml.evaluate_model import ModelEvaluator
from ml.model_versioning import ModelVersionManager

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)


class RetrainingWorkflow:
    """Weekly retraining workflow"""
    
    def __init__(self, 
                 dataset_dir: str = "ml/datasets",
                 models_dir: str = "ml/models",
                 log_dir: str = "logs"):
        """
        Initialize retraining workflow
        
        Args:
            dataset_dir: Dataset directory
            models_dir: Models directory
            log_dir: Log directory
        """
        self.dataset_dir = Path(dataset_dir)
        self.models_dir = Path(models_dir)
        self.log_dir = Path(log_dir)
        
        self.feedback_collector = FeedbackCollector(
            log_dir=str(self.log_dir),
            dataset_dir=str(self.dataset_dir)
        )
        self.version_manager = ModelVersionManager(
            models_dir=str(self.models_dir)
        )
    
    def collect_feedback(self, api_client=None, since_days: int = 7) -> Path:
        """
        Step 1: Collect new feedback and append to dataset
        
        Args:
            api_client: Optional API client
            since_days: Days to look back
        
        Returns:
            Path to updated dataset
        """
        # Ensure since_days is an integer
        try:
            since_days = int(since_days)
        except (ValueError, TypeError):
            since_days = 7
        
        logger.info("=" * 70)
        logger.info("Step 1: Collecting Feedback")
        logger.info("=" * 70)
        
        try:
            dataset_path = self.feedback_collector.collect_and_append(
                api_client=api_client,
                since_days=since_days
            )
            
            logger.info(f"Feedback collection complete: {dataset_path}")
            return dataset_path
        except Exception as e:
            # Format detailed error with file name and line number
            tb = traceback.extract_tb(e.__traceback__)
            error_msg = f"\n{'='*70}\n"
            error_msg += f"ERROR in collect_feedback step\n"
            error_msg += f"{'='*70}\n"
            error_msg += f"Error Type: {type(e).__name__}\n"
            error_msg += f"Error Message: {str(e)}\n"
            
            if tb:
                error_msg += f"\nError Location:\n"
                for frame in tb[-3:]:
                    error_msg += f"  File: {frame.filename}\n"
                    error_msg += f"  Line: {frame.lineno}\n"
                    error_msg += f"  Function: {frame.name}\n"
                    if frame.line:
                        error_msg += f"  Code: {frame.line.strip()}\n"
                    error_msg += "\n"
            
            error_msg += f"\nFull Traceback:\n{traceback.format_exc()}\n"
            error_msg += f"{'='*70}\n"
            logger.error(error_msg)
            raise
    
    def prepare_dataset(self, dataset_path: Path) -> Path:
        """
        Step 2: Prepare dataset for training
        
        Args:
            dataset_path: Path to enriched dataset
        
        Returns:
            Path to prepared datasets directory
        """
        logger.info("=" * 70)
        logger.info("Step 2: Preparing Dataset")
        logger.info("=" * 70)
        
        # Check if dataset file exists and has data
        use_raw = False
        if not dataset_path.exists():
            use_raw = True
        else:
            # Check if file is empty or has no data
            try:
                import pandas as pd
                test_df = pd.read_csv(dataset_path, nrows=1)
                if len(test_df) == 0:
                    logger.warning(f"Dataset file exists but is empty, using raw_backlinks.csv")
                    use_raw = True
            except Exception as e:
                logger.warning(f"Error checking dataset file: {e}, trying raw_backlinks.csv")
                use_raw = True
        
        if use_raw:
            # Try to find existing dataset in parent directory first
            parent_dataset = dataset_path.parent.parent / dataset_path.name
            if parent_dataset.exists():
                try:
                    import pandas as pd
                    test_df = pd.read_csv(parent_dataset, nrows=1)
                    if len(test_df) > 0:
                        logger.info(f"Using existing dataset from: {parent_dataset}")
                        dataset_path = parent_dataset
                        use_raw = False
                except:
                    pass
            
            if use_raw:
                # Try in ml directory
                ml_dataset = Path(__file__).parent / dataset_path.name
                if ml_dataset.exists():
                    try:
                        import pandas as pd
                        test_df = pd.read_csv(ml_dataset, nrows=1)
                        if len(test_df) > 0:
                            logger.info(f"Using existing dataset from: {ml_dataset}")
                            dataset_path = ml_dataset
                            use_raw = False
                    except:
                        pass
            
            if use_raw:
                # Use raw_backlinks.csv as fallback
                raw_dataset = Path(__file__).parent / "raw_backlinks.csv"
                if raw_dataset.exists():
                    logger.info(f"Using raw dataset from: {raw_dataset}")
                    dataset_path = raw_dataset
                else:
                    raise FileNotFoundError(
                        f"Dataset file not found or empty: {dataset_path}\n"
                        f"No training data available. Please ensure you have:\n"
                        f"1. Completed automation tasks with success/failure results\n"
                        f"2. Or an existing dataset file at: {dataset_path}\n"
                        f"3. Or raw_backlinks.csv in ml directory"
                    )
        
        preparator = DatasetPreparator(
            input_file=str(dataset_path),
            output_dir=str(self.dataset_dir)
        )
        
        preparator.load_data()
        preparator.clean_data()
        
        # Try feature engineering, but continue if it fails
        try:
            preparator.engineer_features()  # Add feature engineering
        except Exception as e:
            logger.warning(f"Feature engineering failed: {e}. Continuing without engineered features.")
        
        preparator.encode_features()
        preparator.split_dataset(test_size=0.15, val_size=0.15)
        preparator.save_datasets()
        
        logger.info("Dataset preparation complete")
        return self.dataset_dir
    
    def train_model(
        self,
        model_type: str = None,
        use_smote: bool = False,
        use_optuna: bool = False,
        optuna_trials: int = 50
    ) -> Path:
        """
        Step 3: Train new model
        
        Args:
            model_type: Model type (xgboost, lightgbm, randomforest)
            use_smote: Whether to use SMOTE oversampling
            use_optuna: Whether to use Optuna for hyperparameter tuning
            optuna_trials: Number of Optuna trials (if use_optuna=True)
        
        Returns:
            Path to trained model
        """
        logger.info("=" * 70)
        logger.info("Step 3: Training New Model")
        logger.info("=" * 70)
        
        trainer = ActionModelTrainer(
            dataset_dir=str(self.dataset_dir),
            model_dir=str(self.models_dir)
        )
        
        datasets = trainer.load_datasets()
        trainer.train(
            datasets,
            model_type=model_type,
            use_smote=use_smote,
            use_optuna=use_optuna,
            optuna_trials=optuna_trials
        )
        
        # Save with timestamp
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        model_filename = f"export_model_{timestamp}.pkl"
        model_path = trainer.save_model(model_filename)
        
        logger.info(f"Model training complete: {model_path}")
        return model_path
    
    def evaluate_model(self, model_path: Path) -> dict:
        """
        Step 4: Evaluate new model
        
        Args:
            model_path: Path to trained model
        
        Returns:
            Evaluation metrics
        """
        logger.info("=" * 70)
        logger.info("Step 4: Evaluating New Model")
        logger.info("=" * 70)
        
        evaluator = ModelEvaluator(
            model_path=str(model_path),
            dataset_dir=str(self.dataset_dir)
        )
        
        evaluator.load_model()
        evaluator.load_test_data()
        evaluator.predict()
        
        metrics = evaluator.calculate_metrics()
        reduction = evaluator.calculate_failure_rate_reduction()
        
        # Generate report
        evaluator.generate_report()
        evaluator.plot_confusion_matrix()
        
        # Log metrics to monitoring dashboard
        try:
            from .monitoring_dashboard import ModelMonitor
            monitor = ModelMonitor()
            
            # Get model version from model path
            model_version = model_path.stem.replace('export_model_', 'v').replace('_', '.')
            if not model_version.startswith('v'):
                model_version = f"v{model_version}"
            
            # Combine all metrics
            all_metrics = {**metrics, 'failure_rate_reduction': reduction.get('failure_rate_reduction_pct', 0)}
            monitor.log_metrics(all_metrics, model_version=model_version)
            monitor.plot_metrics_trend()
            monitor.save_report()
            
            logger.info("Metrics logged to monitoring dashboard")
        except Exception as e:
            logger.warning(f"Could not log to monitoring dashboard: {e}")
        
        logger.info(f"Model evaluation complete")
        logger.info(f"Accuracy: {metrics['accuracy']:.4f}")
        logger.info(f"Macro F1: {metrics['f1_macro']:.4f}")
        
        return {
            'metrics': metrics,
            'failure_rate_reduction': reduction,
        }
    
    def compare_with_current(self, new_metrics: dict) -> bool:
        """
        Step 5: Compare new model with current production model
        
        Args:
            new_metrics: Metrics from new model
        
        Returns:
            True if new model is better
        """
        logger.info("=" * 70)
        logger.info("Step 5: Comparing with Current Model")
        logger.info("=" * 70)
        
        current_model_path = Path("ml/export_model.pkl")
        
        if not current_model_path.exists():
            logger.info("No current model found, new model is better by default")
            return True
        
        try:
            # Evaluate current model
            current_evaluator = ModelEvaluator(
                model_path=str(current_model_path),
                dataset_dir=str(self.dataset_dir)
            )
            current_evaluator.load_model()
            current_evaluator.load_test_data()
            current_evaluator.predict()
            
            current_metrics = current_evaluator.calculate_metrics()
            
            # Compare
            new_accuracy = new_metrics['metrics']['accuracy']
            current_accuracy = current_metrics['accuracy']
            
            improvement = new_accuracy - current_accuracy
            
            logger.info(f"Current model accuracy: {current_accuracy:.4f}")
            logger.info(f"New model accuracy: {new_accuracy:.4f}")
            logger.info(f"Improvement: {improvement:+.4f}")
            
            # New model is better if accuracy improved by at least 0.01 (1%)
            is_better = improvement >= 0.01
            
            if is_better:
                logger.info("New model is better, will deploy")
            else:
                logger.info("New model is not significantly better, keeping current")
            
            return is_better
            
        except Exception as e:
            logger.warning(f"Error comparing models: {e}, assuming new model is better")
            return True
    
    def version_and_deploy(self, model_path: Path, evaluation_metrics: dict, 
                          deploy: bool = True) -> str:
        """
        Step 6: Version and deploy model
        
        Args:
            model_path: Path to trained model
            evaluation_metrics: Evaluation metrics
            deploy: Whether to deploy to production
        
        Returns:
            Version string
        """
        logger.info("=" * 70)
        logger.info("Step 6: Versioning and Deployment")
        logger.info("=" * 70)
        
        # Create version with metadata
        metadata = {
            'training_stats': evaluation_metrics.get('metrics', {}),
            'failure_rate_reduction': evaluation_metrics.get('failure_rate_reduction', {}),
            'created_by': 'retraining_job',
            'dataset_size': self._get_dataset_size(),
        }
        
        version = self.version_manager.create_version(model_path, metadata)
        
        if deploy:
            # Deploy to production
            self.version_manager.deploy_version(version.version, "ml/export_model.pkl")
            logger.info(f"Deployed version {version.version} to production")
        else:
            logger.info(f"Created version {version.version} (not deployed)")
        
        return version.version
    
    def _get_dataset_size(self) -> int:
        """Get current dataset size"""
        try:
            train_file = self.dataset_dir / 'X_train.csv'
            if train_file.exists():
                import pandas as pd
                df = pd.read_csv(train_file)
                return len(df)
        except:
            pass
        return 0
    
    def run_full_workflow(
        self,
        api_client=None,
        since_days: int = 7,
        model_type: str = None,
        auto_deploy: bool = True,
        use_smote: bool = False,
        use_optuna: bool = False,
        optuna_trials: int = 50
    ) -> dict:
        """
        Run complete retraining workflow
        
        Args:
            api_client: Optional API client
            since_days: Days to look back for feedback
            model_type: Model type to train
            auto_deploy: Automatically deploy if better
        
        Returns:
            Workflow results
        """
        logger.info("=" * 70)
        logger.info("RETRAINING WORKFLOW STARTED")
        logger.info("=" * 70)
        logger.info(f"Timestamp: {datetime.utcnow().isoformat()}")
        
        results = {
            'started_at': datetime.utcnow().isoformat(),
            'steps_completed': [],
            'errors': [],
        }
        
        try:
            # Step 1: Collect feedback
            dataset_path = self.collect_feedback(api_client, since_days)
            results['steps_completed'].append('collect_feedback')
            results['dataset_path'] = str(dataset_path)
            
            # Step 2: Prepare dataset
            prepared_dir = self.prepare_dataset(dataset_path)
            results['steps_completed'].append('prepare_dataset')
            
            # Step 3: Train model
            model_path = self.train_model(
                model_type=model_type,
                use_smote=use_smote,
                use_optuna=use_optuna,
                optuna_trials=optuna_trials
            )
            results['steps_completed'].append('train_model')
            results['model_path'] = str(model_path)
            
            # Step 4: Evaluate
            evaluation_metrics = self.evaluate_model(model_path)
            results['steps_completed'].append('evaluate_model')
            results['evaluation_metrics'] = evaluation_metrics
            
            # Step 5: Compare
            is_better = self.compare_with_current(evaluation_metrics)
            results['steps_completed'].append('compare_models')
            results['is_better'] = is_better
            
            # Step 6: Version and deploy
            if is_better and auto_deploy:
                version = self.version_and_deploy(model_path, evaluation_metrics, deploy=True)
                results['steps_completed'].append('deploy')
                results['version'] = version
                results['deployed'] = True
            else:
                version = self.version_and_deploy(model_path, evaluation_metrics, deploy=False)
                results['steps_completed'].append('version')
                results['version'] = version
                results['deployed'] = False
            
        except Exception as e:
            # Format detailed error with file name and line number
            tb = traceback.extract_tb(e.__traceback__)
            error_detail = f"\n{'='*70}\n"
            error_detail += f"WORKFLOW ERROR\n"
            error_detail += f"{'='*70}\n"
            error_detail += f"Error Type: {type(e).__name__}\n"
            error_detail += f"Error Message: {str(e)}\n"
            
            if tb:
                error_detail += f"\nError Location:\n"
                # Show last 3 frames for context
                for frame in tb[-3:]:
                    error_detail += f"  File: {frame.filename}\n"
                    error_detail += f"  Line: {frame.lineno}\n"
                    error_detail += f"  Function: {frame.name}\n"
                    if frame.line:
                        error_detail += f"  Code: {frame.line.strip()}\n"
                    error_detail += "\n"
            
            error_detail += f"\nFull Traceback:\n{traceback.format_exc()}\n"
            error_detail += f"{'='*70}\n"
            
            logger.error(error_detail)
            results['errors'].append(error_detail)
        
        results['completed_at'] = datetime.utcnow().isoformat()
        results['success'] = len(results['errors']) == 0
        
        logger.info("=" * 70)
        logger.info("RETRAINING WORKFLOW COMPLETE")
        logger.info("=" * 70)
        logger.info(f"Success: {results['success']}")
        logger.info(f"Steps completed: {len(results['steps_completed'])}")
        
        return results


def main():
    """Main function"""
    parser = argparse.ArgumentParser(description='Weekly retraining job')
    parser.add_argument('--since-days', type=int, default=7, help='Days to look back for feedback')
    parser.add_argument('--model-type', choices=['xgboost', 'lightgbm', 'randomforest'],
                       help='Model type to train')
    parser.add_argument('--no-auto-deploy', action='store_true', help='Do not auto-deploy')
    parser.add_argument('--use-api', action='store_true', help='Collect from API')
    parser.add_argument('--use-smote', action='store_true', help='Use SMOTE oversampling')
    parser.add_argument('--use-optuna', action='store_true', help='Use Optuna hyperparameter tuning')
    parser.add_argument('--optuna-trials', type=int, default=50, help='Number of Optuna trials')
    
    args = parser.parse_args()
    
    # Initialize workflow
    workflow = RetrainingWorkflow()
    
    # Get API client if needed
    api_client = None
    if args.use_api:
        from api_client import LaravelAPIClient
        import os
        from dotenv import load_dotenv
        load_dotenv()
        
        api_url = os.getenv('LARAVEL_API_URL', 'http://nginx')
        api_token = os.getenv('LARAVEL_API_TOKEN') or os.getenv('APP_API_TOKEN') or ''
        api_client = LaravelAPIClient(api_url, api_token)
    
    # Run workflow
    results = workflow.run_full_workflow(
        api_client=api_client,
        since_days=args.since_days,
        model_type=args.model_type,
        auto_deploy=not args.no_auto_deploy,
        use_smote=args.use_smote,
        use_optuna=args.use_optuna,
        optuna_trials=args.optuna_trials
    )
    
    # Print summary
    print("\n" + "=" * 70)
    print("Workflow Summary")
    print("=" * 70)
    print(f"Success: {results['success']}")
    print(f"Steps: {', '.join(results['steps_completed'])}")
    if results.get('version'):
        print(f"Version: {results['version']}")
        print(f"Deployed: {results.get('deployed', False)}")
    if results.get('errors'):
        print(f"Errors: {results['errors']}")


if __name__ == "__main__":
    main()

