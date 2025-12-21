# Refactor: Swap Backlinks and Opportunities Logic

## Current (Wrong) Logic:
- **`backlink_opportunities`** = Global pool of sites
- **`backlinks`** = Campaign-specific created links

## Desired (Correct) Logic:
- **`backlinks`** = Global store/pool of millions of sites (admin-managed catalog)
- **`backlink_opportunities`** = Campaign-specific selections showing where user's link was added

---

## Migration Plan

### Step 1: Database Structure Changes

#### A. Modify `backlinks` table (make it the global store):
```sql
-- Remove campaign-specific fields
ALTER TABLE backlinks DROP FOREIGN KEY backlinks_campaign_id_foreign;
ALTER TABLE backlinks DROP COLUMN campaign_id;
ALTER TABLE backlinks DROP COLUMN site_account_id;
ALTER TABLE backlinks DROP COLUMN keyword;
ALTER TABLE backlinks DROP COLUMN anchor_text;
ALTER TABLE backlinks DROP COLUMN status;
ALTER TABLE backlinks DROP COLUMN verified_at;
ALTER TABLE backlinks DROP COLUMN error_message;

-- Add global store fields
ALTER TABLE backlinks ADD COLUMN pa TINYINT UNSIGNED NULL COMMENT 'Page Authority 0-100';
ALTER TABLE backlinks ADD COLUMN da TINYINT UNSIGNED NULL COMMENT 'Domain Authority 0-100';
ALTER TABLE backlinks ADD COLUMN site_type ENUM('comment', 'profile', 'forum', 'guestposting', 'other') DEFAULT 'comment';
ALTER TABLE backlinks ADD COLUMN status ENUM('active', 'inactive', 'banned') DEFAULT 'active';
ALTER TABLE backlinks ADD COLUMN daily_site_limit INT UNSIGNED NULL COMMENT 'Max links per day from this site';
ALTER TABLE backlinks ADD COLUMN metadata JSON NULL;
ALTER TABLE backlinks MODIFY COLUMN url VARCHAR(255) UNIQUE;

-- Create pivot table for categories
CREATE TABLE backlink_category (
    backlink_id BIGINT UNSIGNED,
    category_id BIGINT UNSIGNED,
    PRIMARY KEY (backlink_id, category_id),
    FOREIGN KEY (backlink_id) REFERENCES backlinks(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);
```

#### B. Modify `backlink_opportunities` table (make it campaign-specific):
```sql
-- Remove global pool fields
ALTER TABLE backlink_opportunities DROP COLUMN url;
ALTER TABLE backlink_opportunities DROP COLUMN pa;
ALTER TABLE backlink_opportunities DROP COLUMN da;
ALTER TABLE backlink_opportunities DROP COLUMN site_type;
ALTER TABLE backlink_opportunities DROP COLUMN status;
ALTER TABLE backlink_opportunities DROP COLUMN daily_site_limit;
ALTER TABLE backlink_opportunities DROP COLUMN metadata;

-- Add campaign-specific fields
ALTER TABLE backlink_opportunities ADD COLUMN campaign_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE backlink_opportunities ADD COLUMN backlink_id BIGINT UNSIGNED NOT NULL COMMENT 'Reference to backlinks store';
ALTER TABLE backlink_opportunities ADD COLUMN site_account_id BIGINT UNSIGNED NULL;
ALTER TABLE backlink_opportunities ADD COLUMN keyword VARCHAR(255) NULL;
ALTER TABLE backlink_opportunities ADD COLUMN anchor_text VARCHAR(255) NULL;
ALTER TABLE backlink_opportunities ADD COLUMN status ENUM('pending', 'submitted', 'verified', 'error') DEFAULT 'pending';
ALTER TABLE backlink_opportunities ADD COLUMN verified_at TIMESTAMP NULL;
ALTER TABLE backlink_opportunities ADD COLUMN error_message TEXT NULL;
ALTER TABLE backlink_opportunities ADD COLUMN url VARCHAR(255) NULL COMMENT 'Actual backlink URL (may differ from backlink.url)';

-- Add foreign keys
ALTER TABLE backlink_opportunities ADD FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE;
ALTER TABLE backlink_opportunities ADD FOREIGN KEY (backlink_id) REFERENCES backlinks(id) ON DELETE CASCADE;
ALTER TABLE backlink_opportunities ADD FOREIGN KEY (site_account_id) REFERENCES site_accounts(id) ON DELETE SET NULL;

-- Drop old pivot table
DROP TABLE IF EXISTS backlink_opportunity_category;
```

