# Page State Detection and Popup Control

## Overview

Implemented page state detection and popup dismissal utilities for better automation reliability.

## Components

### 1. State Detector (`core/state_detector.py`)

**Class:** `StateDetector`

**Method:** `analyze(page: Page) -> PageState`

**Detects:**
- **Overlays/Modals:**
  - `overlay_present` - Generic overlay detected
  - `modal_present` - Modal dialog present
  - `cookie_banner_present` - Cookie consent banner
  - `newsletter_modal_present` - Newsletter subscription modal
  - `login_modal_present` - Login modal dialog

- **Authentication:**
  - `login_required` - Login/auth wall detected
  - `registration_hints` - Registration form/signup hints
  - `email_verification_hints` - Email verification prompts

- **Technical:**
  - `iframe_count` - Number of iframes on page
  - `captcha_present` - Captcha detected (detect only, no solving)

- **Blocking:**
  - `blocked_hints` - Blocked/forbidden indicators
  - `bot_check_hints` - Bot check/challenge detected

- **Intent Guess:**
  - `intent_guess` - One of: `login`, `comment`, `profile`, `forum`, `guest`, `unknown`

**Returns:** `PageState` object with all detections

### 2. Popup Controller (`core/popup_controller.py`)

**Class:** `PopupController`

**Method:** `clear_if_needed(page: Page, task_id: int, state: Optional[PageState] = None) -> Dict`

**Strategies:**
1. **Close Buttons:**
   - Searches for buttons/links with text: "close", "x", "accept", "not now", "dismiss", "decline", "reject", "no thanks", "maybe later", "skip", "cancel"
   - Searches for elements with selectors: `[aria-label*="close"]`, `[class*="close"]`, `[id*="close"]`, etc.
   - Clicks first visible close button found

2. **ESC Key:**
   - Presses ESC key
   - Waits for modal/overlay to close
   - Verifies clearing worked

**Logging:**
- All steps logged via `core.telemetry.log_step()`
- Logs include: analysis start/complete, clear start/success/failed, strategy attempts, errors

**Returns:** Dict with:
- `cleared` - Boolean indicating if popup was cleared
- `strategies_attempted` - List of strategies tried
- `errors` - List of errors encountered

## Integration

### Worker Integration

**Location:** `worker.py` - Before `automation.execute(task)`

**Flow:**
1. Enter automation context
2. Save initial snapshot (if page available)
3. **Detect page state** (`StateDetector.analyze()`)
4. **Clear popups if needed** (`PopupController.clear_if_needed()`)
5. Execute automation

**Code:**
```python
# Detect page state and clear popups before automation
if hasattr(automation, 'page') and automation.page:
    log_step(task_id, 'page_state_detection_start')
    page_state = StateDetector.analyze(automation.page)
    log_step(task_id, 'page_state_detected', page_state.to_dict())
    
    # Clear popups if needed
    popup_result = PopupController.clear_if_needed(
        automation.page, 
        task_id, 
        state=page_state
    )
    log_step(task_id, 'popup_clear_complete', popup_result)
```

## PageState Structure

```python
{
    'overlay_present': bool,
    'modal_present': bool,
    'cookie_banner_present': bool,
    'newsletter_modal_present': bool,
    'login_modal_present': bool,
    'login_required': bool,
    'registration_hints': bool,
    'email_verification_hints': bool,
    'iframe_count': int,
    'captcha_present': bool,
    'blocked_hints': bool,
    'bot_check_hints': bool,
    'intent_guess': str,  # 'login', 'comment', 'profile', 'forum', 'guest', 'unknown'
}
```

## Logging

All operations are logged via telemetry:

### State Detection Logs
- `page_state_detection_start` - Detection started
- `page_state_detected` - Detection complete (includes full state dict)
- `page_state_detection_error` - Error during detection

