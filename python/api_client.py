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

    def _request(self, method: str, endpoint: str, retry_on_rate_limit: bool = False, **kwargs) -> Optional[Dict]:
        """Make HTTP request to API with rate limit handling

        Note: retry_on_rate_limit defaults to False to avoid making more requests
        when rate limited. The caller should handle rate limits by waiting.
        """
        url = urljoin(self.base_url, endpoint)
        max_retries = 3
        retry_delay = 5  # Start with 5 seconds

        for attempt in range(max_retries):
            try:
                response = self.session.request(method, url, **kwargs)

                # Handle rate limiting (429) - don't retry by default
                if response.status_code == 429:
                    # Parse retry_after from response
                    retry_after = 60  # Default
                    error_msg = 'Rate limit exceeded'
                    try:
                        error_data = response.json()
                        retry_after = error_data.get('retry_after', retry_after)
                        error_msg = error_data.get('message', error_msg)
                    except:
                        # Try to get from Retry-After header
                        retry_after_header = response.headers.get('Retry-After')
                        if retry_after_header:
                            try:
                                retry_after = int(retry_after_header)
                            except:
                                pass

                    # Only retry if explicitly enabled AND it's not the last attempt
                    if retry_on_rate_limit and attempt < max_retries - 1:
                        # Wait before retrying (don't add exponential backoff for rate limits)
                        logger.warning(
                            f"Rate limit exceeded for {method} {endpoint}. "
                            f"Waiting {retry_after} seconds before retry {attempt + 1}/{max_retries}"
                        )
                        import time
                        time.sleep(retry_after)
                        continue  # Retry the request
                    else:
                        # Don't retry - let the caller handle it
                        raise requests.exceptions.HTTPError(
                            f"429 Client Error: Rate limit exceeded. {error_msg} "
                            f"(Retry after {retry_after} seconds)",
                            response=response
                        )

                # For other errors, raise immediately
                response.raise_for_status()
                return response.json() if response.content else None

            except requests.exceptions.HTTPError as e:
                # Re-raise HTTP errors (including 429 if retries exhausted)
                if e.response and e.response.status_code == 429:
                    raise
                logger.error(f"API request failed: {method} {endpoint} - {e}")
                raise
            except requests.exceptions.RequestException as e:
                logger.error(f"API request failed: {method} {endpoint} - {e}")
                raise

        # Should never reach here, but just in case
        raise requests.exceptions.RequestException(f"Failed to complete request after {max_retries} attempts")

    def get_pending_tasks(self, limit: int = 10, task_type: Optional[str] = None) -> List[Dict]:
        """Get pending tasks from Laravel"""
        params = {'limit': limit}
        if task_type:
            params['type'] = task_type

        # Don't retry on rate limit - let the worker handle it by waiting
        response = self._request('GET', '/api/tasks/pending', params=params, retry_on_rate_limit=False)
        return response.get('tasks', []) if response else []
    
    def get_task(self, task_id: int) -> Optional[Dict]:
        """Get a specific task by ID from Laravel"""
        try:
            response = self._request('GET', f'/api/tasks/{task_id}', retry_on_rate_limit=False)
            return response
        except requests.exceptions.HTTPError as e:
            if e.response is not None and e.response.status_code == 404:
                return None
            raise

    def lock_task(self, task_id: int, worker_id: str) -> Dict:
        """Lock a task for processing"""
        try:
            response = self._request('POST', f'/api/tasks/{task_id}/lock', json={'worker_id': worker_id})
            return response or {}
        except requests.exceptions.HTTPError as e:
            # A 409 means another worker locked the task first; treat as a soft failure
            if e.response is not None and e.response.status_code == 409:
                try:
                    error_payload = e.response.json()
                except Exception:
                    error_payload = {}

                return {
                    'error': error_payload.get('error', 'Task is already locked'),
                    'status_code': 409,
                }

            # Bubble up everything else
            raise

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

    def get_opportunities_for_campaign(self, campaign_id: int, count: int = 1,
                                      task_type: Optional[str] = None,
                                      site_type: Optional[str] = None) -> List[Dict]:
        """Get opportunities for a campaign based on category, plan limits, and daily limits"""
        params = {'count': count}
        if task_type:
            params['task_type'] = task_type
        if site_type:
            params['site_type'] = site_type

        response = self._request('GET', f'/api/opportunities/for-campaign/{campaign_id}', params=params)
        if response and response.get('success'):
            return response.get('opportunities', [])
        return []

    def create_backlink(self, campaign_id: int, url: str, task_type: str,
                       keyword: Optional[str] = None, anchor_text: Optional[str] = None,
                       status: str = 'submitted', site_account_id: Optional[int] = None,
                       backlink_id: Optional[int] = None,
                       error_message: Optional[str] = None) -> Dict:
        """
        Create a backlink opportunity (campaign-specific)
        
        Args:
            campaign_id: Campaign ID
            url: Actual backlink URL (may differ from store URL)
            task_type: Type of backlink (comment, profile, forum, guestposting)
            keyword: Optional keyword
            anchor_text: Optional anchor text
            status: Status (pending, submitted, verified, error)
            site_account_id: Optional site account ID
            backlink_id: Reference to backlink from the store (required)
            error_message: Optional error message
        """
        if not backlink_id:
            raise ValueError("backlink_id is required to create an opportunity")
        
        data = {
            'campaign_id': campaign_id,
            'backlink_id': backlink_id,
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

    def update_backlink(self, opportunity_id: int, status: Optional[str] = None,
                       error_message: Optional[str] = None) -> Dict:
        """
        Update a backlink opportunity
        
        Args:
            opportunity_id: Opportunity ID (not backlink store ID)
            status: Optional status update
            error_message: Optional error message
        """
        data = {}
        if status:
            data['status'] = status
        if error_message:
            data['error_message'] = error_message

        response = self._request('PUT', f'/api/backlinks/{opportunity_id}', json=data)
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

    def update_site_account(self, site_account_id: int, data: Optional[Dict] = None,
                          status: Optional[str] = None, verification_link: Optional[str] = None) -> Dict:
        """Update a site account"""
        if data is None:
            data = {}
        if status:
            data['status'] = status
        if verification_link:
            data['verification_link'] = verification_link

        response = self._request('PUT', f'/api/site-accounts/{site_account_id}', json=data)
        return response or {}

    def get_proxies(self, country: Optional[str] = None, prefer_country: bool = True) -> List[Dict]:
        """Get proxy list with smart selection"""
        params = {}
        if country:
            params['country'] = country
        if prefer_country:
            params['prefer_country'] = '1'

        response = self._request('GET', '/api/proxies', params=params)
        return response.get('proxies', []) if response else []

    def generate_content(self, content_type: str, data: Dict, tone: str = 'professional') -> Optional[str]:
        """Generate content using LLM"""
        try:
            payload = {
                'type': content_type,
                'data': {**data, 'tone': tone},
            }
            response = self._request('POST', '/api/llm/generate', json=payload)

            if response and response.get('success'):
                if content_type == 'anchor_text':
                    return response.get('variations', [])
                return response.get('content')
            return None
        except Exception as e:
            logger.error(f"Failed to generate content: {e}")
            return None

    def solve_captcha(self, captcha_type: str, captcha_data: Dict) -> Optional[Dict]:
        """Solve captcha via API"""
        try:
            payload = {
                'captcha_type': captcha_type,
                'data': captcha_data,
            }
            response = self._request('POST', '/api/captcha/solve', json=payload)

            if response and response.get('success'):
                return response
            return None
        except Exception as e:
            logger.error(f"Failed to solve captcha: {e}")
            return None

    def get_historical_backlink_data(self, limit: int = 1000, min_date: Optional[str] = None) -> List[Dict]:
        """
        Get historical backlink data for ML training
        
        Args:
            limit: Maximum number of records to fetch
            min_date: Optional minimum date filter (ISO format)
        
        Returns:
            List of historical records with success/failure outcomes
        """
        params = {'limit': limit}
        if min_date:
            params['min_date'] = min_date
        
        try:
            response = self._request('GET', '/api/ml/historical-data', params=params)
            if response and response.get('success'):
                return response.get('data', [])
            return []
        except Exception as e:
            logger.error(f"Failed to fetch historical data: {e}")
            return []

