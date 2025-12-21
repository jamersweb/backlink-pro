"""
Domain Memory

Stores per-domain learning and patterns for faster, more reliable automation
"""

import sqlite3
import json
import logging
import threading
from typing import Dict, Optional, Any
from pathlib import Path
from datetime import datetime

logger = logging.getLogger(__name__)


class DomainMemory:
    """Stores and retrieves per-domain learning"""
    
    def __init__(self, db_path: str = "domain_memory.db", enable_cache: bool = True):
        """
        Initialize domain memory
        
        Args:
            db_path: Path to SQLite database
            enable_cache: Enable in-memory cache for speed
        """
        self.db_path = Path(db_path)
        self.enable_cache = enable_cache
        self._cache: Dict[str, Dict] = {}
        self._cache_lock = threading.Lock()
        self._init_database()
    
    def _init_database(self):
        """Initialize database schema"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS domain_memory (
                domain TEXT PRIMARY KEY,
                iframe_required INTEGER DEFAULT 0,
                recurring_popup_selectors TEXT,
                best_locator_strategy TEXT,
                login_flow_type TEXT,
                always_blocked INTEGER DEFAULT 0,
                sso_only INTEGER DEFAULT 0,
                stats TEXT,
                last_updated TEXT,
                created_at TEXT
            )
        """)
        
        conn.commit()
        conn.close()
        logger.debug(f"Domain memory database initialized at {self.db_path}")
    
    def _get_from_db(self, domain: str) -> Optional[Dict]:
        """Get domain data from database"""
        conn = sqlite3.connect(self.db_path)
        conn.row_factory = sqlite3.Row
        cursor = conn.cursor()
        
        cursor.execute("SELECT * FROM domain_memory WHERE domain = ?", (domain,))
        row = cursor.fetchone()
        
        conn.close()
        
        if not row:
            return None
        
        data = dict(row)
        
        # Parse JSON fields
        if data.get('recurring_popup_selectors'):
            try:
                data['recurring_popup_selectors'] = json.loads(data['recurring_popup_selectors'])
            except:
                data['recurring_popup_selectors'] = []
        else:
            data['recurring_popup_selectors'] = []
        
        if data.get('best_locator_strategy'):
            try:
                data['best_locator_strategy'] = json.loads(data['best_locator_strategy'])
            except:
                data['best_locator_strategy'] = {}
        else:
            data['best_locator_strategy'] = {}
        
        if data.get('stats'):
            try:
                data['stats'] = json.loads(data['stats'])
            except:
                data['stats'] = {}
        else:
            data['stats'] = {}
        
        # Convert boolean fields
        data['iframe_required'] = bool(data.get('iframe_required', 0))
        data['always_blocked'] = bool(data.get('always_blocked', 0))
        data['sso_only'] = bool(data.get('sso_only', 0))
        
        return data
    
    def _save_to_db(self, domain: str, data: Dict):
        """Save domain data to database"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        now = datetime.utcnow().isoformat()
        
        # Check if domain exists
        cursor.execute("SELECT domain FROM domain_memory WHERE domain = ?", (domain,))
        exists = cursor.fetchone() is not None
        
        # Prepare data
        recurring_popup_selectors = json.dumps(data.get('recurring_popup_selectors', []))
        best_locator_strategy = json.dumps(data.get('best_locator_strategy', {}))
        stats = json.dumps(data.get('stats', {}))
        
        if exists:
            # Update
            cursor.execute("""
                UPDATE domain_memory SET
                    iframe_required = ?,
                    recurring_popup_selectors = ?,
                    best_locator_strategy = ?,
                    login_flow_type = ?,
                    always_blocked = ?,
                    sso_only = ?,
                    stats = ?,
                    last_updated = ?
                WHERE domain = ?
            """, (
                int(data.get('iframe_required', 0)),
                recurring_popup_selectors,
                best_locator_strategy,
                data.get('login_flow_type'),
                int(data.get('always_blocked', 0)),
                int(data.get('sso_only', 0)),
                stats,
                now,
                domain
            ))
        else:
            # Insert
            cursor.execute("""
                INSERT INTO domain_memory (
                    domain, iframe_required, recurring_popup_selectors,
                    best_locator_strategy, login_flow_type, always_blocked,
                    sso_only, stats, last_updated, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            """, (
                domain,
                int(data.get('iframe_required', 0)),
                recurring_popup_selectors,
                best_locator_strategy,
                data.get('login_flow_type'),
                int(data.get('always_blocked', 0)),
                int(data.get('sso_only', 0)),
                stats,
                now,
                now
            ))
        
        conn.commit()
        conn.close()
    
    def get(self, domain: str) -> Dict:
        """
        Get domain memory data
        
        Args:
            domain: Domain name
        
        Returns:
            Dict with domain memory data (defaults if not found)
        """
        # Check cache first
        if self.enable_cache:
            with self._cache_lock:
                if domain in self._cache:
                    return self._cache[domain].copy()
        
        # Get from database
        data = self._get_from_db(domain)
        
        if not data:
            # Return defaults
            data = {
                'domain': domain,
                'iframe_required': False,
                'recurring_popup_selectors': [],
                'best_locator_strategy': {},
                'login_flow_type': None,
                'always_blocked': False,
                'sso_only': False,
                'stats': {},
                'last_updated': None,
                'created_at': None
            }
        
        # Update cache
        if self.enable_cache:
            with self._cache_lock:
                self._cache[domain] = data.copy()
        
        return data
    
    def update(self, domain: str, patch: Dict):
        """
        Update domain memory with patch
        
        Args:
            domain: Domain name
            patch: Dict with fields to update
        """
        # Get current data
        current = self.get(domain)
        
        # Apply patch
        current.update(patch)
        
        # Save to database
        self._save_to_db(domain, current)
        
        # Update cache
        if self.enable_cache:
            with self._cache_lock:
                self._cache[domain] = current.copy()
        
        logger.debug(f"Domain memory updated for {domain}: {patch}")
    
    def increment_stat(self, domain: str, key: str, amount: int = 1):
        """
        Increment a statistic for a domain
        
        Args:
            domain: Domain name
            key: Stat key
            amount: Amount to increment (default: 1)
        """
        data = self.get(domain)
        stats = data.get('stats', {})
        
        if key not in stats:
            stats[key] = 0
        
        stats[key] += amount
        
        self.update(domain, {'stats': stats})
    
    def record_iframe_used(self, domain: str, success: bool):
        """Record iframe usage"""
        if success:
            self.update(domain, {'iframe_required': True})
            self.increment_stat(domain, 'iframe_uses_success', 1)
        else:
            self.increment_stat(domain, 'iframe_uses_failed', 1)
    
    def record_popup_cleared(self, domain: str, selector: str, success: bool):
        """Record popup clear attempt"""
        if success:
            data = self.get(domain)
            popup_selectors = data.get('recurring_popup_selectors', [])
            
            # Add selector if not already present
            if selector not in popup_selectors:
                popup_selectors.append(selector)
                self.update(domain, {'recurring_popup_selectors': popup_selectors})
            
            self.increment_stat(domain, 'popup_clears_success', 1)
        else:
            self.increment_stat(domain, 'popup_clears_failed', 1)
    
    def record_locator_strategy(self, domain: str, role: str, strategy: str, success: bool):
        """Record locator strategy usage"""
        if success:
            data = self.get(domain)
            strategies = data.get('best_locator_strategy', {})
            
            # Update best strategy for role
            if role not in strategies:
                strategies[role] = strategy
            else:
                # Keep track of success rate
                current = strategies[role]
                if isinstance(current, dict):
                    # Already tracking stats
                    if current.get('strategy') == strategy:
                        current['success_count'] = current.get('success_count', 0) + 1
                    else:
                        # New strategy is better, replace
                        strategies[role] = strategy
                else:
                    # Convert to dict format
                    if current == strategy:
                        strategies[role] = {
                            'strategy': strategy,
                            'success_count': 2
                        }
                    else:
                        strategies[role] = strategy
            
            self.update(domain, {'best_locator_strategy': strategies})
            self.increment_stat(domain, f'locator_{role}_{strategy}_success', 1)
        else:
            self.increment_stat(domain, f'locator_{role}_{strategy}_failed', 1)
    
    def record_login_flow(self, domain: str, flow_type: str):
        """Record login flow type"""
        self.update(domain, {'login_flow_type': flow_type})
        self.increment_stat(domain, f'login_flow_{flow_type}', 1)
    
    def record_failure(self, domain: str, failure_type: str):
        """Record failure and potentially set skip flags"""
        self.increment_stat(domain, f'failures_{failure_type}', 1)
        
        # Check if we should set skip flags
        data = self.get(domain)
        stats = data.get('stats', {})
        
        # If consistently blocked, set always_blocked flag
        blocked_count = stats.get('failures_blocked', 0)
        total_attempts = sum(v for k, v in stats.items() if k.startswith('failures_'))
        
        if total_attempts >= 5 and blocked_count >= total_attempts * 0.8:
            self.update(domain, {'always_blocked': True})
            logger.warning(f"Domain {domain} marked as always_blocked due to consistent failures")
        
        # If consistently requires SSO, set sso_only flag
        sso_count = stats.get('failures_sso_required', 0)
        if total_attempts >= 5 and sso_count >= total_attempts * 0.8:
            self.update(domain, {'sso_only': True})
            logger.warning(f"Domain {domain} marked as sso_only due to consistent SSO requirements")
    
    def should_skip(self, domain: str):
        """
        Check if domain should be skipped
        
        Returns:
            Tuple of (should_skip, reason)
        """
        data = self.get(domain)
        
        if data.get('always_blocked'):
            return True, 'always_blocked'
        
        if data.get('sso_only'):
            return True, 'sso_only'
        
        return False, None
    
    def clear_cache(self):
        """Clear in-memory cache"""
        with self._cache_lock:
            self._cache.clear()
        logger.debug("Domain memory cache cleared")


# Global instance
_domain_memory = None


def get_domain_memory(db_path: str = "domain_memory.db", enable_cache: bool = True) -> DomainMemory:
    """Get global domain memory instance"""
    global _domain_memory
    if _domain_memory is None:
        _domain_memory = DomainMemory(db_path=db_path, enable_cache=enable_cache)
    return _domain_memory

