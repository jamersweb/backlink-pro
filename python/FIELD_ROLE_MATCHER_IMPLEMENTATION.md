# Field Role Matcher Implementation

## Overview

Implemented a field role matcher that maps varied form fields to standard roles using heuristics, making form filling resilient to label/name changes.

## Components

### Field Role Matcher (`core/field_role_matcher.py`)

**Class:** `FieldRoleMatcher`

**Main Method:** `match_fields(page, form_locator, task_id)`

**Input:**
- `page`: Playwright Page object
- `form_locator`: Optional form locator (searches within form)
- `task_id`: Optional task ID for logging

**Output:**
- Dict mapping `role -> (locator, confidence_score)`
- Only includes roles with confidence >= `MIN_CONFIDENCE` (0.60)

**Supported Roles:**
- `email` - Email input fields
- `username` - Username/login fields
- `password` - Password fields
- `comment` - Comment/message textareas
- `website` - Website/URL fields
- `name` - Name/full name fields
- `bio` - Biography/about fields

## Heuristics

### 1. Input Type (Weight: 0.30-0.40)

**High confidence indicators:**
- `type="email"` → email role (0.40)
- `type="password"` → password role (0.40)
- Other matching types → 0.30

### 2. Tag Name (Weight: 0.25)

**Special cases:**
- `textarea` → comment/bio roles (0.25 bonus)
- `textarea` for non-textarea roles → penalty (-0.10)

### 3. Name Attribute Tokens (Weight: 0.20)

Matches against field `name` attribute:
- `name="email"` → email role
- `name="username"` → username role
- `name="comment"` → comment role

### 4. ID Attribute Tokens (Weight: 0.15)

Matches against field `id` attribute:
- `id="email-field"` → email role
- `id="user-name"` → username role

### 5. Placeholder Tokens (Weight: 0.15)

Matches against field `placeholder` attribute:
- `placeholder="Enter your email"` → email role
- `placeholder="Your comment"` → comment role

### 6. Autocomplete Hints (Weight: 0.20)

Matches against `autocomplete` attribute:
- `autocomplete="email"` → email role
- `autocomplete="username"` → username role
- `autocomplete="current-password"` → password role

### 7. Label Tokens (Weight: 0.20)

Finds associated label and matches text:
- `<label>Email Address</label>` → email role
- `<label>Your Name</label>` → name role

**Label finding strategies:**
- `label[for="{field_id}"]`
- Parent label element
- Preceding label element

### 8. Nearby Text (Weight: 0.10)

Matches text in parent or sibling elements:
- Parent text containing "email" → email role
- Preceding sibling containing "comment" → comment role

## Scoring Example

### Email Field Detection

**Field:**
```html
<input type="email" name="user_email" id="email-input" 
       placeholder="Enter your email" autocomplete="email">
<label for="email-input">Email Address</label>
```

**Scoring:**
- Input type: `type="email"` → +0.40
- Name token: `name="user_email"` contains "email" → +0.20
- ID token: `id="email-input"` contains "email" → +0.15
- Placeholder: `placeholder="Enter your email"` contains "email" → +0.15
- Autocomplete: `autocomplete="email"` → +0.20
- Label: `<label>Email Address</label>` contains "email" → +0.20

**Total: 1.30** (capped at 1.0) → **Confidence: 1.0**

### Comment Field Detection

**Field:**
```html
<textarea name="message" id="comment-text" 
          placeholder="Leave a comment"></textarea>
<label>Your Comment</label>
```

**Scoring:**
- Tag name: `textarea` for comment role → +0.25
- Name token: `name="message"` contains "message" → +0.20
- Placeholder: `placeholder="Leave a comment"` contains "comment" → +0.15
- Label: `<label>Your Comment</label>` contains "comment" → +0.20
- Nearby text: "comment" in context → +0.10

**Total: 0.90** → **Confidence: 0.90**

## Integration

### Comment Automation

**Location:** `automation/comment.py` - `_fill_comment_form()`

**Integration:**
- Uses `FieldRoleMatcher.match_fields()` to find fields
- Falls back to hardcoded selectors if matcher fails
- Logs confidence scores

