# Runtime Agent Implementation

## Overview

Implemented a goal-oriented runtime agent that decides next steps like a human, with subgoals and recovery mechanisms.

## Components

### 1. Runtime Agent (`runtime/agent.py`)

**Purpose:** Goal-oriented agent that decides next steps like a human

**Subgoals:**
- `OPEN_COMMENT_EDITOR` - Open comment form/editor
- `SUBMIT_COMMENT` - Submit the comment
- `GO_TO_LOGIN` - Navigate to login page
- `REGISTER_ACCOUNT` - Register new account
- `VERIFY_EMAIL` - Detect email verification requirement (mark pending, don't automate)
- `RETURN_AND_SUBMIT` - Return to page and submit
- `ABORT_SKIP_DOMAIN` - Abort and skip domain

**Features:**
- Uses StateDetector, PopupController, LocatorEngine, FieldRoleMatcher, DomainMemory, BudgetGuard
- Keeps history of tried steps to avoid loops
- Structured logging via telemetry
- Safe stopping with budget checks

**Flow Examples:**

**Comment Flow:**
1. Clear popups
2. Open comment editor (find form)
3. Fill form (delegated to automation module)
4. Submit comment

**Profile Flow:**
1. Clear popups
2. Check if login required → navigate to login
3. Register account (delegated to automation module)
4. Check for email verification → mark pending

### 2. Runtime Healer (`runtime/healer.py`)

**Purpose:** Attempts recovery on failures

**Recovery Strategies:**
- **Popup blocking** → Clear popups and retry
- **Iframe missed** → Route to iframe and retry
- **Element not found** → Use LocatorEngine fallback
- **Other failures** → Fail fast

**Features:**
- Budget-aware (checks before healing)
- Context-aware (uses failure context)
- Structured logging

## Integration

### Worker Integration

**Location:** `worker.py`

**Changes:**
1. Create RuntimeAgent before automation execution
2. Agent prepares the page (clears popups, finds forms)
3. Delegate form filling to automation module
4. Use RuntimeHealer on failures
5. Retry with healed context if healing succeeds

**Code Flow:**
```python
# Create agent
agent = RuntimeAgent(
    task_id=task_id,
    page=automation.page,
    domain=domain,
    goal=task_type
)

# Execute agent flow
agent_result = agent.execute()

# If agent succeeded, delegate to automation
if agent_result.get('success'):
    result = automation.execute(task)
    
    # On failure, try healing
    if not result.get('success'):
        healer = RuntimeHealer(task_id, automation.page, domain)
        heal_result = healer.heal(
            result.get('failure_reason'),
            context=result.get('context', {})
        )
        
        # Retry if healing succeeded
        if heal_result.get('success'):
            result = automation.execute(task)
```

## Agent State Management

**AgentState:**
- `task_id` - Task ID
- `page` - Playwright Page object
- `domain` - Domain name
- `current_url` - Current page URL
- `goal` - Main goal (comment, profile, forum, guest)
- `subgoal` - Current subgoal
- `history` - List of tried actions
- `context` - Additional context
- `success` - Success flag
- `failure_reason` - Failure reason if failed

**History Tracking:**
- Prevents infinite loops
- Tracks try counts per action
- Limits retries (e.g., max 3 tries for comment editor)

## Healer Recovery Examples

### Example 1: Popup Blocking

**Failure:** `POPUP_BLOCKING`

**Recovery:**
1. Analyze page state
2. Clear popups using PopupController
3. Return success with new context

**Result:** Popup cleared, can retry action

### Example 2: Iframe Missed

**Failure:** `IFRAME_MISSED`

**Context:** `{'selector': 'form#comment', 'description': 'comment_form'}`

**Recovery:**
1. Use IframeRouter to find element in iframes
2. Return locator and frame context

**Result:** Element found in iframe, can retry action

### Example 3: Element Not Found

**Failure:** `ELEMENT_NOT_FOUND`

**Context:** `{'target_role': 'button', 'keywords': ['submit', 'post']}`

**Recovery:**
1. Use LocatorEngine to find element
2. Return locator with strategy and confidence

**Result:** Element found via LocatorEngine, can retry action

## Acceptance Criteria

✅ **Agent handles popup/login/iframe variations better than old flow**
- Uses StateDetector to analyze page state
- Uses PopupController to clear popups
- Uses LocatorEngine to find elements
- Uses IframeRouter for iframe navigation
- Uses DomainMemory for learned patterns

✅ **Produces structured logs and stops safely**
- All actions logged via telemetry
- Budget checks prevent infinite loops
- History tracking prevents retry loops
- Safe stopping with proper error handling

## Benefits

1. **Better Handling:** Agent adapts to page variations
2. **Recovery:** Healer attempts recovery on failures
3. **Learning:** Uses DomainMemory for learned patterns
4. **Safety:** Budget checks and history tracking prevent loops
5. **Observability:** Structured logging for all decisions

## Example Agent Flow

### Comment Task

1. **Agent Start:**
   - Check domain skip
   - Initialize agent state

2. **Clear Popups:**
   - Analyze page state
   - Clear popups if needed
   - Record in history

3. **Open Comment Editor:**
   - Use LocatorEngine to find comment form
   - Try multiple strategies
   - Record in history

4. **Delegate to Automation:**
   - Agent has prepared page
   - Automation module fills form

5. **Submit Comment:**
   - Use LocatorEngine to find submit button
   - Click submit
   - Record success

### Profile Task with Login

1. **Agent Start:**
   - Check domain skip
   - Initialize agent state

2. **Clear Popups:**
   - Analyze and clear popups

3. **Check Login Required:**
   - Analyze page state
   - If login required, navigate to login

4. **Register Account:**
   - Delegate to automation module

5. **Check Email Verification:**
   - Analyze page state
   - If verification required, mark pending
   - Return with pending_verification flag

## Logging

All agent actions logged via telemetry:

- `agent_execute_start` - Agent execution started
- `agent_comment_flow_start` - Comment flow started
- `agent_popup_cleared` - Popup cleared
- `agent_comment_editor_found` - Comment editor found
- `agent_comment_submitted` - Comment submitted
- `agent_failed` - Agent failed
- `healer_attempt_start` - Healer attempt started
- `healer_popup_blocking_success` - Popup blocking healed
- `healer_iframe_missed_success` - Iframe missed healed
- `healer_element_not_found_success` - Element not found healed

## Notes

- **Delegation:** Agent prepares page, automation module fills forms
- **Recovery:** Healer attempts recovery before final failure
- **Learning:** Uses DomainMemory for learned patterns
- **Safety:** Budget checks and history tracking prevent loops
- **Observability:** All decisions logged via telemetry

