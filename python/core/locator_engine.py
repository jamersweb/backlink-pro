"""
Self-Healing Locator Engine

Generates ranked locator candidates with confidence scores
Falls back through multiple strategies before failing
"""

import logging
import re
from typing import Dict, List, Optional, Tuple, Any, Callable
from playwright.sync_api import Page, Locator, FrameLocator
from core.iframe_router import IframeRouter
from core.budget_guard import BudgetGuard
from core.domain_memory import get_domain_memory

logger = logging.getLogger(__name__)


class LocatorCandidate:
    """Represents a locator candidate with metadata"""
    
    def __init__(self, locator: Locator, confidence: float, strategy: str, 
                 why: str, frame_context: Optional[Any] = None):
        """
        Initialize locator candidate
        
        Args:
            locator: Playwright Locator
            confidence: Confidence score (0.0-1.0)
            strategy: Strategy name
            why: Explanation of why this candidate was chosen
            frame_context: Frame context if found in iframe
        """
        self.locator = locator
        self.confidence = confidence
        self.strategy = strategy
        self.why = why
        self.frame_context = frame_context
    
    def to_dict(self) -> Dict:
        """Convert to dictionary"""
        return {
            'strategy': self.strategy,
            'confidence': self.confidence,
            'why': self.why,
            'frame_context': self.frame_context is not None,
        }


