# Python Automation Guide - Backlink Opportunity Selection

## Overview

The Python automation worker now selects backlink opportunities from a global pool based on:
1. Campaign category/subcategory
2. User's plan PA/DA limits
3. Daily limits (campaign and per-site)
4. Related categories matching

## Database Structure

### Key Tables

1. **`categories`** - Category hierarchy (parent/subcategory)
2. **`backlink_opportunities`** - Global pool of sites (millions)
3. **`backlink_opportunity_category`** - Many-to-many relationship
4. **`campaigns`** - Contains `category_id` and `subcategory_id`
5. **`plans`** - Contains PA/DA limits: `min_pa`, `max_pa`, `min_da`, `max_da`
6. **`backlinks`** - Actual created backlinks (links to `backlink_opportunity_id`)

## Selection Algorithm

### Step 1: Get Campaign and User Plan

```python
# Get campaign with category info
campaign = get_campaign(campaign_id)
category_id = campaign.category_id
subcategory_id = campaign.subcategory_id

# Get user's plan
user = get_user(campaign.user_id)
plan = get_plan(user.plan_id)

# Plan PA/DA limits
min_pa = plan.min_pa  # e.g., 0
max_pa = plan.max_pa  # e.g., 40 for Basic plan
min_da = plan.min_da  # e.g., 0
max_da = plan.max_da  # e.g., 40 for Basic plan
```

### Step 2: Determine Category Set

```python
# Start with campaign's primary category
category_ids = [category_id]

# Add subcategory if exists
if subcategory_id:
    category_ids.append(subcategory_id)

# Add related categories (you can create a related_categories table or mapping)
# For now, this is a placeholder - implement based on your business logic
related_categories = get_related_categories(category_id)
category_ids.extend(related_categories)

# Remove duplicates
category_ids = list(set(category_ids))
```

### Step 3: Query Opportunities

```python
# Base query
query = """
    SELECT bo.*
    FROM backlink_opportunities bo
    INNER JOIN backlink_opportunity_category boc ON bo.id = boc.backlink_opportunity_id
    WHERE boc.category_id IN ({category_ids})
    AND bo.status = 'active'
    AND bo.pa >= {min_pa} AND bo.pa <= {max_pa}
    AND bo.da >= {min_da} AND bo.da <= {max_da}
    ORDER BY bo.da DESC, bo.pa DESC
    LIMIT {daily_limit}
"""

# Check daily site limits
# You'll need to track usage per site per day
# Create a table: opportunity_usage (opportunity_id, campaign_id, date, count)
# Or check in real-time from backlinks table

# Filter out sites that hit their daily limit
available_opportunities = []
for opp in opportunities:
    daily_usage = get_daily_usage(opp.id, campaign_id, today)
    if opp.daily_site_limit and daily_usage >= opp.daily_site_limit:
        continue  # Skip this site
    available_opportunities.append(opp)
```

### Step 4: Prioritize and Randomize

```python
# Sort by DA/PA (already done in SQL)
# Add some randomization to avoid always using same sites
import random

# Group by DA ranges for randomization
high_da = [o for o in available_opportunities if o.da >= 60]
mid_da = [o for o in available_opportunities if 30 <= o.da < 60]
low_da = [o for o in available_opportunities if o.da < 30]

# Select proportionally (e.g., 70% high/mid, 30% low)
selected = []
selected.extend(random.sample(high_da, min(len(high_da), int(daily_limit * 0.5))))
selected.extend(random.sample(mid_da, min(len(mid_da), int(daily_limit * 0.2))))
selected.extend(random.sample(low_da, min(len(low_da), int(daily_limit * 0.3))))

# Shuffle final selection
random.shuffle(selected)
```

### Step 5: Create Backlinks

```python
for opportunity in selected:
    try:
        # Your automation logic to create backlink on the site
        result = create_backlink_on_site(opportunity.url, campaign)
        
        if result.success:
            # Create backlink record
            backlink_data = {
                'campaign_id': campaign_id,
                'backlink_opportunity_id': opportunity.id,
                'url': result.final_url,  # Actual created URL
                'type': opportunity.site_type,
                'pa': opportunity.pa,
                'da': opportunity.da,
                'status': 'submitted',  # or 'verified' if you can verify immediately
                'keyword': campaign.web_keyword,
                'anchor_text': result.anchor_text,
            }
            
            create_backlink(backlink_data)
            
            # Track usage
            increment_daily_usage(opportunity.id, campaign_id, today)
            
    except Exception as e:
        log_error(opportunity.id, campaign_id, str(e))
        # Optionally mark opportunity as problematic after X failures
```

