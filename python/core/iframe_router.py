"""
Iframe Router Helper for Playwright

Routes locator searches across main page and iframes
"""

import logging
from typing import Callable, Optional, Tuple, Dict, List, Any
from playwright.sync_api import Page, FrameLocator, Locator, Frame

logger = logging.getLogger(__name__)


class IframeRouter:
    """Routes locator searches across main page and iframes"""
    
    @classmethod
    def find_in_main_or_frames(
        cls,
        page: Page,
        locator_build_fn: Callable[[Any], Locator],
        task_id: Optional[int] = None,
        description: Optional[str] = None
    ) -> Tuple[Optional[Locator], Optional[Frame], str]:
        """
        Find element in main page or iframes
        
        Args:
            page: Playwright Page object
            locator_build_fn: Function that takes a context (Page or FrameLocator) and returns a Locator
            task_id: Optional task ID for logging
            description: Optional description for logging
        
        Returns:
            Tuple of (locator, frame_context, source) where:
            - locator: Found locator or None
            - frame_context: Frame if found in iframe, None if in main page
            - source: "main" or "iframe_{index}" or "not_found"
        """
        from core.telemetry import log_step
        from core.domain_memory import get_domain_memory
        
        description = description or "element"
        log_prefix = f"iframe_router_{description}"
        
        try:
            # Step 1: Try main page first
            if task_id:
                log_step(task_id, f'{log_prefix}_try_main')
            
            try:
                main_locator = locator_build_fn(page)
                # Check if element exists and is visible
                if main_locator.count() > 0:
                    # Verify at least one is visible
                    try:
                        first = main_locator.first
                        if first.is_visible(timeout=1000):
                            if task_id:
                                log_step(task_id, f'{log_prefix}_found_main', {
                                    'count': main_locator.count(),
                                    'source': 'main'
                                })
                            return main_locator, None, "main"
                    except:
                        # Element exists but not visible, continue to iframes
                        pass
            except Exception as e:
                logger.debug(f"Main page search failed: {e}")
            
            # Step 2: Try iframes
            if task_id:
                log_step(task_id, f'{log_prefix}_try_iframes')
            
            frames = cls.list_frames(page)
            if task_id:
                log_step(task_id, f'{log_prefix}_frames_found', {
                    'frame_count': len(frames)
                })
            
            for idx, frame_info in enumerate(frames):
                frame = frame_info['frame']
                frame_url = frame_info.get('url', 'unknown')
                
                try:
                    if task_id:
                        log_step(task_id, f'{log_prefix}_try_frame', {
                            'frame_index': idx,
                            'frame_url': frame_url
                        })
                    
                    # Use frame_locator for iframe
                    frame_locator = page.frame_locator(f'iframe:nth-of-type({idx + 1})')
                    iframe_locator = locator_build_fn(frame_locator)
                    
                    # Check if element exists
                    if iframe_locator.count() > 0:
                        # Verify at least one is visible
                        try:
                            first = iframe_locator.first
                            if first.is_visible(timeout=1000):
                                if task_id:
                                    log_step(task_id, f'{log_prefix}_found_frame', {
                                        'frame_index': idx,
                                        'frame_url': frame_url,
                                        'count': iframe_locator.count(),
                                        'source': f'iframe_{idx}'
                                    })
                                
                                # Record iframe usage in domain memory
                                try:
                                    page_url = page.url
                                    from urllib.parse import urlparse
                                    parsed = urlparse(page_url)
                                    domain = parsed.netloc or parsed.path.split('/')[0] if parsed.path else None
                                    if domain:
                                        domain_memory = get_domain_memory()
                                        domain_memory.record_iframe_used(domain, True)
                                except:
                                    pass
                                
                                return iframe_locator, frame, f"iframe_{idx}"
                        except:
                            # Element exists but not visible, continue
                            continue
                            
                except Exception as e:
                    logger.debug(f"Iframe {idx} search failed: {e}")
                    if task_id:
                        log_step(task_id, f'{log_prefix}_frame_error', {
                            'frame_index': idx,
                            'error': str(e)
                        })
                    continue
            
            # Not found in main or any iframe
            if task_id:
                log_step(task_id, f'{log_prefix}_not_found', {
                    'searched_frames': len(frames) + 1  # +1 for main page
                })
            
            return None, None, "not_found"
            
        except Exception as e:
            logger.error(f"Error in iframe router: {e}")
            if task_id:
                log_step(task_id, f'{log_prefix}_error', {'error': str(e)})
            return None, None, "error"
    
    @classmethod
    def list_frames(cls, page: Page) -> List[Dict[str, Any]]:
        """
        List all frames for debugging
        
        Args:
            page: Playwright Page object
        
        Returns:
            List of frame info dicts
        """
        frames_info = []
        
        try:
            # Get all iframes
            iframes = page.query_selector_all('iframe')
            
            for idx, iframe in enumerate(iframes):
                try:
                    frame_info = {
                        'index': idx,
                        'src': iframe.get_attribute('src') or '',
                        'id': iframe.get_attribute('id') or '',
                        'name': iframe.get_attribute('name') or '',
                        'title': iframe.get_attribute('title') or '',
                    }
                    
                    # Try to get frame object
                    try:
                        # Get frame by index (1-based)
                        frame = page.frames[idx + 1] if idx + 1 < len(page.frames) else None
                        if frame:
                            frame_info['frame'] = frame
                            frame_info['url'] = frame.url
                            frame_info['name'] = frame.name or frame_info['name']
                    except:
                        frame_info['frame'] = None
                        frame_info['url'] = 'unknown'
                    
                    frames_info.append(frame_info)
                except Exception as e:
                    logger.debug(f"Error getting frame {idx} info: {e}")
                    frames_info.append({
                        'index': idx,
                        'error': str(e)
                    })
        except Exception as e:
            logger.error(f"Error listing frames: {e}")
        
        return frames_info
    
    @classmethod
    def find_with_fallback(
        cls,
        page: Page,
        main_selector: str,
        locator_build_fn: Optional[Callable[[Any], Locator]] = None,
        task_id: Optional[int] = None,
        description: Optional[str] = None
    ) -> Tuple[Optional[Locator], Optional[Frame], str]:
        """
        Convenience method: Find element with selector, fallback to iframes
        
        Args:
            page: Playwright Page object
            main_selector: CSS selector to search for
            locator_build_fn: Optional custom locator builder (defaults to selector search)
            task_id: Optional task ID for logging
            description: Optional description for logging
        
        Returns:
            Tuple of (locator, frame_context, source)
        """
        if locator_build_fn is None:
            def default_builder(context):
                if isinstance(context, Page):
                    return page.locator(main_selector)
                else:  # FrameLocator
                    return context.locator(main_selector)
            locator_build_fn = default_builder
        
        return cls.find_in_main_or_frames(page, locator_build_fn, task_id, description)
    
    @classmethod
    def find_form_in_frames(
        cls,
        page: Page,
        form_selector: Optional[str] = None,
        task_id: Optional[int] = None
    ) -> Tuple[Optional[Locator], Optional[Frame], str]:
        """
        Find form in main page or iframes
        
        Args:
            page: Playwright Page object
            form_selector: Optional form selector (defaults to 'form')
            task_id: Optional task ID for logging
        
        Returns:
            Tuple of (locator, frame_context, source)
        """
        form_selector = form_selector or 'form'
        
        def form_builder(context):
            if isinstance(context, Page):
                return context.locator(form_selector)
            else:  # FrameLocator
                return context.locator(form_selector)
        
        return cls.find_in_main_or_frames(
            page,
            form_builder,
            task_id,
            description='form'
        )
    
    @classmethod
    def find_input_in_frames(
        cls,
        page: Page,
        input_selector: str,
        task_id: Optional[int] = None
    ) -> Tuple[Optional[Locator], Optional[Frame], str]:
        """
        Find input in main page or iframes
        
        Args:
            page: Playwright Page object
            input_selector: Input selector
            task_id: Optional task ID for logging
        
        Returns:
            Tuple of (locator, frame_context, source)
        """
        def input_builder(context):
            if isinstance(context, Page):
                return context.locator(input_selector)
            else:  # FrameLocator
                return context.locator(input_selector)
        
        return cls.find_in_main_or_frames(
            page,
            input_builder,
            task_id,
            description='input'
        )