---

### Step 2: Model Updates

#### A. Update `Backlink` model (global store):
```php
// Remove campaign relationship
// Add categories relationship (many-to-many)
// Remove opportunity relationship
// Add opportunities relationship (hasMany)
```

#### B. Update `BacklinkOpportunity` model (campaign-specific):
```php
// Add campaign relationship (belongsTo)
// Add backlink relationship (belongsTo) - references the store
// Remove categories relationship
// Add siteAccount relationship
```

---

### Step 3: API Changes

#### A. Update `OpportunityController`:
- Change endpoint to select from `backlinks` table (the store)
- Filter by categories, PA/DA, plan limits
- Return backlink IDs from the store

#### B. Update `BacklinkController` API:
- When Python worker creates a backlink, it should:
  1. Select a backlink from the `backlinks` store
  2. Create an entry in `backlink_opportunities` with:
     - `campaign_id`
     - `backlink_id` (reference to store)
     - `url` (actual backlink URL)
     - `status`, `keyword`, `anchor_text`, etc.

---

### Step 4: Python Automation Changes

#### A. Update `api_client.py`:
- `get_opportunities_for_campaign()` → Should fetch from `backlinks` store
- `create_backlink()` → Should create `backlink_opportunity` entry

#### B. Update automation classes:
- Select from `backlinks` store
- Create `backlink_opportunities` entries when links are created

---

### Step 5: Admin Panel Changes

#### A. Backlinks Management (`/admin/backlinks`):
- Purpose: Manage the global store of sites
- Features:
  - Add single site
  - Bulk import sites (CSV)
  - Edit site details (PA, DA, categories, status)
  - Filter by category, PA/DA, site type, status
  - Export sites

#### B. Opportunities Management (`/admin/backlink-opportunities`):
- Purpose: View/manage campaign-specific opportunities
- Features:
  - Filter by campaign
  - View where user links were added
  - Status tracking (pending/submitted/verified/error)
  - Verification management

---

### Step 6: User-Facing Changes

#### A. User Dashboard:
- Show "Opportunities" instead of "Backlinks"
- Opportunities = Where user's links were added

#### B. User Backlinks Page (`/backlinks`):
- Rename to "Opportunities" or "My Backlinks"
- Show `backlink_opportunities` filtered by user's campaigns
- Display: URL, campaign, status, verification status, date added

---

### Step 7: Data Migration

#### A. Migrate existing data:
1. Move `backlink_opportunities` data to `backlinks` table (the store)
2. Create `backlink_opportunities` entries from existing `backlinks` data
3. Map categories from old pivot table to new one

---

## Implementation Order:

1. ✅ Create migration files
2. ✅ Update models
3. ✅ Update API controllers
4. ✅ Update Python automation
5. ✅ Update admin controllers/views
6. ✅ Update user-facing views
7. ✅ Run data migration
8. ✅ Test end-to-end

---

## Key Changes Summary:

| Component | Current | New |
|-----------|---------|-----|
| **Global Pool** | `backlink_opportunities` | `backlinks` |
| **Campaign Links** | `backlinks` | `backlink_opportunities` |
| **Admin Adds** | Opportunities | Backlinks (store) |
| **User Sees** | Backlinks | Opportunities |
| **Python Selects** | Opportunities | Backlinks (store) |
| **Python Creates** | Backlinks | Opportunities |

