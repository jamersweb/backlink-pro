"""
Model Versioning System

Manages model versions, rollback, and deployment
"""

import pickle
import json
import os
import shutil
from pathlib import Path
from datetime import datetime
from typing import Dict, Optional, List
import logging

logger = logging.getLogger(__name__)


class ModelVersion:
    """Represents a model version"""
    
    def __init__(self, version: str, model_path: Path, metadata: Dict):
        """
        Initialize model version
        
        Args:
            version: Version string (e.g., 'v1.0.0', 'v1.1.0')
            model_path: Path to model file
            metadata: Version metadata
        """
        self.version = version
        self.model_path = Path(model_path)
        self.metadata = metadata
    
    def to_dict(self) -> Dict:
        """Convert to dictionary"""
        return {
            'version': self.version,
            'model_path': str(self.model_path),
            'metadata': self.metadata,
        }


class ModelVersionManager:
    """
    Manages model versions, rollback, and deployment
    """
    
    def __init__(self, models_dir: str = "ml/models", versions_dir: str = "ml/models/versions"):
        """
        Initialize version manager
        
        Args:
            models_dir: Directory for current model
            versions_dir: Directory for versioned models
        """
        self.models_dir = Path(models_dir)
        self.versions_dir = Path(versions_dir)
        self.versions_dir.mkdir(parents=True, exist_ok=True)
        
        self.versions_file = self.versions_dir / 'versions.json'
        self.versions = self._load_versions()
    
    def _load_versions(self) -> List[ModelVersion]:
        """Load version history"""
        if not self.versions_file.exists():
            return []
        
        try:
            with open(self.versions_file, 'r') as f:
                data = json.load(f)
                versions = []
                for v_data in data.get('versions', []):
                    version = ModelVersion(
                        version=v_data['version'],
                        model_path=Path(v_data['model_path']),
                        metadata=v_data.get('metadata', {})
                    )
                    versions.append(version)
                return versions
        except Exception as e:
            logger.error(f"Error loading versions: {e}")
            return []
    
    def _save_versions(self):
        """Save version history"""
        try:
            data = {
                'versions': [v.to_dict() for v in self.versions],
                'last_updated': datetime.utcnow().isoformat() + 'Z',
            }
            with open(self.versions_file, 'w') as f:
                json.dump(data, f, indent=2)
        except Exception as e:
            logger.error(f"Error saving versions: {e}")
    
    def get_next_version(self, current_version: Optional[str] = None) -> str:
        """
        Get next version number
        
        Args:
            current_version: Current version (e.g., 'v1.0.0')
        
        Returns:
            Next version string (e.g., 'v1.1.0')
        """
        if current_version:
            # Parse version
            try:
                parts = current_version.lstrip('v').split('.')
                major, minor, patch = int(parts[0]), int(parts[1]), int(parts[2])
                # Increment minor version
                return f"v{major}.{minor + 1}.0"
            except:
                pass
        
        # Default: start at v1.0.0
        if not self.versions:
            return "v1.0.0"
        
        # Get latest version
        latest = self.get_latest_version()
        if latest:
            return self.get_next_version(latest.version)
        
        return "v1.0.0"
    
    def get_latest_version(self) -> Optional[ModelVersion]:
        """Get latest version"""
        if not self.versions:
            return None
        
        # Sort by version number
        sorted_versions = sorted(
            self.versions,
            key=lambda v: self._version_to_tuple(v.version),
            reverse=True
        )
        return sorted_versions[0]
    
    def _version_to_tuple(self, version: str) -> tuple:
        """Convert version string to tuple for sorting"""
        try:
            parts = version.lstrip('v').split('.')
            return tuple(int(p) for p in parts)
        except:
            return (0, 0, 0)
    
    def create_version(self, model_path: Path, metadata: Optional[Dict] = None) -> ModelVersion:
        """
        Create a new model version
        
        Args:
            model_path: Path to model file
            metadata: Optional metadata (training stats, accuracy, etc.)
        
        Returns:
            ModelVersion object
        """
        # Get next version
        latest = self.get_latest_version()
        next_version = self.get_next_version(latest.version if latest else None)
        
        # Create version directory
        version_dir = self.versions_dir / next_version
        version_dir.mkdir(parents=True, exist_ok=True)
        
        # Copy model to version directory
        version_model_path = version_dir / 'model.pkl'
        shutil.copy2(model_path, version_model_path)
        
        # Create version metadata
        version_metadata = {
            'created_at': datetime.utcnow().isoformat() + 'Z',
            'model_path': str(version_model_path),
            **(metadata or {})
        }
        
        # Create version object
        version = ModelVersion(
            version=next_version,
            model_path=version_model_path,
            metadata=version_metadata
        )
        
        # Add to versions list
        self.versions.append(version)
        self._save_versions()
        
        logger.info(f"Created model version: {next_version}")
        
        return version
    
    def deploy_version(self, version: str, target_path: str = "ml/export_model.pkl") -> bool:
        """
        Deploy a version to production
        
        Args:
            version: Version to deploy
            target_path: Target path for production model
        
        Returns:
            True if successful
        """
        # Find version
        version_obj = None
        for v in self.versions:
            if v.version == version:
                version_obj = v
                break
        
        if not version_obj:
            logger.error(f"Version {version} not found")
            return False
        
        if not version_obj.model_path.exists():
            logger.error(f"Model file not found: {version_obj.model_path}")
            return False
        
        # Backup current model if exists
        target = Path(target_path)
        if target.exists():
            backup_path = target.parent / f"{target.stem}_backup_{datetime.now().strftime('%Y%m%d_%H%M%S')}.pkl"
            shutil.copy2(target, backup_path)
            logger.info(f"Backed up current model to {backup_path}")
        
        # Copy version to target
        shutil.copy2(version_obj.model_path, target)
        
        # Update metadata
        version_obj.metadata['deployed_at'] = datetime.utcnow().isoformat() + 'Z'
        version_obj.metadata['deployed_to'] = str(target)
        self._save_versions()
        
        logger.info(f"Deployed version {version} to {target}")
        
        return True
    
    def rollback(self, target_path: str = "ml/export_model.pkl") -> Optional[str]:
        """
        Rollback to previous version
        
        Args:
            target_path: Target path for production model
        
        Returns:
            Version rolled back to, or None if failed
        """
        if len(self.versions) < 2:
            logger.warning("Not enough versions to rollback")
            return None
        
        # Get versions sorted by creation time
        sorted_versions = sorted(
            self.versions,
            key=lambda v: v.metadata.get('created_at', ''),
            reverse=True
        )
        
        # Current version is the latest
        current = sorted_versions[0]
        
        # Previous version
        previous = sorted_versions[1]
        
        # Deploy previous version
        if self.deploy_version(previous.version, target_path):
            logger.info(f"Rolled back from {current.version} to {previous.version}")
            return previous.version
        
        return None
    
    def list_versions(self) -> List[Dict]:
        """List all versions"""
        sorted_versions = sorted(
            self.versions,
            key=lambda v: self._version_to_tuple(v.version),
            reverse=True
        )
        
        return [{
            'version': v.version,
            'created_at': v.metadata.get('created_at'),
            'deployed_at': v.metadata.get('deployed_at'),
            'training_stats': v.metadata.get('training_stats', {}),
            'model_path': str(v.model_path),
        } for v in sorted_versions]
    
    def get_version_info(self, version: str) -> Optional[Dict]:
        """Get information about a specific version"""
        for v in self.versions:
            if v.version == version:
                return {
                    'version': v.version,
                    'model_path': str(v.model_path),
                    'metadata': v.metadata,
                }
        return None


