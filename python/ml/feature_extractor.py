"""
Feature Extractor

Extracts features from URLs and HTML for ML training
"""

import re
import csv
import logging
import time
import hashlib
from typing import Dict, List, Optional, Any
from urllib.parse import urlparse, urljoin
from pathlib import Path
import json

import requests
from bs4 import BeautifulSoup
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry

logger = logging.getLogger(__name__)


class FeatureExtractor:
    """Extracts features from URLs and HTML"""
    
    def __init__(self, cache_dir: str = "ml/cache", timeout: int = 10):
        """
        Initialize feature extractor
        
        Args:
            cache_dir: Directory for caching HTML responses
            timeout: Request timeout in seconds
        """
        self.cache_dir = Path(cache_dir)
        self.cache_dir.mkdir(parents=True, exist_ok=True)
        self.timeout = timeout
        
        # Setup requests session with retries
        self.session = requests.Session()
        retry_strategy = Retry(
            total=3,
            backoff_factor=1,
            status_forcelist=[429, 500, 502, 503, 504]
        )
        adapter = HTTPAdapter(max_retries=retry_strategy)
        self.session.mount("http://", adapter)
        self.session.mount("https://", adapter)
        
        # User agent to avoid blocking
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        })
    
    def extract_features(self, url: str, url_type: str, pa: Optional[int] = None, 
                        da: Optional[int] = None, status: Optional[str] = None) -> Dict:
        """
        Extract features from URL and HTML
        
        Args:
            url: URL to extract features from
            url_type: Type (comment, profile, forum, guest)
            pa: Page Authority (optional)
            da: Domain Authority (optional)
            status: Status (optional)
        
        Returns:
            Dict with extracted features
        """
        features = {
            'url': url,
            'type': url_type,
            'pa': pa,
            'da': da,
            'status': status,
        }
        
        try:
            # Parse URL
            parsed = urlparse(url)
            domain = parsed.netloc or parsed.path.split('/')[0] if parsed.path else ''
            
            # Basic URL features
            features['domain'] = domain
            features['tld'] = self._extract_tld(domain)
            features['url_path_depth'] = len([p for p in parsed.path.split('/') if p])
            features['https_enabled'] = parsed.scheme == 'https'
            
            # Get HTML and extract features
            html_content = self._get_html(url)
            if html_content:
                soup = BeautifulSoup(html_content, 'html.parser')
                
                # Platform detection
                features['platform_guess'] = self._detect_platform(url, soup)
                features['site_type'] = self._detect_site_type(url, soup)
                
                # Feature detection
                features['comment_supported'] = self._detect_comment_support(soup)
                features['profile_supported'] = self._detect_profile_support(soup)
                features['forum_supported'] = self._detect_forum_support(soup)
                features['guest_supported'] = self._detect_guest_support(soup)
                features['requires_login'] = self._detect_login_requirement(soup)
                features['registration_detected'] = self._detect_registration(soup)
            else:
                # Defaults if HTML not available
                features['platform_guess'] = 'unknown'
                features['site_type'] = 'unknown'
                features['comment_supported'] = False
                features['profile_supported'] = False
                features['forum_supported'] = False
                features['guest_supported'] = False
                features['requires_login'] = False
                features['registration_detected'] = False
            
        except Exception as e:
            logger.warning(f"Error extracting features from {url}: {e}")
            # Set defaults on error
            features.update({
                'platform_guess': 'unknown',
                'site_type': 'unknown',
                'comment_supported': False,
                'profile_supported': False,
                'forum_supported': False,
                'guest_supported': False,
                'requires_login': False,
                'registration_detected': False,
            })
        
        return features
    
    def _extract_tld(self, domain: str) -> str:
        """Extract top-level domain"""
        if not domain:
            return 'unknown'
        
        parts = domain.split('.')
        if len(parts) >= 2:
            return '.'.join(parts[-2:])  # e.g., example.com
        return domain
    
    def _get_html(self, url: str) -> Optional[str]:
        """Get HTML content with caching"""
        # Check cache
        cache_key = hashlib.md5(url.encode()).hexdigest()
        cache_file = self.cache_dir / f"{cache_key}.html"
        
        if cache_file.exists():
            try:
                with open(cache_file, 'r', encoding='utf-8') as f:
                    return f.read()
            except:
                pass
        
        # Fetch HTML
        try:
            response = self.session.get(url, timeout=self.timeout, allow_redirects=True)
            response.raise_for_status()
            html = response.text
            
            # Cache HTML
            try:
                with open(cache_file, 'w', encoding='utf-8') as f:
                    f.write(html)
            except:
                pass
            
            return html
        except Exception as e:
            logger.debug(f"Failed to fetch HTML from {url}: {e}")
            return None
    
    def _detect_platform(self, url: str, soup: BeautifulSoup) -> str:
        """Detect platform (wordpress/xenforo/disqus/custom)"""
        url_lower = url.lower()
        html_lower = str(soup).lower()
        
        # WordPress detection
        if 'wp-content' in html_lower or 'wordpress' in html_lower or '/wp-admin' in url_lower:
            return 'wordpress'
        
        # XenForo detection
        if 'xenforo' in html_lower or 'xf-' in html_lower or '/forums/' in url_lower:
            return 'xenforo'
        
        # Disqus detection
        if 'disqus' in html_lower or 'disqus.com' in html_lower:
            return 'disqus'
        
        # Other common platforms
        if 'vbulletin' in html_lower:
            return 'vbulletin'
        if 'phpbb' in html_lower:
            return 'phpbb'
        if 'drupal' in html_lower:
            return 'drupal'
        if 'joomla' in html_lower:
            return 'joomla'
        
        return 'custom'
    
    def _detect_site_type(self, url: str, soup: BeautifulSoup) -> str:
        """Detect site type (blog/forum/cms)"""
        url_lower = url.lower()
        html_lower = str(soup).lower()
        
        # Forum detection
        if 'forum' in url_lower or 'forums' in url_lower or 'forum' in html_lower:
            return 'forum'
        
        # Blog detection
        if 'blog' in url_lower or '/blog/' in url_lower or 'blog' in html_lower:
            return 'blog'
        
        # CMS detection
        if 'article' in html_lower or 'post' in html_lower or 'content' in html_lower:
            return 'cms'
        
        return 'unknown'
    
    def _detect_comment_support(self, soup: BeautifulSoup) -> bool:
        """Detect if comment support exists"""
        html_lower = str(soup).lower()
        
        # Check for comment-related elements
        comment_indicators = [
            'comment',
            'reply',
            'discuss',
            'leave a comment',
            'post comment',
            'add comment',
            'comments',
        ]
        
        for indicator in comment_indicators:
            if indicator in html_lower:
                # Check for form elements
                forms = soup.find_all('form')
                for form in forms:
                    form_text = form.get_text().lower()
                    if indicator in form_text:
                        # Check for textarea
                        if form.find('textarea'):
                            return True
        
        # Check for Disqus
        if soup.find('div', id='disqus_thread') or soup.find('div', class_='disqus'):
            return True
        
        return False
    
    def _detect_profile_support(self, soup: BeautifulSoup) -> bool:
        """Detect if profile/registration support exists"""
        html_lower = str(soup).lower()
        
        # Check for registration/signup elements
        profile_indicators = [
            'register',
            'sign up',
            'signup',
            'create account',
            'join',
            'membership',
            'profile',
        ]
        
        for indicator in profile_indicators:
            if indicator in html_lower:
                # Check for form elements
                forms = soup.find_all('form')
                for form in forms:
                    form_text = form.get_text().lower()
                    if indicator in form_text:
                        # Check for registration form fields
                        if form.find('input', {'type': 'email'}) or form.find('input', {'type': 'password'}):
                            return True
        
        return False
    
    def _detect_forum_support(self, soup: BeautifulSoup) -> bool:
        """Detect if forum support exists"""
        html_lower = str(soup).lower()
        
        # Check for forum-related elements
        forum_indicators = [
            'forum',
            'thread',
            'post reply',
            'new thread',
            'discussion',
        ]
        
        for indicator in forum_indicators:
            if indicator in html_lower:
                forms = soup.find_all('form')
                for form in forms:
                    form_text = form.get_text().lower()
                    if indicator in form_text:
                        if form.find('textarea'):
                            return True
        
        return False
    
    def _detect_guest_support(self, soup: BeautifulSoup) -> bool:
        """Detect if guest post support exists"""
        html_lower = str(soup).lower()
        
        # Check for guest post elements
        guest_indicators = [
            'guest post',
            'write for us',
            'submit article',
            'contribute',
            'guest author',
        ]
        
        for indicator in guest_indicators:
            if indicator in html_lower:
                forms = soup.find_all('form')
                for form in forms:
                    form_text = form.get_text().lower()
                    if indicator in form_text:
                        return True
        
        return False
    
    def _detect_login_requirement(self, soup: BeautifulSoup) -> bool:
        """Detect if login is required"""
        html_lower = str(soup).lower()
        
        # Check for login-related text
        login_indicators = [
            'login to continue',
            'sign in to view',
            'authentication required',
            'please log in',
        ]
        
        for indicator in login_indicators:
            if indicator in html_lower:
                return True
        
        # Check for login forms
        forms = soup.find_all('form')
        for form in forms:
            form_text = form.get_text().lower()
            if 'login' in form_text or 'sign in' in form_text:
                if form.find('input', {'type': 'password'}):
                    return True
        
        return False
    
    def _detect_registration(self, soup: BeautifulSoup) -> bool:
        """Detect if registration is available"""
        html_lower = str(soup).lower()
        
        # Check for registration links/forms
        registration_indicators = [
            'register',
            'sign up',
            'create account',
            'join now',
        ]
        
        for indicator in registration_indicators:
            if indicator in html_lower:
                # Check for links
                links = soup.find_all('a')
                for link in links:
                    link_text = link.get_text().lower()
                    if indicator in link_text:
                        return True
                
                # Check for forms
                forms = soup.find_all('form')
                for form in forms:
                    form_text = form.get_text().lower()
                    if indicator in form_text:
                        return True
        
        return False
    
    def process_csv(self, input_csv: str, output_csv: str, limit: Optional[int] = None):
        """
        Process CSV file and extract features
        
        Args:
            input_csv: Input CSV file path
            output_csv: Output CSV file path
            limit: Optional limit on number of rows to process
        """
        logger.info(f"Processing CSV: {input_csv} -> {output_csv}")
        
        rows_processed = 0
        rows_total = 0
        
        with open(input_csv, 'r', encoding='utf-8') as infile, \
             open(output_csv, 'w', encoding='utf-8', newline='') as outfile:
            
            reader = csv.DictReader(infile)
            
            # Get fieldnames
            fieldnames = ['url', 'type', 'pa', 'da', 'status', 'domain', 'tld', 
                         'url_path_depth', 'https_enabled', 'platform_guess', 'site_type',
                         'comment_supported', 'profile_supported', 'forum_supported', 
                         'guest_supported', 'requires_login', 'registration_detected']
            
            writer = csv.DictWriter(outfile, fieldnames=fieldnames)
            writer.writeheader()
            
            for row in reader:
                rows_total += 1
                
                if limit and rows_processed >= limit:
                    break
                
                url = row.get('URL') or row.get('url', '')
                url_type = row.get('TYPE') or row.get('type', '')
                pa = row.get('PA') or row.get('pa', '')
                da = row.get('DA') or row.get('da', '')
                status = row.get('STATUS') or row.get('status', '')
                
                # Convert PA/DA to int if possible
                try:
                    pa = int(pa) if pa else None
                except:
                    pa = None
                
                try:
                    da = int(da) if da else None
                except:
                    da = None
                
                if not url:
                    continue
                
                # Extract features
                features = self.extract_features(url, url_type, pa, da, status)
                
                # Write to output
                writer.writerow(features)
                rows_processed += 1
                
                if rows_processed % 10 == 0:
                    logger.info(f"Processed {rows_processed} rows...")
                
                # Small delay to avoid rate limiting
                time.sleep(0.5)
        
        logger.info(f"Feature extraction complete: {rows_processed}/{rows_total} rows processed")


if __name__ == '__main__':
    import argparse
    
    parser = argparse.ArgumentParser(description='Extract features from CSV')
    parser.add_argument('input_csv', help='Input CSV file')
    parser.add_argument('output_csv', help='Output CSV file')
    parser.add_argument('--limit', type=int, help='Limit number of rows to process')
    
    args = parser.parse_args()
    
    extractor = FeatureExtractor()
    extractor.process_csv(args.input_csv, args.output_csv, limit=args.limit)

