# Budget Guard and Domain Memory Implementation

## Overview

Implemented enterprise safety and learning memory per domain to prevent infinite loops and improve reliability over time.

## Components

### 1. Budget Guard (`core/budget_guard.py`)

**Purpose:** Enforces limits to prevent infinite loops and resource exhaustion

**Features:**
- Max total runtime per task (default: 300 seconds)
- Max retries per step (default: 3)
- Max popup dismiss attempts (default: 5)
- Max locator candidates tried (default: 10)
- Raises controlled exception with enum reason when exceeded

**Classes:**
- `BudgetConfig` - Configuration for budget limits
- `BudgetState` - Current budget state for a task
- `BudgetGuard` - Main class for enforcing budgets
- `BudgetExceededException` - Exception raised when budget exceeded
- `BudgetExceededReason` - Enum for budget exceeded reasons

**Usage:**
```python
from core.budget_guard import BudgetGuard, BudgetConfig

# Initialize budget for task
config = BudgetConfig(
    max_runtime_seconds=300,
    max_retries_per_step=3,
    max_popup_dismiss_attempts=5,
    max_locator_candidates=10
)
BudgetGuard.init_task(task_id, config)

# Check budgets at major steps
BudgetGuard.check_runtime(task_id)
BudgetGuard.check_step_retry(task_id, 'lock_task')
BudgetGuard.check_popup_dismiss(task_id)
BudgetGuard.check_locator_candidates(task_id)

# Cleanup
BudgetGuard.cleanup_task(task_id)
```

### 2. Domain Memory (`core/domain_memory.py`)

**Purpose:** Stores per-domain learning and patterns for faster, more reliable automation

**Storage:**
- SQLite database (default: `domain_memory.db`)
- Table: `domain_memory`
- Optional in-memory cache for speed

**Stored Data:**
- `iframe_required` - Boolean, if domain requires iframe navigation
- `recurring_popup_selectors` - List of selectors that work for popups
- `best_locator_strategy` - Dict mapping role -> best strategy
- `login_flow_type` - String (modal/page)
- `always_blocked` - Boolean, if domain is consistently blocked
- `sso_only` - Boolean, if domain requires SSO
- `stats` - Dict with various statistics

**Methods:**
- `get(domain)` - Get domain memory data
- `update(domain, patch)` - Update domain memory
- `increment_stat(domain, key, amount)` - Increment statistic
- `record_iframe_used(domain, success)` - Record iframe usage
- `record_popup_cleared(domain, selector, success)` - Record popup clear
- `record_locator_strategy(domain, role, strategy, success)` - Record locator strategy
- `record_login_flow(domain, flow_type)` - Record login flow type
- `record_failure(domain, failure_type)` - Record failure
- `should_skip(domain)` - Check if domain should be skipped

**Usage:**
```python
from core.domain_memory import get_domain_memory

domain_memory = get_domain_memory()

# Get domain data
data = domain_memory.get('example.com')

# Update domain data
domain_memory.update('example.com', {
    'iframe_required': True,
    'login_flow_type': 'modal'
})

# Record events
domain_memory.record_iframe_used('example.com', True)
domain_memory.record_popup_cleared('example.com', 'button.close', True)
domain_memory.record_locator_strategy('example.com', 'button', 'get_by_role', True)

# Check if should skip
should_skip, reason = domain_memory.should_skip('example.com')
```

## Integration

### Worker Integration

**Location:** `worker.py`

**Changes:**
1. Initialize budget guard at task start
2. Check runtime budget at major steps
3. Check step retry budget before locking task
4. Extract domain from opportunity URL
5. Check if domain should be skipped
6. Record success/failure in domain memory
7. Handle BudgetExceededException
8. Cleanup budget guard in finally block

**Code:**
```python
# Initialize budget guard
budget_config = BudgetConfig(...)
BudgetGuard.init_task(task_id, budget_config)

# Check runtime budget
BudgetGuard.check_runtime(task_id)

# Check step retry budget
BudgetGuard.check_step_retry(task_id, 'lock_task')

# Check domain skip
if domain:
    should_skip, skip_reason = domain_memory.should_skip(domain)
    if should_skip:
        return  # Skip task

# Record in domain memory
if domain:
    if result.get('success'):
        domain_memory.increment_stat(domain, 'successes', 1)
    else:
        domain_memory.record_failure(domain, failure_reason)
```

### Popup Controller Integration

**Location:** `core/popup_controller.py`

**Changes:**
1. Check popup dismiss budget before attempts
2. Use recurring popup selectors from domain memory
3. Record successful popup clears in domain memory

**Code:**
```python
# Check budget
BudgetGuard.check_popup_dismiss(task_id)

# Use recurring selectors first
if domain:
    domain_data = domain_memory.get(domain)
    recurring_selectors = domain_data.get('recurring_popup_selectors', [])

# Record success
if domain:
    domain_memory.record_popup_cleared(domain, selector, True)
```

### Locator Engine Integration

**Location:** `core/locator_engine.py`

**Changes:**
1. Check locator candidates budget
2. Use best locator strategy from domain memory
3. Reorder candidates to prioritize best strategy
4. Record successful strategies in domain memory

**Code:**
```python
# Check budget
BudgetGuard.check_locator_candidates(task_id)

# Use best strategy from domain memory
if domain:
    domain_data = domain_memory.get(domain)
    best_strategies = domain_data.get('best_locator_strategy', {})
    best_strategy = best_strategies.get(target_role)
    # Reorder candidates to prioritize best strategy

# Record success
if domain:
    domain_memory.record_locator_strategy(domain, target_role, strategy, True)
```

### Iframe Router Integration

**Location:** `core/iframe_router.py`

**Changes:**
1. Record iframe usage in domain memory when iframe is used

**Code:**
```python
# Record iframe usage
if domain:
    domain_memory.record_iframe_used(domain, True)
```

## Acceptance Criteria

✅ **No infinite loops**
- Budget guard enforces limits at all major steps
- Controlled exceptions prevent infinite retries
- Runtime limits prevent hanging tasks

✅ **Repeat runs on same domain become faster and more reliable**
- Domain memory stores learned patterns
- Recurring popup selectors tried first
- Best locator strategies prioritized
- Iframe requirements remembered
- Skip flags prevent wasted attempts

## Configuration

**Environment Variables:**
- `MAX_TASK_RUNTIME_SECONDS` - Max runtime per task (default: 300)
- `MAX_RETRIES_PER_STEP` - Max retries per step (default: 3)
- `MAX_POPUP_DISMISS_ATTEMPTS` - Max popup dismiss attempts (default: 5)
- `MAX_LOCATOR_CANDIDATES` - Max locator candidates (default: 10)

**Database:**
- Default path: `domain_memory.db`
- Can be configured via `get_domain_memory(db_path=...)`
- Cache enabled by default

## Example Flow

1. **Task Start:**
   - Initialize budget guard
   - Extract domain from URL
   - Check if domain should be skipped

2. **During Execution:**
   - Check runtime budget at major steps
   - Use learned patterns from domain memory
   - Record successful patterns

3. **On Success:**
   - Record success in domain memory
   - Update statistics

4. **On Failure:**
   - Record failure in domain memory
   - Check if should set skip flags
   - Update statistics

5. **Task End:**
   - Cleanup budget guard
   - Finalize telemetry

## Benefits

1. **Safety:** Prevents infinite loops and resource exhaustion
2. **Learning:** Improves reliability over time
3. **Speed:** Faster execution on repeat runs
4. **Efficiency:** Skips known problematic domains
5. **Observability:** All decisions logged via telemetry

