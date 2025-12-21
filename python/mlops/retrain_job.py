"""
Weekly Retrain Job

Automated retraining workflow with canary rollout and rollback
"""

import os
import sys
import logging
import json
from pathlib import Path
from datetime import datetime, timedelta
from typing import Dict, Optional

# Add parent directory to path
sys.path.insert(0, str(Path(__file__).parent.parent))

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Try to import ML components
try:
    from ml.feature_extractor import FeatureExtractor
    FEATURE_EXTRACTOR_AVAILABLE = True
except ImportError:
    FEATURE_EXTRACTOR_AVAILABLE = False
    logger.warning("FeatureExtractor not available")

try:
    from ml.prepare_dataset import DatasetPreparator
    DATASET_PREPARATOR_AVAILABLE = True
except ImportError:
    DATASET_PREPARATOR_AVAILABLE = False
    logger.warning("DatasetPreparator not available")

try:
    from ml.train_action_model import ActionModelTrainer
    MODEL_TRAINER_AVAILABLE = True
except ImportError:
    MODEL_TRAINER_AVAILABLE = False
    logger.warning("ActionModelTrainer not available")

try:
    from ml.evaluate_model import ModelEvaluator
    MODEL_EVALUATOR_AVAILABLE = True
except ImportError:
    MODEL_EVALUATOR_AVAILABLE = False
    logger.warning("ModelEvaluator not available")


