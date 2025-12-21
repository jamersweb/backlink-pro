# Self-Healing Locator Engine Implementation

## Overview

Implemented a self-healing locator engine that generates ranked locator candidates with confidence scores and tries multiple strategies before failing.

## Components

### Locator Engine (`core/locator_engine.py`)

**Class:** `LocatorEngine`

**Main Method:** `find(page, target_role, keywords, context, task_id, top_k)`

**Input:**
- `target_role`: Target role (button, input, form, textarea, etc.)
- `keywords`: List of keywords to match (name, label, placeholder, etc.)
- `context`: Optional context (form_locator, parent_locator, etc.)
- `task_id`: Optional task ID for logging
- `top_k`: Number of top candidates to try (default: 5)

**Output:**
- `found_locator`: Locator if found, None otherwise
- `winning_candidate`: LocatorCandidate object with metadata
- `all_candidates`: List of all generated candidates

## Ranked Strategies

### Strategy 1: getByRole + Name Matching (Confidence: 0.95)

**Highest confidence** - Uses Playwright's `get_by_role()` with name matching

**Example:**
```python
page.get_by_role('button', name=re.compile('submit', re.IGNORECASE))
```

**Why:** Most reliable, uses accessibility attributes

### Strategy 2: Label/Placeholder Matching (Confidence: 0.85)

Matches against labels and placeholders

**Selectors:**
- `input[placeholder*="keyword" i]`
- `label:has-text("keyword") ~ input`
- `input[name*="keyword" i]`

**Why:** Common pattern, stable across sites

### Strategy 3: Visible Text Matching (Confidence: 0.75)

Matches visible text content

**Selectors:**
- `text=keyword`
- `button:has-text("keyword")`
- `a:has-text("keyword")`

**Why:** Works when text is visible but attributes aren't

### Strategy 4: Stable Attributes (Confidence: 0.70)

Matches stable attributes

**Selectors:**
- `[aria-label*="keyword" i]`
- `[name*="keyword" i]`
- `[autocomplete*="keyword" i]`
- `[data-testid*="keyword" i]`
- `[id*="keyword" i]`

**Why:** Stable attributes less likely to change

### Strategy 5: CSS/XPath Fallback (Confidence: 0.60-0.55)

CSS and XPath fallback selectors

**Selectors:**
- `input[class*="keyword" i]`
- `form[action*="keyword" i]`
- XPath: `//input[contains(@name, "keyword")]`

**Why:** Last resort, less reliable but catches edge cases

## LocatorCandidate

Each candidate includes:
- `locator`: Playwright Locator
- `confidence`: Score (0.0-1.0)
- `strategy`: Strategy name
- `why`: Explanation
- `frame_context`: Frame if found in iframe

## IframeRouter Integration

All strategies use `IframeRouter` under the hood:
- Tries main page first
- Falls back to iframes if not found
- Returns frame context if found in iframe

## Logging

All operations logged via telemetry:

### Log Events

- `locator_engine_{description}_start` - Search started
- `locator_engine_{description}_candidates_generated` - Candidates generated
- `locator_engine_{description}_try_candidate` - Trying candidate
- `locator_engine_{description}_found` - Found element
- `locator_engine_{description}_candidate_not_visible` - Candidate not visible
- `locator_engine_{description}_candidate_not_found` - Candidate not found
- `locator_engine_{description}_candidate_error` - Candidate error
- `locator_engine_{description}_failed` - All candidates failed

### Example Log Flow

```json
{"step": "locator_engine_submit_button_start", "meta": {"target_role": "button", "keywords": ["submit", "post"]}}
{"step": "locator_engine_submit_button_candidates_generated", "meta": {"count": 8, "top_confidence": 0.95}}
{"step": "locator_engine_submit_button_try_candidate", "meta": {"index": 0, "strategy": "get_by_role", "confidence": 0.95, "why": "getByRole('button', name=~'submit') in main"}}
{"step": "locator_engine_submit_button_found", "meta": {"strategy": "get_by_role", "confidence": 0.95, "attempt": 1}}
```

## Usage

### Basic Usage

```python
from core.locator_engine import LocatorEngine

# Find submit button
locator, candidate, all_candidates = LocatorEngine.find(
    page,
    target_role='button',
    keywords=['submit', 'post', 'send'],
    task_id=123
)

if locator:
    print(f"Found via {candidate.strategy} (confidence: {candidate.confidence})")
    print(f"Why: {candidate.why}")
    locator.click()
```

### Convenience Methods

```python
# Find form
form_locator, form_candidate, _ = LocatorEngine.find_form(
    page,
    keywords=['comment', 'reply'],
    task_id=123
)

# Find input
input_locator, input_candidate, _ = LocatorEngine.find_input(
    page,
    keywords=['email', 'username'],
    task_id=123
)

# Find button
button_locator, button_candidate, _ = LocatorEngine.find_button(
    page,
    keywords=['submit', 'post'],
    task_id=123
)
```

### In Automation Classes

```python
# In BaseAutomation
locator, candidate, candidates = self.find_with_locator_engine(
    target_role='button',
    keywords=['submit', 'post'],
    context={'form_locator': form}
)

if locator:
    logger.info(f"Found button via {candidate.strategy}: {candidate.why}")
    locator.click()
else:
    logger.warning(f"Failed to find button. Tried {len(candidates)} candidates")
```

## Integration Points

### Comment Automation

**Potential integration:**
- Find submit buttons with `find_button(['submit', 'post'])`
- Find textareas with `find_input(['comment', 'message'])`
- Find forms with `find_form(['comment', 'reply'])`

### Profile Automation

**Potential integration:**
- Find registration forms
- Find email/password inputs
- Find submit buttons

## Example Scenarios

### Scenario 1: Submit Button with Multiple Strategies

**Input:**
- `target_role`: 'button'
- `keywords`: ['submit', 'post']

**Generated Candidates:**
1. `getByRole('button', name=~'submit')` - Confidence: 0.95
2. `button:has-text("submit")` - Confidence: 0.75
3. `button[aria-label*="submit" i]` - Confidence: 0.70
4. `button[class*="submit" i]` - Confidence: 0.60

**Result:**
- Tries candidate 1 first (highest confidence)
- If fails, tries candidate 2, then 3, then 4
- Returns first successful candidate

### Scenario 2: Comment Form in Iframe

**Input:**
- `target_role`: 'form'
- `keywords`: ['comment', 'reply']

**Process:**
1. Tries main page with all strategies
2. Not found in main page
3. Tries iframes with all strategies
4. Found in iframe_0 via `getByRole('form', name=~'comment')`
5. Returns locator + frame context

## Acceptance Criteria

✅ **When primary selectors fail, engine tries fallbacks before failing**
- Generates multiple candidates with different strategies
- Tries top-K candidates in order of confidence
- Only fails if all candidates fail

✅ **Logs show candidates tried and final pick**
- All candidates logged with strategy, confidence, why
- Each attempt logged
- Final pick logged with metadata

## Notes

- **Self-healing:** Automatically tries multiple strategies
- **Confidence-based:** Tries highest confidence first
- **Iframe-aware:** Uses IframeRouter for iframe support
- **Comprehensive logging:** All decisions logged
- **Non-intrusive:** Falls back gracefully if engine unavailable

