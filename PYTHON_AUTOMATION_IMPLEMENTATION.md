# Python Automation Implementation - Complete

## Overview
The Python automation worker has been updated to use the new global backlink opportunity pool system. Instead of using campaign-specific target URLs, the automation now selects opportunities from a global pool based on campaign category, plan PA/DA limits, and daily limits.

## Implementation Details

### 1. API Endpoint (`app/Http/Controllers/Api/OpportunityController.php`)
- **Endpoint**: `GET /api/opportunities/for-campaign/{campaign_id}`
- **Parameters**:
  - `count` (optional): Number of opportunities to return (default: 1)
  - `task_type` (optional): Type of task (comment, profile, forum, guest)
  - `site_type` (optional): Filter by site type
- **Logic**:
  - Gets campaign category and subcategory
  - Retrieves user's plan PA/DA limits
  - Filters opportunities by:
    - Category match (category_id or subcategory_id)
    - PA/DA within plan limits
    - Status = 'active'
    - Daily limits (campaign + site)
  - Prioritizes higher PA+DA but adds randomization
  - Returns opportunities with metadata

### 2. Python API Client (`python/api_client.py`)
- **New Method**: `get_opportunities_for_campaign()`
  - Fetches opportunities from Laravel API
  - Handles parameters and response parsing
- **Updated Method**: `create_backlink()`
  - Now accepts `backlink_opportunity_id` parameter
  - Links created backlinks to opportunities

### 3. Opportunity Selector (`python/opportunity_selector.py`)
- **Class**: `OpportunitySelector`
- **Methods**:
  - `select_opportunity()`: Get single opportunity
  - `select_opportunities()`: Get multiple opportunities
  - `get_opportunity_url()`: Convenience method for URL only
  - `get_opportunity_urls()`: Convenience method for URLs list

### 4. Updated Automation Classes

#### Base Automation (`python/automation/base.py`)
- Added `opportunity_selector` instance
- Available to all automation classes

#### Comment Automation (`python/automation/comment.py`)
- Uses opportunity selector if available
- Falls back to payload `target_urls` if no opportunities
- Includes `backlink_opportunity_id` in result

#### Profile Automation (`python/automation/profile.py`)
- Uses opportunity selector if available
- Falls back to payload `target_urls` if no opportunities
- Includes `backlink_opportunity_id` in result

#### Forum Automation (`python/automation/forum.py`)
- Uses opportunity selector if available
- Falls back to payload `target_urls` if no opportunities
- Includes `backlink_opportunity_id` in result

#### Guest Post Automation (`python/automation/guest.py`)
- Uses opportunity selector if available
- Falls back to payload `target_urls` if no opportunities
- Includes `backlink_opportunity_id` in result

### 5. Worker Updates (`python/worker.py`)
- Updated `process_task()` to pass `backlink_opportunity_id` when creating backlinks
- Maintains backward compatibility with existing task structure

## How It Works

### Selection Flow
1. **Campaign Processing**: Worker receives task with `campaign_id`
2. **Opportunity Selection**: Automation class calls `opportunity_selector.select_opportunity()`
3. **API Request**: Selector calls Laravel API endpoint
4. **Filtering**: Laravel filters opportunities by:
   - Campaign category/subcategory
   - Plan PA/DA limits
   - Daily limits (campaign + site)
   - Status = active
5. **Prioritization**: Higher PA+DA prioritized, but randomization added
6. **Execution**: Automation uses selected opportunity URL
7. **Backlink Creation**: Created backlink includes `backlink_opportunity_id`

### Daily Limit Logic
- **Campaign Daily Limit**: Maximum backlinks per campaign per day
- **Site Daily Limit**: Maximum backlinks per opportunity per day
- **Campaign-Site Limit**: Maximum uses of same opportunity by same campaign per day (default: 1)
- All limits are checked before returning opportunities

### Fallback Behavior
- If no opportunities found, automation falls back to `payload.target_urls`
- This maintains backward compatibility with existing tasks
- Logs warning when falling back

## Usage Example

```python
from api_client import LaravelAPIClient
from opportunity_selector import OpportunitySelector

# Initialize
api_client = LaravelAPIClient('http://app:8000', 'api_token')
selector = OpportunitySelector(api_client)

# Select opportunity
opportunity = selector.select_opportunity(
    campaign_id=1,
    task_type='comment'
)

if opportunity:
    url = opportunity['url']
    pa = opportunity['pa']
    da = opportunity['da']
    opportunity_id = opportunity['id']
```

## Benefits

1. **Scalability**: Can handle millions of opportunities
2. **Smart Selection**: Automatically filters by category and plan limits
3. **Limit Management**: Respects daily limits at multiple levels
4. **Quality Control**: Prioritizes higher PA/DA sites
5. **Flexibility**: Supports multiple categories per opportunity
6. **Backward Compatible**: Falls back to old system if needed

## Testing

To test the implementation:

1. **Create Opportunities**: Import opportunities via admin panel CSV
2. **Set Campaign Category**: Ensure campaign has category/subcategory selected
3. **Set Plan Limits**: Configure plan PA/DA limits
4. **Run Automation**: Python worker will automatically use opportunities
5. **Verify**: Check backlinks table for `backlink_opportunity_id` values

## API Response Format

```json
{
  "success": true,
  "opportunities": [
    {
      "id": 1,
      "url": "https://example.com",
      "pa": 45,
      "da": 50,
      "site_type": "comment",
      "daily_site_limit": 5,
      "categories": [1, 2]
    }
  ],
  "campaign": {
    "id": 1,
    "category_id": 1,
    "subcategory_id": 2
  },
  "plan_limits": {
    "min_pa": 0,
    "max_pa": 40,
    "min_da": 0,
    "max_da": 50
  }
}
```

## Notes

- Opportunities are selected per task, not per campaign batch
- Each automation class handles opportunity selection independently
- The system maintains a balance between quality (high PA/DA) and diversity (randomization)
- Daily limits are checked in real-time, ensuring fair distribution

