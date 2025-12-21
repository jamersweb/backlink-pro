"""
Field Role Matcher

Maps varied form fields to standard roles using heuristics
"""

import re
import logging
from typing import Dict, List, Optional, Tuple
from playwright.sync_api import Page, Locator, Frame

logger = logging.getLogger(__name__)


class FieldRoleMatcher:
    """Maps form fields to standard roles using heuristics"""
    
    # Role definitions with scoring patterns
    ROLE_PATTERNS = {
        'email': {
            'input_types': ['email'],
            'label_tokens': ['email', 'e-mail', 'mail', 'address'],
            'name_tokens': ['email', 'mail', 'e-mail', 'useremail', 'emailaddress'],
            'placeholder_tokens': ['email', 'mail', 'e-mail', 'your@email.com'],
            'autocomplete': ['email', 'username'],
            'nearby_text': ['email', 'mail', 'address'],
            'id_tokens': ['email', 'mail', 'e-mail'],
        },
        'username': {
            'input_types': ['text'],
            'label_tokens': ['username', 'user', 'login', 'account', 'user name'],
            'name_tokens': ['username', 'user', 'login', 'account', 'user_name', 'user-name'],
            'placeholder_tokens': ['username', 'user', 'login'],
            'autocomplete': ['username'],
            'nearby_text': ['username', 'user', 'login'],
            'id_tokens': ['username', 'user', 'login'],
        },
        'password': {
            'input_types': ['password'],
            'label_tokens': ['password', 'pass', 'pwd', 'passwd'],
            'name_tokens': ['password', 'pass', 'pwd', 'passwd', 'user_password'],
            'placeholder_tokens': ['password', 'pass', '••••'],
            'autocomplete': ['current-password', 'new-password'],
            'nearby_text': ['password', 'pass'],
            'id_tokens': ['password', 'pass', 'pwd'],
        },
        'comment': {
            'input_types': ['text'],
            'label_tokens': ['comment', 'message', 'reply', 'response', 'feedback', 'thoughts'],
            'name_tokens': ['comment', 'message', 'reply', 'response', 'content', 'body'],
            'placeholder_tokens': ['comment', 'message', 'reply', 'your comment', 'add a comment'],
            'autocomplete': [],
            'nearby_text': ['comment', 'message', 'reply', 'leave a comment'],
            'id_tokens': ['comment', 'message', 'reply'],
            'is_textarea': True,  # Comments are usually textareas
        },
        'website': {
            'input_types': ['text', 'url'],
            'label_tokens': ['website', 'url', 'web', 'site', 'homepage', 'home page'],
            'name_tokens': ['website', 'url', 'web', 'site', 'homepage', 'homepage_url'],
            'placeholder_tokens': ['website', 'url', 'http://', 'https://'],
            'autocomplete': ['url'],
            'nearby_text': ['website', 'url', 'homepage'],
            'id_tokens': ['website', 'url', 'web'],
        },
        'name': {
            'input_types': ['text'],
            'label_tokens': ['name', 'full name', 'fullname', 'display name', 'your name'],
            'name_tokens': ['name', 'fullname', 'full_name', 'display_name', 'full-name'],
            'placeholder_tokens': ['name', 'your name', 'full name'],
            'autocomplete': ['name', 'given-name', 'family-name'],
            'nearby_text': ['name', 'your name'],
            'id_tokens': ['name', 'fullname'],
        },
        'bio': {
            'input_types': ['text'],
            'label_tokens': ['bio', 'biography', 'about', 'about me', 'description', 'profile'],
            'name_tokens': ['bio', 'biography', 'about', 'about_me', 'description', 'profile'],
            'placeholder_tokens': ['bio', 'biography', 'about', 'tell us about yourself'],
            'autocomplete': [],
            'nearby_text': ['bio', 'biography', 'about'],
            'id_tokens': ['bio', 'biography', 'about'],
            'is_textarea': True,  # Bios are usually textareas
        },
    }
    
    # Minimum confidence threshold
    MIN_CONFIDENCE = 0.60
    
    @classmethod
    def match_fields(
        cls,
        page: Page,
        form_locator: Optional[Locator] = None,
        task_id: Optional[int] = None
    ) -> Dict[str, Tuple[Locator, float]]:
        """
        Match form fields to roles
        
        Args:
            page: Playwright Page object
            form_locator: Optional form locator (searches within form)
            task_id: Optional task ID for logging
        
        Returns:
            Dict mapping role -> (locator, confidence_score)
            Only includes roles with confidence >= MIN_CONFIDENCE
        """
        from core.telemetry import log_step
        
        log_prefix = 'field_role_matcher'
        
        if task_id:
            log_step(task_id, f'{log_prefix}_start')
        
        # Extract candidate fields
        candidates = cls._extract_candidates(page, form_locator, task_id, log_prefix)
        
        if not candidates:
            if task_id:
                log_step(task_id, f'{log_prefix}_no_candidates')
            return {}
        
        if task_id:
            log_step(task_id, f'{log_prefix}_candidates_extracted', {
                'count': len(candidates)
            })
        
        # Score each candidate for each role
        role_mappings = {}
        
        for role, role_patterns in cls.ROLE_PATTERNS.items():
            best_candidate = None
            best_score = 0.0
            
            for candidate in candidates:
                score = cls._score_candidate(candidate, role, role_patterns, page, form_locator)
                
                if score > best_score:
                    best_score = score
                    best_candidate = candidate
            
            # Only include if confidence is high enough
            if best_candidate and best_score >= cls.MIN_CONFIDENCE:
                role_mappings[role] = (best_candidate['locator'], best_score)
                
                if task_id:
                    log_step(task_id, f'{log_prefix}_role_matched', {
                        'role': role,
                        'confidence': best_score,
                        'why': best_candidate.get('why', ''),
                    })
            else:
                if task_id:
                    log_step(task_id, f'{log_prefix}_role_no_match', {
                        'role': role,
                        'best_score': best_score,
                        'threshold': cls.MIN_CONFIDENCE
                    })
        
        if task_id:
            log_step(task_id, f'{log_prefix}_complete', {
                'roles_matched': len(role_mappings),
                'roles': list(role_mappings.keys())
            })
        
        return role_mappings
    
    @classmethod
    def _extract_candidates(
        cls,
        page: Page,
        form_locator: Optional[Locator],
        task_id: Optional[int],
        log_prefix: str
    ) -> List[Dict]:
        """Extract candidate form fields"""
        candidates = []
        
        try:
            # Determine search context
            search_context = form_locator if form_locator else page
            
            # Find all input, textarea, and select elements
            inputs = search_context.locator('input, textarea, select')
            count = inputs.count()
            
            for i in range(count):
                try:
                    field = inputs.nth(i)
                    
                    # Get field attributes
                    field_type = field.get_attribute('type') or ''
                    tag_name = field.evaluate('el => el.tagName.toLowerCase()') if field.count() > 0 else 'input'
                    name = field.get_attribute('name') or ''
                    field_id = field.get_attribute('id') or ''
                    placeholder = field.get_attribute('placeholder') or ''
                    autocomplete = field.get_attribute('autocomplete') or ''
                    
                    # Skip hidden fields
                    try:
                        if not field.is_visible(timeout=500):
                            continue
                    except:
                        continue
                    
                    candidates.append({
                        'locator': field,
                        'type': field_type,
                        'tag_name': tag_name,
                        'name': name,
                        'id': field_id,
                        'placeholder': placeholder,
                        'autocomplete': autocomplete,
                        'index': i,
                    })
                except Exception as e:
                    logger.debug(f"Error extracting candidate {i}: {e}")
                    continue
        
        except Exception as e:
            logger.error(f"Error extracting candidates: {e}")
        
        return candidates
    
    @classmethod
    def _score_candidate(
        cls,
        candidate: Dict,
        role: str,
        role_patterns: Dict,
        page: Page,
        form_locator: Optional[Locator]
    ) -> float:
        """Score a candidate for a specific role"""
        score = 0.0
        reasons = []
        
        # Heuristic 1: Input type (high weight)
        if candidate['type'] in role_patterns.get('input_types', []):
            score += 0.30
            reasons.append(f"input type matches: {candidate['type']}")
        
        # Special case: password type is very strong indicator
        if role == 'password' and candidate['type'] == 'password':
            score += 0.40
            reasons.append("password input type")
        
        # Special case: email type is very strong indicator
        if role == 'email' and candidate['type'] == 'email':
            score += 0.40
            reasons.append("email input type")
        
        # Heuristic 2: Tag name (textarea for comment/bio)
        if role_patterns.get('is_textarea', False) and candidate['tag_name'] == 'textarea':
            score += 0.25
            reasons.append("is textarea")
        elif not role_patterns.get('is_textarea', False) and candidate['tag_name'] == 'textarea':
            score -= 0.10  # Penalty if role shouldn't be textarea
        
        # Heuristic 3: Name attribute tokens
        name_lower = candidate['name'].lower()
        for token in role_patterns.get('name_tokens', []):
            if token.lower() in name_lower:
                score += 0.20
                reasons.append(f"name contains '{token}'")
                break
        
        # Heuristic 4: ID attribute tokens
        id_lower = candidate['id'].lower()
        for token in role_patterns.get('id_tokens', []):
            if token.lower() in id_lower:
                score += 0.15
                reasons.append(f"id contains '{token}'")
                break
        
        # Heuristic 5: Placeholder tokens
        placeholder_lower = candidate['placeholder'].lower()
        for token in role_patterns.get('placeholder_tokens', []):
            if token.lower() in placeholder_lower:
                score += 0.15
                reasons.append(f"placeholder contains '{token}'")
                break
        
        # Heuristic 6: Autocomplete hints
        autocomplete_lower = candidate['autocomplete'].lower()
        for hint in role_patterns.get('autocomplete', []):
            if hint.lower() in autocomplete_lower:
                score += 0.20
                reasons.append(f"autocomplete='{hint}'")
                break
        
        # Heuristic 7: Label/placeholder tokens (try to find label)
        try:
            label_text = cls._find_label_text(candidate['locator'], page, form_locator)
            if label_text:
                label_lower = label_text.lower()
                for token in role_patterns.get('label_tokens', []):
                    if token.lower() in label_lower:
                        score += 0.20
                        reasons.append(f"label contains '{token}'")
                        break
        except:
            pass
        
        # Heuristic 8: Nearby text
        try:
            nearby_text = cls._find_nearby_text(candidate['locator'], page, form_locator)
            if nearby_text:
                nearby_lower = nearby_text.lower()
                for token in role_patterns.get('nearby_text', []):
                    if token.lower() in nearby_lower:
                        score += 0.10
                        reasons.append(f"nearby text contains '{token}'")
                        break
        except:
            pass
        
        # Cap score at 1.0
        score = min(score, 1.0)
        
        # Store why for logging
        candidate['why'] = '; '.join(reasons) if reasons else 'no matches'
        
        return score
    
    @classmethod
    def _find_label_text(
        cls,
        field_locator: Locator,
        page: Page,
        form_locator: Optional[Locator]
    ) -> Optional[str]:
        """Find associated label text"""
        try:
            # Try for attribute
            field_id = field_locator.get_attribute('id')
            if field_id:
                label = page.locator(f'label[for="{field_id}"]')
                if label.count() > 0:
                    return label.inner_text()
            
            # Try parent label
            parent_label = field_locator.locator('xpath=ancestor::label[1]')
            if parent_label.count() > 0:
                return parent_label.inner_text()
            
            # Try preceding label
            preceding_label = field_locator.locator('xpath=preceding::label[1]')
            if preceding_label.count() > 0:
                return preceding_label.inner_text()
        except:
            pass
        
        return None
    
    @classmethod
    def _find_nearby_text(
        cls,
        field_locator: Locator,
        page: Page,
        form_locator: Optional[Locator]
    ) -> Optional[str]:
        """Find nearby text (sibling, parent, etc.)"""
        try:
            # Try parent text
            parent = field_locator.locator('xpath=parent::*')
            if parent.count() > 0:
                parent_text = parent.inner_text()
                if parent_text:
                    return parent_text[:100]  # Limit length
            
            # Try preceding sibling
            preceding = field_locator.locator('xpath=preceding-sibling::*[1]')
            if preceding.count() > 0:
                preceding_text = preceding.inner_text()
                if preceding_text:
                    return preceding_text[:100]
        except:
            pass
        
        return None
    
    @classmethod
    def get_field(
        cls,
        page: Page,
        role: str,
        form_locator: Optional[Locator] = None,
        task_id: Optional[int] = None
    ) -> Optional[Locator]:
        """
        Convenience method: Get field for a specific role
        
        Args:
            page: Playwright Page object
            role: Role to find (email, username, password, etc.)
            form_locator: Optional form locator
            task_id: Optional task ID for logging
        
        Returns:
            Locator for the field, or None if not found
        """
        mappings = cls.match_fields(page, form_locator, task_id)
        
        if role in mappings:
            locator, confidence = mappings[role]
            return locator
        
        return None
    
    @classmethod
    def get_fields(
        cls,
        page: Page,
        roles: List[str],
        form_locator: Optional[Locator] = None,
        task_id: Optional[int] = None
    ) -> Dict[str, Optional[Locator]]:
        """
        Convenience method: Get multiple fields
        
        Args:
            page: Playwright Page object
            roles: List of roles to find
            form_locator: Optional form locator
            task_id: Optional task ID for logging
        
        Returns:
            Dict mapping role -> locator (or None if not found)
        """
        mappings = cls.match_fields(page, form_locator, task_id)
        
        result = {}
        for role in roles:
            if role in mappings:
                locator, confidence = mappings[role]
                result[role] = locator
            else:
                result[role] = None
        
        return result

