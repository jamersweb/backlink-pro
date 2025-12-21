# Iframe Router Implementation

## Overview

Implemented robust iframe router helper for Playwright that routes locator searches across main page and iframes.

## Components

### 1. Iframe Router (`core/iframe_router.py`)

**Class:** `IframeRouter`

**Main Method:** `find_in_main_or_frames(page, locator_build_fn, task_id, description)`

**Features:**
- Tries main page first
- Scans iframes using `frameLocator` approach
- Returns locator + frame context
- Provides `list_frames()` for debugging
- Logs routing decisions via telemetry

**Helper Methods:**
- `find_with_fallback()` - Convenience method with selector
- `find_form_in_frames()` - Find forms in main or iframes
- `find_input_in_frames()` - Find inputs in main or iframes
- `list_frames()` - List all frames for debugging

### 2. Integration

**Base Automation (`automation/base.py`):**
- Added `find_with_iframe_fallback()` helper method
- Available to all automation classes

**Comment Automation (`automation/comment.py`):**
- Integrated iframe router in `_find_comment_form()`
- Tries iframe router before falling back to main page
- Logs which frame was used

## Usage

### Basic Usage

```python
from core.iframe_router import IframeRouter

# Find element with iframe fallback
locator, frame, source = IframeRouter.find_in_main_or_frames(
    page,
    locator_build_fn=lambda ctx: ctx.locator('form'),
    task_id=123,
    description='comment_form'
)

if locator:
    print(f"Found in {source}")  # "main" or "iframe_0"
    if frame:
        print(f"Frame URL: {frame.url}")
```

### Convenience Methods

```python
# Find form
form_locator, form_frame, source = IframeRouter.find_form_in_frames(
    page,
    form_selector='form',
    task_id=123
)

# Find input
input_locator, input_frame, source = IframeRouter.find_input_in_frames(
    page,
    input_selector='textarea',
    task_id=123
)

# List frames for debugging
frames = IframeRouter.list_frames(page)
for frame_info in frames:
    print(f"Frame {frame_info['index']}: {frame_info['url']}")
```

### In Automation Classes

```python
# In BaseAutomation
locator, frame, source = self.find_with_iframe_fallback('form', 'comment_form')

# Or directly
if IframeRouter:
    locator, frame, source = IframeRouter.find_form_in_frames(
        self.page,
        form_selector='form',
        task_id=self._current_task_id
    )
```

## Logging

All routing decisions are logged via telemetry:

### Log Events

- `iframe_router_{description}_try_main` - Trying main page
- `iframe_router_{description}_found_main` - Found in main page
- `iframe_router_{description}_try_iframes` - Trying iframes
- `iframe_router_{description}_frames_found` - Frames found (with count)
- `iframe_router_{description}_try_frame` - Trying specific frame
- `iframe_router_{description}_found_frame` - Found in iframe
- `iframe_router_{description}_frame_error` - Error in frame search
- `iframe_router_{description}_not_found` - Not found anywhere
- `iframe_router_{description}_error` - General error

### Example Log Flow

```json
{"step": "iframe_router_comment_form_try_main"}
{"step": "iframe_router_comment_form_try_iframes", "meta": {}}
{"step": "iframe_router_comment_form_frames_found", "meta": {"frame_count": 2}}
{"step": "iframe_router_comment_form_try_frame", "meta": {"frame_index": 0, "frame_url": "https://example.com/iframe"}}
{"step": "iframe_router_comment_form_found_frame", "meta": {"frame_index": 0, "frame_url": "https://example.com/iframe", "count": 1, "source": "iframe_0"}}
```

## Frame Detection

### `list_frames()` Output

```python
[
    {
        'index': 0,
        'src': 'https://example.com/iframe',
        'id': 'comment-iframe',
        'name': 'comments',
        'title': 'Comment Form',
        'frame': <Frame object>,
        'url': 'https://example.com/iframe',
    },
    {
        'index': 1,
        'src': 'https://disqus.com/embed',
        'id': '',
        'name': '',
        'frame': <Frame object>,
        'url': 'https://disqus.com/embed',
    }
]
```

## Integration Points

### Comment Automation

**Location:** `automation/comment.py` - `_find_comment_form()`

**Integration:**
- Strategy 1: Textarea search - Uses iframe router
- Strategy 2: Form selectors - Uses iframe router
- Strategy 3: Container selectors - Uses iframe router
- Strategy 4: Generic forms - Uses iframe router

**Code Pattern:**
```python
if IframeRouter:
    locator, frame, source = IframeRouter.find_form_in_frames(
        self.page,
        form_selector=selector,
        task_id=self._current_task_id
    )
    if locator and locator.count() > 0:
        forms = locator
        logger.info(f"Found form via iframe router in {source}")
    else:
        forms = self.page.locator(selector)
else:
    forms = self.page.locator(selector)
```

## Return Values

### `find_in_main_or_frames()` Returns

```python
Tuple[Optional[Locator], Optional[Frame], str]
```

- **Locator**: Found locator or None
- **Frame**: Frame object if found in iframe, None if in main page
- **Source**: "main", "iframe_{index}", "not_found", or "error"

## Acceptance Criteria

✅ **If a form is inside an iframe, system can locate it via router**
- Router tries main page first
- Falls back to iframes if not found
- Uses `frameLocator` approach for iframe access
- Returns locator + frame context

✅ **Logs show which frame was used**
- All routing decisions logged via telemetry
- Frame index and URL logged
- Source ("main" or "iframe_X") logged

## Example

### Scenario: Comment form in iframe

1. **Main page search:**
   - Tries `page.locator('form')`
   - Not found or not visible
   - Logs: `iframe_router_comment_form_try_main`

2. **Iframe search:**
   - Lists frames: finds 2 iframes
   - Logs: `iframe_router_comment_form_frames_found` (count: 2)
   - Tries frame 0: `page.frame_locator('iframe').nth(0).locator('form')`
   - Not found
   - Tries frame 1: `page.frame_locator('iframe').nth(1).locator('form')`
   - Found!
   - Logs: `iframe_router_comment_form_found_frame` (frame_index: 1, source: "iframe_1")

3. **Result:**
   - Returns: `(locator, frame_object, "iframe_1")`
   - Automation can use locator normally
   - Frame context available if needed

## Notes

- **Non-intrusive:** Falls back to main page if iframe router unavailable
- **Comprehensive logging:** All routing decisions logged
- **Frame context:** Returns frame object for advanced usage
- **Multiple strategies:** Tries different iframe selection methods
- **Safe:** Handles errors gracefully