def main():
    """Main function for testing"""
    import argparse
    
    parser = argparse.ArgumentParser(description='Model versioning management')
    parser.add_argument('command', choices=['list', 'create', 'deploy', 'rollback', 'info'],
                       help='Command to execute')
    parser.add_argument('--version', help='Version string')
    parser.add_argument('--model', help='Model file path')
    parser.add_argument('--target', default='ml/export_model.pkl', help='Target path for deploy')
    
    args = parser.parse_args()
    
    manager = ModelVersionManager()
    
    if args.command == 'list':
        versions = manager.list_versions()
        print("\nModel Versions:")
        print("=" * 70)
        for v in versions:
            print(f"Version: {v['version']}")
            print(f"  Created: {v['created_at']}")
            print(f"  Deployed: {v.get('deployed_at', 'Not deployed')}")
            print(f"  Path: {v['model_path']}")
            print()
    
    elif args.command == 'create':
        if not args.model:
            print("Error: --model required for create")
            return
        
        metadata = {
            'training_stats': {},
            'created_by': 'retraining_job',
        }
        version = manager.create_version(Path(args.model), metadata)
        print(f"Created version: {version.version}")
    
    elif args.command == 'deploy':
        if not args.version:
            print("Error: --version required for deploy")
            return
        
        success = manager.deploy_version(args.version, args.target)
        if success:
            print(f"Deployed version {args.version} to {args.target}")
        else:
            print(f"Failed to deploy version {args.version}")
    
    elif args.command == 'rollback':
        rolled_back = manager.rollback(args.target)
        if rolled_back:
            print(f"Rolled back to version: {rolled_back}")
        else:
            print("Rollback failed")
    
    elif args.command == 'info':
        if not args.version:
            print("Error: --version required for info")
            return
        
        info = manager.get_version_info(args.version)
        if info:
            print(json.dumps(info, indent=2))
        else:
            print(f"Version {args.version} not found")


if __name__ == "__main__":
    main()