class LocatorEngine:
    """Self-healing locator engine with ranked strategies"""
    
    # Role mappings for common elements
    ROLE_MAPPINGS = {
        'button': 'button',
        'submit': 'button',
        'link': 'link',
        'input': 'textbox',
        'textarea': 'textbox',
        'form': 'form',
        'field': 'textbox',
        'text': 'textbox',
        'email': 'textbox',
        'password': 'textbox',
        'search': 'searchbox',
        'checkbox': 'checkbox',
        'radio': 'radio',
    }
    
    @classmethod
    def find(
        cls,
        page: Page,
        target_role: str,
        keywords: List[str],
        context: Optional[Dict] = None,
        task_id: Optional[int] = None,
        top_k: int = 5
    ) -> Tuple[Optional[Locator], Optional[LocatorCandidate], List[LocatorCandidate]]:
        """
        Find element using ranked strategies
        
        Args:
            page: Playwright Page object
            target_role: Target role (button, input, form, textarea, etc.)
            keywords: List of keywords to match (name, label, placeholder, etc.)
            context: Optional context (form_locator, parent_locator, etc.)
            task_id: Optional task ID for logging
            top_k: Number of top candidates to try
        
        Returns:
            Tuple of (found_locator, winning_candidate, all_candidates)
        """
        from core.telemetry import log_step
        
        description = f"{target_role}_{'_'.join(keywords[:2])}"
        log_prefix = f"locator_engine_{description}"
        
        if task_id:
            log_step(task_id, f'{log_prefix}_start', {
                'target_role': target_role,
                'keywords': keywords,
                'context': context or {}
            })
        
        # Generate candidates
        candidates = cls._generate_candidates(page, target_role, keywords, context, task_id, log_prefix)
        
        if not candidates:
            if task_id:
                log_step(task_id, f'{log_prefix}_no_candidates')
            return None, None, []
        
        # Sort by confidence (highest first)
        candidates.sort(key=lambda c: c.confidence, reverse=True)
        
        if task_id:
            log_step(task_id, f'{log_prefix}_candidates_generated', {
                'count': len(candidates),
                'top_confidence': candidates[0].confidence if candidates else 0.0
            })
        
        # Try top-K candidates
        top_candidates = candidates[:top_k]
        
        # Check domain memory for best strategy per role
        domain_memory = get_domain_memory()
        domain = None
        best_strategy = None
        if context and context.get('domain'):
            domain = context['domain']
            domain_data = domain_memory.get(domain)
            best_strategies = domain_data.get('best_locator_strategy', {})
            best_strategy = best_strategies.get(target_role)
            
            # Reorder candidates to prioritize best strategy
            if best_strategy:
                strategy_name = best_strategy if isinstance(best_strategy, str) else best_strategy.get('strategy')
                if strategy_name:
                    # Move matching strategy to front
                    for i, c in enumerate(top_candidates):
                        if c.strategy == strategy_name:
                            top_candidates.insert(0, top_candidates.pop(i))
                            break
        
        for idx, candidate in enumerate(top_candidates):
            try:
                # Check budget
                try:
                    BudgetGuard.check_locator_candidates(task_id)
                except Exception as e:
                    if task_id:
                        log_step(task_id, f'{log_prefix}_budget_exceeded', {'error': str(e)})
                    break
                
                if task_id:
                    log_step(task_id, f'{log_prefix}_try_candidate', {
                        'index': idx,
                        'strategy': candidate.strategy,
                        'confidence': candidate.confidence,
                        'why': candidate.why
                    })
                
                # Check if locator exists and is visible
                if candidate.locator.count() > 0:
                    try:
                        first = candidate.locator.first
                            if first.is_visible(timeout=2000):
                                if task_id:
                                    log_step(task_id, f'{log_prefix}_found', {
                                        'strategy': candidate.strategy,
                                        'confidence': candidate.confidence,
                                        'why': candidate.why,
                                        'attempt': idx + 1
                                    })
                                
                                # Record successful strategy in domain memory
                                if domain:
                                    domain_memory.record_locator_strategy(
                                        domain,
                                        target_role,
                                        candidate.strategy,
                                        True
                                    )
                                
                                return candidate.locator, candidate, candidates
                    except:
                        # Element exists but not visible, try next
                        if task_id:
                            log_step(task_id, f'{log_prefix}_candidate_not_visible', {
                                'index': idx,
                                'strategy': candidate.strategy
                            })
                        continue
                else:
                    if task_id:
                        log_step(task_id, f'{log_prefix}_candidate_not_found', {
                            'index': idx,
                            'strategy': candidate.strategy
                        })
            except Exception as e:
                if task_id:
                    log_step(task_id, f'{log_prefix}_candidate_error', {
                        'index': idx,
                        'strategy': candidate.strategy,
                        'error': str(e)
                    })
                logger.debug(f"Candidate {idx} failed: {e}")
                continue
        
        # None of the candidates worked
        if task_id:
            log_step(task_id, f'{log_prefix}_failed', {
                'candidates_tried': len(top_candidates),
                'all_candidates': len(candidates)
            })
        
        return None, None, candidates
    
    @classmethod
    def _generate_candidates(
        cls,
        page: Page,
        target_role: str,
        keywords: List[str],
        context: Optional[Dict],
        task_id: Optional[int],
        log_prefix: str
    ) -> List[LocatorCandidate]:
        """Generate ranked locator candidates"""
        candidates = []
        
        # Normalize target role
        normalized_role = cls._normalize_role(target_role)
        
        # Strategy 1: getByRole + name matching (highest confidence)
        candidates.extend(cls._strategy_get_by_role(page, normalized_role, keywords, context, task_id, log_prefix))
        
        # Strategy 2: label/placeholder matching
        candidates.extend(cls._strategy_label_placeholder(page, normalized_role, keywords, context, task_id, log_prefix))
        
        # Strategy 3: visible text matching
        candidates.extend(cls._strategy_visible_text(page, normalized_role, keywords, context, task_id, log_prefix))
        
        # Strategy 4: stable attributes (aria/name/autocomplete/data-testid)
        candidates.extend(cls._strategy_stable_attrs(page, normalized_role, keywords, context, task_id, log_prefix))
        
        # Strategy 5: CSS/XPath fallback
        candidates.extend(cls._strategy_css_xpath(page, normalized_role, keywords, context, task_id, log_prefix))
        
        return candidates
    
    @classmethod
    def _normalize_role(cls, role: str) -> str:
        """Normalize role to Playwright role"""
        role_lower = role.lower()
        return cls.ROLE_MAPPINGS.get(role_lower, role_lower)
    
    @classmethod
    def _strategy_get_by_role(
        cls,
        page: Page,
        role: str,
        keywords: List[str],
        context: Optional[Dict],
        task_id: Optional[int],
        log_prefix: str
    ) -> List[LocatorCandidate]:
        """Strategy 1: getByRole + name matching"""
        candidates = []
        
        try:
            # Try each keyword as name
            for keyword in keywords:
                if not keyword:
                    continue
                
                try:
                    # Use iframe router
                    def locator_builder(ctx):
                        if isinstance(ctx, Page):
                            return ctx.get_by_role(role, name=re.compile(keyword, re.IGNORECASE))
                        else:  # FrameLocator
                            return ctx.get_by_role(role, name=re.compile(keyword, re.IGNORECASE))
                    
                    locator, frame, source = IframeRouter.find_in_main_or_frames(
                        page,
                        locator_builder,
                        task_id=task_id,
                        description=f'{log_prefix}_get_by_role'
                    )
                    
                    if locator:
                        candidates.append(LocatorCandidate(
                            locator=locator,
                            confidence=0.95,
                            strategy='get_by_role',
                            why=f"getByRole('{role}', name=~'{keyword}') in {source}",
                            frame_context=frame
                        ))
                except Exception as e:
                    logger.debug(f"getByRole strategy failed for '{keyword}': {e}")
        
        except Exception as e:
            logger.debug(f"Error in getByRole strategy: {e}")
        
        return candidates
    
    @classmethod
    def _strategy_label_placeholder(
        cls,
        page: Page,
        role: str,
        keywords: List[str],
        context: Optional[Dict],
        task_id: Optional[int],
        log_prefix: str
    ) -> List[LocatorCandidate]:
        """Strategy 2: label/placeholder matching"""
        candidates = []
        
        try:
            # Build selectors for label/placeholder
            for keyword in keywords:
                if not keyword:
                    continue
                
                # Label-based selectors
                label_selectors = [
                    f'input[type="{role}"][placeholder*="{keyword}" i]',
                    f'textarea[placeholder*="{keyword}" i]',
                    f'input[name*="{keyword}" i]',
                    f'textarea[name*="{keyword}" i]',
                    f'label:has-text("{keyword}") ~ input',
                    f'label:has-text("{keyword}") ~ textarea',
                ]
                
                # Add role-specific selectors
                if role in ['textbox', 'input']:
                    label_selectors.extend([
                        f'input[placeholder*="{keyword}" i]',
                        f'input[name*="{keyword}" i]',
                    ])
                elif role == 'button':
                    label_selectors.extend([
                        f'button:has-text("{keyword}")',
                        f'input[type="submit"][value*="{keyword}" i]',
                    ])
                
                for selector in label_selectors:
                    try:
                        def locator_builder(ctx):
                            if isinstance(ctx, Page):
                                return ctx.locator(selector)
                            else:  # FrameLocator
                                return ctx.locator(selector)
                        
                        locator, frame, source = IframeRouter.find_in_main_or_frames(
                            page,
                            locator_builder,
                            task_id=task_id,
                            description=f'{log_prefix}_label_placeholder'
                        )
                        
                        if locator:
                            candidates.append(LocatorCandidate(
                                locator=locator,
                                confidence=0.85,
                                strategy='label_placeholder',
                                why=f"Label/placeholder matching '{keyword}' via {selector} in {source}",
                                frame_context=frame
                            ))
                            break  # Found one, move to next keyword
                    except:
                        continue
        except Exception as e:
            logger.debug(f"Error in label/placeholder strategy: {e}")
        
        return candidates
    
    @classmethod
    def _strategy_visible_text(
        cls,
        page: Page,
        role: str,
        keywords: List[str],
        context: Optional[Dict],
        task_id: Optional[int],
        log_prefix: str
    ) -> List[LocatorCandidate]:
        """Strategy 3: visible text matching"""
        candidates = []
        
        try:
            for keyword in keywords:
                if not keyword:
                    continue
                
                # Text-based selectors
                text_selectors = [
                    f'text={keyword}',
                    f'text=/{keyword}/i',
                ]
                
                # Role-specific text matching
                if role == 'button':
                    text_selectors.extend([
                        f'button:has-text("{keyword}")',
                        f'button:has-text(/{keyword}/i)',
                    ])
                elif role == 'link':
                    text_selectors.extend([
                        f'a:has-text("{keyword}")',
                        f'a:has-text(/{keyword}/i)',
                    ])
                
                for selector in text_selectors:
                    try:
                        def locator_builder(ctx):
                            if isinstance(ctx, Page):
                                if selector.startswith('text='):
                                    return ctx.locator(selector)
                                else:
                                    return ctx.locator(selector)
                            else:  # FrameLocator
                                return ctx.locator(selector)
                        
                        locator, frame, source = IframeRouter.find_in_main_or_frames(
                            page,
                            locator_builder,
                            task_id=task_id,
                            description=f'{log_prefix}_visible_text'
                        )
                        
                        if locator:
                            candidates.append(LocatorCandidate(
                                locator=locator,
                                confidence=0.75,
                                strategy='visible_text',
                                why=f"Visible text matching '{keyword}' via {selector} in {source}",
                                frame_context=frame
                            ))
                            break
                    except:
                        continue
        except Exception as e:
            logger.debug(f"Error in visible text strategy: {e}")
        
        return candidates
    
    @classmethod
    def _strategy_stable_attrs(
        cls,
        page: Page,
        role: str,
        keywords: List[str],
        context: Optional[Dict],
        task_id: Optional[int],
        log_prefix: str
    ) -> List[LocatorCandidate]:
        """Strategy 4: stable attributes (aria/name/autocomplete/data-testid)"""
        candidates = []
        
        try:
            for keyword in keywords:
                if not keyword:
                    continue
                
                # Stable attribute selectors
                attr_selectors = [
                    f'[aria-label*="{keyword}" i]',
                    f'[name*="{keyword}" i]',
                    f'[autocomplete*="{keyword}" i]',
                    f'[data-testid*="{keyword}" i]',
                    f'[id*="{keyword}" i]',
                ]
                
                # Role-specific attribute selectors
                if role in ['textbox', 'input']:
                    attr_selectors.extend([
                        f'input[aria-label*="{keyword}" i]',
                        f'input[name*="{keyword}" i]',
                        f'textarea[aria-label*="{keyword}" i]',
                        f'textarea[name*="{keyword}" i]',
                    ])
                elif role == 'button':
                    attr_selectors.extend([
                        f'button[aria-label*="{keyword}" i]',
                        f'button[name*="{keyword}" i]',
                    ])
                
                for selector in attr_selectors:
                    try:
                        def locator_builder(ctx):
                            if isinstance(ctx, Page):
                                return ctx.locator(selector)
                            else:  # FrameLocator
                                return ctx.locator(selector)
                        
                        locator, frame, source = IframeRouter.find_in_main_or_frames(
                            page,
                            locator_builder,
                            task_id=task_id,
                            description=f'{log_prefix}_stable_attrs'
                        )
                        
                        if locator:
                            candidates.append(LocatorCandidate(
                                locator=locator,
                                confidence=0.70,
                                strategy='stable_attrs',
                                why=f"Stable attribute matching '{keyword}' via {selector} in {source}",
                                frame_context=frame
                            ))
                            break
                    except:
                        continue
        except Exception as e:
            logger.debug(f"Error in stable attrs strategy: {e}")
        
        return candidates
    
    @classmethod
    def _strategy_css_xpath(
        cls,
        page: Page,
        role: str,
        keywords: List[str],
        context: Optional[Dict],
        task_id: Optional[int],
        log_prefix: str
    ) -> List[LocatorCandidate]:
        """Strategy 5: CSS/XPath fallback"""
        candidates = []
        
        try:
            for keyword in keywords:
                if not keyword:
                    continue
                
                # CSS fallback selectors
                css_selectors = []
                
                if role in ['textbox', 'input']:
                    css_selectors = [
                        f'input[type="text"][class*="{keyword}" i]',
                        f'textarea[class*="{keyword}" i]',
                        f'input[class*="{keyword}" i]',
                        f'textarea[class*="{keyword}" i]',
                    ]
                elif role == 'button':
                    css_selectors = [
                        f'button[class*="{keyword}" i]',
                        f'input[type="submit"][class*="{keyword}" i]',
                    ]
                elif role == 'form':
                    css_selectors = [
                        f'form[class*="{keyword}" i]',
                        f'form[id*="{keyword}" i]',
                        f'form[action*="{keyword}" i]',
                    ]
                
                for selector in css_selectors:
                    try:
                        def locator_builder(ctx):
                            if isinstance(ctx, Page):
                                return ctx.locator(selector)
                            else:  # FrameLocator
                                return ctx.locator(selector)
                        
                        locator, frame, source = IframeRouter.find_in_main_or_frames(
                            page,
                            locator_builder,
                            task_id=task_id,
                            description=f'{log_prefix}_css_xpath'
                        )
                        
                        if locator:
                            candidates.append(LocatorCandidate(
                                locator=locator,
                                confidence=0.60,
                                strategy='css_xpath',
                                why=f"CSS fallback matching '{keyword}' via {selector} in {source}",
                                frame_context=frame
                            ))
                            break
                    except:
                        continue
                
                # XPath fallback (if CSS didn't work)
                if not candidates:
                    xpath_patterns = [
                        f'//input[contains(translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "{keyword.lower()}")]',
                        f'//textarea[contains(translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "{keyword.lower()}")]',
                    ]
                    
                    for xpath in xpath_patterns:
                        try:
                            def locator_builder(ctx):
                                if isinstance(ctx, Page):
                                    return ctx.locator(f'xpath={xpath}')
                                else:  # FrameLocator
                                    return ctx.locator(f'xpath={xpath}')
                            
                            locator, frame, source = IframeRouter.find_in_main_or_frames(
                                page,
                                locator_builder,
                                task_id=task_id,
                                description=f'{log_prefix}_css_xpath'
                            )
                            
                            if locator:
                                candidates.append(LocatorCandidate(
                                    locator=locator,
                                    confidence=0.55,
                                    strategy='css_xpath',
                                    why=f"XPath fallback matching '{keyword}' in {source}",
                                    frame_context=frame
                                ))
                                break
                        except:
                            continue
        except Exception as e:
            logger.debug(f"Error in CSS/XPath strategy: {e}")
        
        return candidates
    
    @classmethod
    def find_form(
        cls,
        page: Page,
        keywords: List[str],
        context: Optional[Dict] = None,
        task_id: Optional[int] = None
    ) -> Tuple[Optional[Locator], Optional[LocatorCandidate], List[LocatorCandidate]]:
        """Convenience method: Find form"""
        return cls.find(page, 'form', keywords, context, task_id)
    
    @classmethod
    def find_input(
        cls,
        page: Page,
        keywords: List[str],
        context: Optional[Dict] = None,
        task_id: Optional[int] = None
    ) -> Tuple[Optional[Locator], Optional[LocatorCandidate], List[LocatorCandidate]]:
        """Convenience method: Find input/textarea"""
        return cls.find(page, 'input', keywords, context, task_id)
    
    @classmethod
    def find_button(
        cls,
        page: Page,
        keywords: List[str],
        context: Optional[Dict] = None,
        task_id: Optional[int] = None
    ) -> Tuple[Optional[Locator], Optional[LocatorCandidate], List[LocatorCandidate]]:
        """Convenience method: Find button"""
        return cls.find(page, 'button', keywords, context, task_id)