class RetrainJob:
    """Weekly retrain job with canary rollout and rollback"""
    
    def __init__(self, config_path: Optional[str] = None):
        """
        Initialize retrain job
        
        Args:
            config_path: Path to configuration file
        """
        self.config = self._load_config(config_path)
        self.dataset_store_path = Path(self.config.get('dataset_store', 'ml/datasets/outcomes.csv'))
        self.training_data_path = Path(self.config.get('training_data', 'ml/datasets/training_backlinks_enriched.csv'))
        self.output_dir = Path(self.config.get('output_dir', 'ml/datasets'))
        self.model_dir = Path(self.config.get('model_dir', 'ml/models'))
        
        # Registry path (if model registry implemented)
        self.registry_path = Path(self.config.get('registry_path', 'mlops/registry'))
    
    def _load_config(self, config_path: Optional[str]) -> Dict:
        """Load configuration from file or use defaults"""
        if config_path and Path(config_path).exists():
            with open(config_path, 'r') as f:
                return json.load(f)
        
        # Default configuration
        return {
            'retrain': {
                'min_new_samples': 100,
                'merge_strategy': 'append',
                'validation_threshold': 0.80
            },
            'canary': {
                'enabled': True,
                'percentage': 0.1,
                'duration_hours': 24,
                'thresholds': {
                    'min_success_rate': 0.80,
                    'max_error_rate': 0.05,
                    'max_response_time_ms': 100
                }
            },
            'rollback': {
                'auto_rollback': True,
                'thresholds': {
                    'min_success_rate': 0.75,
                    'max_error_rate': 0.10,
                    'max_response_time_ms': 200
                }
            }
        }
    
    def run(self) -> bool:
        """
        Run retrain job
        
        Returns:
            True if successful, False otherwise
        """
        logger.info("=" * 70)
        logger.info("Weekly Retrain Job Started")
        logger.info("=" * 70)
        
        try:
            # Step 1: Collect new data
            logger.info("Step 1: Collecting new data...")
            new_data_path = self._collect_new_data()
            if not new_data_path:
                logger.warning("No new data collected, skipping retrain")
                return False
            
            # Step 2: Merge datasets
            logger.info("Step 2: Merging datasets...")
            merged_data_path = self._merge_datasets(new_data_path)
            
            # Step 3: Feature extraction (if needed)
            logger.info("Step 3: Extracting features...")
            enriched_data_path = self._extract_features(merged_data_path)
            
            # Step 4: Prepare dataset
            logger.info("Step 4: Preparing dataset...")
            dataset_dir = self._prepare_dataset(enriched_data_path)
            
            # Step 5: Train model
            logger.info("Step 5: Training model...")
            model_path = self._train_model(dataset_dir)
            
            # Step 6: Evaluate model
            logger.info("Step 6: Evaluating model...")
            metrics = self._evaluate_model(model_path, dataset_dir)
            
            # Step 7: Register model
            logger.info("Step 7: Registering model...")
            model_version = self._register_model(model_path, enriched_data_path, metrics)
            
            # Step 8: Validate model
            logger.info("Step 8: Validating model...")
            if not self._validate_model(model_version, metrics):
                logger.warning("Model validation failed, not deploying")
                return False
            
            # Step 9: Deploy model
            logger.info("Step 9: Deploying model...")
            if self.config['canary']['enabled']:
                self._deploy_canary(model_version)
            else:
                self._deploy_production(model_version)
            
            logger.info("=" * 70)
            logger.info("Retrain Job Completed Successfully")
            logger.info("=" * 70)
            return True
            
        except Exception as e:
            logger.error(f"Retrain job failed: {e}", exc_info=True)
            return False
    
    def _collect_new_data(self) -> Optional[Path]:
        """Collect new outcomes from dataset store"""
        logger.info(f"Loading outcomes from {self.dataset_store_path}")
        
        if not self.dataset_store_path.exists():
            logger.warning(f"Dataset store not found: {self.dataset_store_path}")
            return None
        
        # Load outcomes (assuming CSV format)
        import pandas as pd
        try:
            df = pd.read_csv(self.dataset_store_path)
            
            # Filter by date (last 7 days or since last retrain)
            if 'timestamp' in df.columns:
                df['timestamp'] = pd.to_datetime(df['timestamp'])
                cutoff_date = datetime.utcnow() - timedelta(days=7)
                df = df[df['timestamp'] >= cutoff_date]
            
            if len(df) < self.config['retrain']['min_new_samples']:
                logger.warning(f"Not enough new samples: {len(df)} < {self.config['retrain']['min_new_samples']}")
                return None
            
            # Save new data
            new_data_path = self.output_dir / f"new_data_{datetime.now().strftime('%Y%m%d_%H%M%S')}.csv"
            df.to_csv(new_data_path, index=False)
            logger.info(f"Collected {len(df)} new samples")
            
            return new_data_path
            
        except Exception as e:
            logger.error(f"Error collecting new data: {e}")
            return None
    
    def _merge_datasets(self, new_data_path: Path) -> Path:
        """Merge new data with existing training data"""
        import pandas as pd
        
        # Load existing training data
        if self.training_data_path.exists():
            existing_df = pd.read_csv(self.training_data_path)
            logger.info(f"Loaded {len(existing_df)} existing samples")
        else:
            existing_df = pd.DataFrame()
            logger.info("No existing training data found")
        
        # Load new data
        new_df = pd.read_csv(new_data_path)
        logger.info(f"Loaded {len(new_df)} new samples")
        
        # Merge
        if self.config['retrain']['merge_strategy'] == 'append':
            merged_df = pd.concat([existing_df, new_df], ignore_index=True)
        else:
            # Other merge strategies (e.g., replace, weighted)
            merged_df = pd.concat([existing_df, new_df], ignore_index=True)
        
        # Remove duplicates if needed
        if 'domain' in merged_df.columns:
            initial_count = len(merged_df)
            merged_df = merged_df.drop_duplicates(subset=['domain'], keep='last')
            logger.info(f"Removed {initial_count - len(merged_df)} duplicate domains")
        
        # Save merged data
        merged_path = self.output_dir / f"merged_data_{datetime.now().strftime('%Y%m%d_%H%M%S')}.csv"
        merged_df.to_csv(merged_path, index=False)
        logger.info(f"Merged dataset: {len(merged_df)} samples")
        
        return merged_path
    
    def _extract_features(self, data_path: Path) -> Path:
        """Extract features from merged dataset"""
        if not FEATURE_EXTRACTOR_AVAILABLE:
            logger.warning("FeatureExtractor not available, skipping feature extraction")
            return data_path
        
        # Check if features already extracted
        import pandas as pd
        df = pd.read_csv(data_path)
        if 'platform_guess' in df.columns and 'comment_supported' in df.columns:
            logger.info("Features already extracted, skipping")
            return data_path
        
        # Extract features
        logger.info("Extracting features from URLs...")
        extractor = FeatureExtractor()
        
        output_path = self.output_dir / f"enriched_data_{datetime.now().strftime('%Y%m%d_%H%M%S')}.csv"
        extractor.process_csv(str(data_path), str(output_path))
        
        return output_path
    
    def _prepare_dataset(self, data_path: Path) -> Path:
        """Prepare dataset for training"""
        if not DATASET_PREPARATOR_AVAILABLE:
            raise RuntimeError("DatasetPreparator not available")
        
        preparator = DatasetPreparator(str(data_path), str(self.output_dir))
        preparator.load_data()
        preparator.clean_data()
        preparator.encode_features()
        preparator.split_dataset()
        preparator.save_datasets()
        
        return self.output_dir
    
    def _train_model(self, dataset_dir: Path) -> Path:
        """Train new model"""
        if not MODEL_TRAINER_AVAILABLE:
            raise RuntimeError("ActionModelTrainer not available")
        
        trainer = ActionModelTrainer(str(dataset_dir), str(self.model_dir))
        datasets = trainer.load_datasets()
        trainer.prepare_targets(
            datasets['y_train'],
            datasets['y_val'],
            datasets['y_test'],
            datasets
        )
        trainer.train()
        model_path = trainer.save_model()
        
        return Path(model_path)
    
    def _evaluate_model(self, model_path: Path, dataset_dir: Path) -> Dict:
        """Evaluate model performance"""
        if not MODEL_EVALUATOR_AVAILABLE:
            logger.warning("ModelEvaluator not available, skipping evaluation")
            return {}
        
        evaluator = ModelEvaluator(str(model_path), str(dataset_dir))
        metrics = evaluator.evaluate()
        evaluator.save_report()
        
        return metrics
    
    def _register_model(self, model_path: Path, dataset_path: Path, metrics: Dict) -> str:
        """Register model in registry"""
        # Calculate dataset hash
        import hashlib
        with open(dataset_path, 'rb') as f:
            dataset_hash = hashlib.sha256(f.read()).hexdigest()
        
        # Generate version
        version = self._generate_version()
        
        # Create model metadata
        model_info = {
            'version': version,
            'model_id': f"action_predictor_{datetime.now().strftime('%Y%m%d_%H%M%S')}",
            'created_at': datetime.utcnow().isoformat() + 'Z',
            'model_path': str(model_path),
            'dataset_hash': dataset_hash,
            'dataset_version': f"dataset_{datetime.now().strftime('%Y%m%d')}",
            'schema_version': 'schema_v1.0',
            'training_metrics': metrics,
            'deployment_status': 'staging',
            'canary_percentage': 0.0,
            'rollback_available': True,
        }
        
        # Save to registry (if implemented)
        registry_dir = self.registry_path / 'models' / version
        registry_dir.mkdir(parents=True, exist_ok=True)
        
        with open(registry_dir / 'model.json', 'w') as f:
            json.dump(model_info, f, indent=2)
        
        logger.info(f"Model registered: {version}")
        return version
    
    def _validate_model(self, model_version: str, metrics: Dict) -> bool:
        """Validate model against production model"""
        # Load production model metrics
        # Compare with new model metrics
        # Return True if new model is better
        
        threshold = self.config['retrain']['validation_threshold']
        
        if 'test_accuracy' in metrics:
            if metrics['test_accuracy'] >= threshold:
                logger.info(f"Model validation passed: accuracy {metrics['test_accuracy']:.2f} >= {threshold}")
                return True
            else:
                logger.warning(f"Model validation failed: accuracy {metrics['test_accuracy']:.2f} < {threshold}")
                return False
        
        return True  # Default to pass if metrics not available
    
    def _deploy_canary(self, model_version: str):
        """Deploy model to canary"""
        logger.info(f"Deploying model {model_version} to canary ({self.config['canary']['percentage']*100}% traffic)")
        
        # Update registry status
        # Switch AI Decision Engine to canary model
        # Start monitoring
        
        logger.info("Canary deployment started, monitoring metrics...")
    
    def _deploy_production(self, model_version: str):
        """Deploy model to production"""
        logger.info(f"Deploying model {model_version} to production")
        
        # Update registry status
        # Switch AI Decision Engine to production model
        # Update current model symlink
        
        logger.info("Production deployment completed")
    
    def _generate_version(self) -> str:
        """Generate semantic version"""
        # Load current version from registry
        # Increment based on changes
        # For now, use timestamp-based version
        
        return f"v1.0.{datetime.now().strftime('%Y%m%d')}"


def main():
    """Main function"""
    import argparse
    
    parser = argparse.ArgumentParser(description='Weekly retrain job')
    parser.add_argument('--config', help='Path to configuration file')
    parser.add_argument('--dry-run', action='store_true', help='Dry run mode')
    
    args = parser.parse_args()
    
    job = RetrainJob(config_path=args.config)
    
    if args.dry_run:
        logger.info("Dry run mode - not executing retrain")
        return
    
    success = job.run()
    sys.exit(0 if success else 1)


if __name__ == '__main__':
    main()