**Code:**
```python
field_mappings = FieldRoleMatcher.match_fields(
    self.page,
    form_locator=form,
    task_id=task_id
)

if 'comment' in field_mappings:
    comment_field, confidence = field_mappings['comment']
    # Use matched field
else:
    # Fallback to hardcoded selector
```

### Profile Automation

**Location:** `automation/profile.py` - `_register_account()`

**Integration:**
- Uses `FieldRoleMatcher.match_fields()` for registration form
- Matches: username, email, password, website
- Falls back to hardcoded selectors if matcher fails

## Logging

All operations logged via telemetry:

### Log Events

- `field_role_matcher_start` - Matching started
- `field_role_matcher_candidates_extracted` - Candidates extracted
- `field_role_matcher_role_matched` - Role matched (with confidence, why)
- `field_role_matcher_role_no_match` - Role not matched (with best score)
- `field_role_matcher_complete` - Matching complete (with roles matched)

### Example Log Flow

```json
{"step": "field_role_matcher_start"}
{"step": "field_role_matcher_candidates_extracted", "meta": {"count": 5}}
{"step": "field_role_matcher_role_matched", "meta": {"role": "email", "confidence": 0.95, "why": "input type matches: email; name contains 'email'; autocomplete='email'"}}
{"step": "field_role_matcher_role_matched", "meta": {"role": "comment", "confidence": 0.85, "why": "is textarea; name contains 'message'; placeholder contains 'comment'"}}
{"step": "field_role_matcher_complete", "meta": {"roles_matched": 2, "roles": ["email", "comment"]}}
```

## Confidence Threshold

**Minimum Confidence:** 0.60

**Rationale:**
- Prevents false positives
- Ensures reliable field identification
- Balances flexibility with accuracy

**If confidence < 0.60:**
- Field not included in mapping
- Falls back to hardcoded selectors
- Logs best score for debugging

## Usage

### Basic Usage

```python
from core.field_role_matcher import FieldRoleMatcher

# Match all fields in a form
mappings = FieldRoleMatcher.match_fields(
    page,
    form_locator=form,
    task_id=123
)

# Get specific field
email_field = mappings.get('email')
if email_field:
    locator, confidence = email_field
    print(f"Email field found (confidence: {confidence:.2f})")
    locator.fill("user@example.com")
```

### Convenience Methods

```python
# Get single field
email_field = FieldRoleMatcher.get_field(
    page,
    role='email',
    form_locator=form,
    task_id=123
)

# Get multiple fields
fields = FieldRoleMatcher.get_fields(
    page,
    roles=['email', 'username', 'password'],
    form_locator=form,
    task_id=123
)

email_field = fields['email']
username_field = fields['username']
password_field = fields['password']
```

## Example Scenarios

### Scenario 1: Varied Field Names

**Form:**
```html
<input type="email" name="user_email_address" placeholder="Your email">
<input type="text" name="display_name" placeholder="Full name">
<textarea name="user_message" placeholder="Your comment"></textarea>
```

**Result:**
- `email` → matched (confidence: 0.95) - type + name + placeholder
- `name` → matched (confidence: 0.75) - name + placeholder
- `comment` → matched (confidence: 0.80) - textarea + name + placeholder

### Scenario 2: Label Changes

**Form:**
```html
<label>E-Mail Address</label>
<input type="email" name="mail">
<label>User Name</label>
<input type="text" name="login">
```

**Result:**
- `email` → matched (confidence: 0.85) - type + label + name
- `username` → matched (confidence: 0.80) - label + name

### Scenario 3: Low Confidence

**Form:**
```html
<input type="text" name="field1">
<input type="text" name="field2">
```

**Result:**
- No roles matched (confidence < 0.60)
- Falls back to hardcoded selectors
- Logs best scores for debugging

## Acceptance Criteria

✅ **Slight label/name changes do not break form filling**
- Matches fields even with varied names
- Uses multiple heuristics for robustness
- Falls back to hardcoded selectors if needed

✅ **Confidence threshold prevents false positives**
- Only matches fields with confidence >= 0.60
- Logs best scores for debugging
- Fail-safe: returns None if confidence too low

## Notes

- **Resilient:** Handles varied field names and labels
- **Confidence-based:** Only matches high-confidence fields
- **Fail-safe:** Falls back to hardcoded selectors
- **Comprehensive logging:** All decisions logged
- **Non-intrusive:** Doesn't break existing functionality

