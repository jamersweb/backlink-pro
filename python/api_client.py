"""
API Client for communicating with Laravel backend
"""
import requests
import logging
from typing import Optional, Dict, List, Any
from urllib.parse import urljoin

logger = logging.getLogger(__name__)


class LaravelAPIClient:
    """Client for Laravel API communication"""
    
    def __init__(self, base_url: str, api_token: str):
        self.base_url = base_url.rstrip('/')
        self.api_token = api_token
        self.session = requests.Session()
        self.session.headers.update({
            'X-API-Token': api_token,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        })
    
    def _request(self, method: str, endpoint: str, **kwargs) -> Optional[Dict]:
        """Make HTTP request to API"""
        url = urljoin(self.base_url, endpoint)
        
        try:
            response = self.session.request(method, url, **kwargs)
            response.raise_for_status()
            return response.json() if response.content else None
        except requests.exceptions.RequestException as e:
            logger.error(f"API request failed: {method} {endpoint} - {e}")
            raise
    
    def get_pending_tasks(self, limit: int = 10, task_type: Optional[str] = None) -> List[Dict]:
        """Get pending tasks from Laravel"""
        params = {'limit': limit}
        if task_type:
            params['type'] = task_type
        
        response = self._request('GET', '/api/tasks/pending', params=params)
        return response.get('tasks', []) if response else []
    
    def lock_task(self, task_id: int, worker_id: str) -> Dict:
        """Lock a task for processing"""
        response = self._request('POST', f'/api/tasks/{task_id}/lock', json={'worker_id': worker_id})
        return response or {}
    
    def unlock_task(self, task_id: int) -> Dict:
        """Unlock a task"""
        response = self._request('POST', f'/api/tasks/{task_id}/unlock')
        return response or {}
    
    def update_task_status(self, task_id: int, status: str, result: Optional[Dict] = None, 
                          error_message: Optional[str] = None) -> Dict:
        """Update task status"""
        data = {'status': status}
        if result:
            data['result'] = result
        if error_message:
            data['error_message'] = error_message
        
        response = self._request('PUT', f'/api/tasks/{task_id}/status', json=data)
        return response or {}
    
    def create_backlink(self, campaign_id: int, url: str, task_type: str, 
                       keyword: Optional[str] = None, anchor_text: Optional[str] = None,
                       status: str = 'submitted', site_account_id: Optional[int] = None,
                       error_message: Optional[str] = None) -> Dict:
        """Create a backlink"""
        data = {
            'campaign_id': campaign_id,
            'url': url,
            'type': task_type,
            'status': status,
        }
        if keyword:
            data['keyword'] = keyword
        if anchor_text:
            data['anchor_text'] = anchor_text
        if site_account_id:
            data['site_account_id'] = site_account_id
        if error_message:
            data['error_message'] = error_message
        
        response = self._request('POST', '/api/backlinks', json=data)
        return response or {}
    
    def update_backlink(self, backlink_id: int, status: Optional[str] = None,
                       error_message: Optional[str] = None) -> Dict:
        """Update a backlink"""
        data = {}
        if status:
            data['status'] = status
        if error_message:
            data['error_message'] = error_message
        
        response = self._request('PUT', f'/api/backlinks/{backlink_id}', json=data)
        return response or {}
    
    def get_campaign(self, campaign_id: int) -> Dict:
        """Get campaign details"""
        response = self._request('GET', f'/api/campaigns/{campaign_id}')
        return response or {}
    
    def create_site_account(self, campaign_id: int, site_domain: str, login_email: str,
                           username: Optional[str] = None, password: Optional[str] = None,
                           status: str = 'created') -> Dict:
        """Create a site account"""
        data = {
            'campaign_id': campaign_id,
            'site_domain': site_domain,
            'login_email': login_email,
            'status': status,
        }
        if username:
            data['username'] = username
        if password:
            data['password'] = password
        
        response = self._request('POST', '/api/site-accounts', json=data)
        return response or {}
    
    def update_site_account(self, site_account_id: int, status: Optional[str] = None,
                          verification_link: Optional[str] = None) -> Dict:
        """Update a site account"""
        data = {}
        if status:
            data['status'] = status
        if verification_link:
            data['verification_link'] = verification_link
        
        response = self._request('PUT', f'/api/site-accounts/{site_account_id}', json=data)
        return response or {}
    
    def get_proxies(self, country: Optional[str] = None) -> List[Dict]:
        """Get proxy list"""
        params = {}
        if country:
            params['country'] = country
        
        response = self._request('GET', '/api/proxies', params=params)
        return response.get('proxies', []) if response else []