### Popup Clearing Logs
- `popup_analysis_start` - Analysis started
- `popup_analysis_complete` - Analysis complete
- `popup_clear_not_needed` - No popups detected
- `popup_clear_start` - Clearing started
- `popup_strategy_{name}_start` - Strategy attempt started
- `popup_strategy_{name}_success` - Strategy succeeded
- `popup_strategy_{name}_failed` - Strategy failed
- `popup_strategy_{name}_error` - Strategy error
- `popup_close_button_found` - Close button found
- `popup_close_link_found` - Close link found
- `popup_esc_key_attempt` - ESC key pressed
- `popup_esc_key_success` - ESC key cleared popup
- `popup_esc_key_failed` - ESC key didn't clear popup
- `popup_clear_verified` - Clearing verified
- `popup_clear_success` - Clearing succeeded
- `popup_clear_failed` - Clearing failed
- `popup_clear_error` - Error during clearing
- `popup_clear_complete` - Clearing complete (includes result)

## Example Log Flow

```
{"step": "page_state_detection_start"}
{"step": "page_state_detected", "meta": {"overlay_present": true, "cookie_banner_present": true, ...}}
{"step": "popup_analysis_start"}
{"step": "popup_analysis_complete", "meta": {...}}
{"step": "popup_clear_start", "meta": {"overlay_present": true, "cookie_banner_present": true}}
{"step": "popup_strategy_close_buttons_start"}
{"step": "popup_close_button_found", "meta": {"text": "accept"}}
{"step": "popup_strategy_close_buttons_success"}
{"step": "popup_clear_verified"}
{"step": "popup_clear_success"}
{"step": "popup_clear_complete", "meta": {"cleared": true, "strategies_attempted": ["close_buttons"]}}
```

## Detection Patterns

### Cookie Banner Detection
- Selectors: `[id*="cookie"]`, `[class*="cookie"]`, `[id*="consent"]`, `[class*="gdpr"]`, etc.
- Text: "cookie", "consent", "privacy", "gdpr"

### Modal Detection
- Selectors: `[role="dialog"]`, `[class*="modal"]`, `[id*="popup"]`, `[class*="overlay"]`, etc.

### Login Detection
- Selectors: `input[type="email"]`, `input[type="password"]`, `form[action*="login"]`, etc.
- Text: "please log in", "login required", "sign in to continue"

### Captcha Detection
- Selectors: `[class*="recaptcha"]`, `[id*="hcaptcha"]`, `iframe[src*="recaptcha"]`, etc.
- Content: "recaptcha", "hcaptcha"

### Intent Guessing
- **Comment:** `textarea[name*="comment"]`, `form[action*="comment"]`
- **Profile:** `[class*="profile"]`, `a[href*="profile"]`
- **Forum:** `[class*="forum"]`, `a[href*="forum"]`
- **Guest:** `a[href*="guest"]`, `form[action*="submit"]`
- **Login:** Login form detected
- **Unknown:** Default fallback

## Acceptance Criteria

✅ **On pages with cookie banners/modals, controller removes overlay before continuing**
- State detector identifies overlays/modals
- Popup controller attempts to clear them
- Verification ensures clearing worked

✅ **Logging shows popup clear attempts**
- All steps logged via telemetry
- Strategy attempts logged
- Success/failure logged
- Errors logged

## Usage

### Manual Usage

```python
from core.state_detector import StateDetector
from core.popup_controller import PopupController

# Detect state
state = StateDetector.analyze(page)
print(state.to_dict())

# Clear popups
result = PopupController.clear_if_needed(page, task_id=123, state=state)
print(result)  # {'cleared': True, 'strategies_attempted': ['close_buttons'], 'errors': []}
```

## Notes

- **Non-intrusive:** Failures in state detection/popup clearing don't stop automation
- **Comprehensive logging:** All operations logged for observability
- **Multiple strategies:** Tries different approaches to clear popups
- **Verification:** Verifies that clearing actually worked
- **Safe:** Handles errors gracefully