## API Endpoints

### Get Opportunities for Campaign

```http
GET /api/campaigns/{campaign_id}/opportunities?limit=10
```

**Response:**
```json
{
    "opportunities": [
        {
            "id": 1,
            "url": "https://example.com/page",
            "pa": 45,
            "da": 60,
            "site_type": "comment",
            "categories": ["Business", "SEO"]
        }
    ],
    "plan_limits": {
        "min_pa": 0,
        "max_pa": 40,
        "min_da": 0,
        "max_da": 40
    }
}
```

### Create Backlink

```http
POST /api/backlinks
Headers: X-API-Token: {your_token}
Body: {
    "campaign_id": 1,
    "backlink_opportunity_id": 123,
    "url": "https://example.com/page/comment-123",
    "status": "submitted",
    "keyword": "SEO tools",
    "anchor_text": "best SEO tools"
}
```

## Related Categories Logic

You can implement related categories in several ways:

### Option 1: Database Table

```sql
CREATE TABLE related_categories (
    category_id INT,
    related_category_id INT,
    PRIMARY KEY (category_id, related_category_id)
);
```

### Option 2: Configuration File

```python
RELATED_CATEGORIES = {
    1: [2, 3, 4],  # Business related to Marketing, SEO, Finance
    5: [6, 7],     # Technology related to Software, Hardware
}
```

### Option 3: Category Metadata

Store related categories in `categories.metadata` JSON field.

## Daily Limits Tracking

### Option 1: Real-time Check

```python
def get_daily_usage(opportunity_id, campaign_id, date):
    return Backlink.objects.filter(
        backlink_opportunity_id=opportunity_id,
        campaign_id=campaign_id,
        created_at__date=date
    ).count()
```

### Option 2: Usage Table

```sql
CREATE TABLE opportunity_usage (
    id BIGINT PRIMARY KEY,
    backlink_opportunity_id INT,
    campaign_id INT,
    date DATE,
    count INT DEFAULT 0,
    UNIQUE KEY (backlink_opportunity_id, campaign_id, date)
);
```

## Example Python Code Structure

```python
# python/automation/opportunity_selector.py

class OpportunitySelector:
    def __init__(self, campaign_id):
        self.campaign_id = campaign_id
        self.campaign = self.get_campaign()
        self.plan = self.get_user_plan()
        
    def get_campaign(self):
        # Fetch campaign with category info
        pass
    
    def get_user_plan(self):
        # Fetch user's plan with PA/DA limits
        pass
    
    def get_category_set(self):
        """Get all relevant category IDs"""
        category_ids = [self.campaign.category_id]
        if self.campaign.subcategory_id:
            category_ids.append(self.campaign.subcategory_id)
        # Add related categories
        category_ids.extend(self.get_related_categories(self.campaign.category_id))
        return list(set(category_ids))
    
    def select_opportunities(self, limit=None):
        """Select opportunities matching criteria"""
        category_ids = self.get_category_set()
        daily_limit = limit or self.campaign.daily_limit
        
        # Query opportunities
        opportunities = self.query_opportunities(
            category_ids=category_ids,
            min_pa=self.plan.min_pa,
            max_pa=self.plan.max_pa,
            min_da=self.plan.min_da,
            max_da=self.plan.max_da,
            limit=daily_limit * 2  # Get more for filtering
        )
        
        # Filter by daily limits
        available = self.filter_by_daily_limits(opportunities, daily_limit)
        
        # Prioritize and randomize
        selected = self.prioritize_and_randomize(available, daily_limit)
        
        return selected
    
    def query_opportunities(self, category_ids, min_pa, max_pa, min_da, max_da, limit):
        # SQL query or API call
        pass
    
    def filter_by_daily_limits(self, opportunities, daily_limit):
        # Check each opportunity's daily usage
        filtered = []
        for opp in opportunities:
            if self.check_daily_limit(opp):
                filtered.append(opp)
            if len(filtered) >= daily_limit:
                break
        return filtered
    
    def prioritize_and_randomize(self, opportunities, limit):
        # Sort by DA/PA, then randomize within ranges
        pass
```

## Testing

1. **Test Category Matching**: Create test opportunities with different categories
2. **Test PA/DA Limits**: Verify Basic plan users only get PA/DA <= 40
3. **Test Daily Limits**: Verify sites respect their daily_site_limit
4. **Test Related Categories**: Verify related categories are included

## Notes

- Always prioritize higher PA/DA sites but add randomization
- Track daily usage to respect limits
- Mark opportunities as `banned` if they fail repeatedly
- Log all selections for debugging
- Consider caching category relationships for performance

