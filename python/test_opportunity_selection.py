"""
Test script for opportunity selection algorithm
Tests the API endpoint and opportunity selector
"""
import os
import sys
from dotenv import load_dotenv

# Add parent directory to path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from api_client import LaravelAPIClient
from opportunity_selector import OpportunitySelector

# Load environment variables
load_dotenv()

# Configuration
LARAVEL_API_URL = os.getenv('LARAVEL_API_URL', 'http://localhost:8000')
LARAVEL_API_TOKEN = os.getenv('LARAVEL_API_TOKEN', '')


def test_opportunity_selection():
    """Test opportunity selection algorithm"""
    print("=" * 60)
    print("Testing Opportunity Selection Algorithm")
    print("=" * 60)
    
    if not LARAVEL_API_TOKEN:
        print("ERROR: LARAVEL_API_TOKEN not set!")
        print("Please set LARAVEL_API_TOKEN in your .env file")
        return False
    
    # Initialize API client
    api_client = LaravelAPIClient(LARAVEL_API_URL, LARAVEL_API_TOKEN)
    selector = OpportunitySelector(api_client)
    
    # Test 1: Get campaign (you'll need to provide a real campaign ID)
    print("\n[Test 1] Testing API Client - Get Campaign")
    print("-" * 60)
    try:
        campaign_id = 1  # Change this to a real campaign ID
        campaign = api_client.get_campaign(campaign_id)
        if campaign:
            print(f"✓ Campaign {campaign_id} found")
            print(f"  Name: {campaign.get('name', 'N/A')}")
            print(f"  Category ID: {campaign.get('category_id', 'N/A')}")
            print(f"  Subcategory ID: {campaign.get('subcategory_id', 'N/A')}")
        else:
            print(f"✗ Campaign {campaign_id} not found")
            print("  Note: Create a campaign with category/subcategory first")
    except Exception as e:
        print(f"✗ Error getting campaign: {e}")
        print("  Note: Make sure Laravel API is running and accessible")
    
    # Test 2: Get opportunities for campaign
    print("\n[Test 2] Testing Opportunity Selection")
    print("-" * 60)
    try:
        campaign_id = 1  # Change this to a real campaign ID
        opportunities = api_client.get_opportunities_for_campaign(
            campaign_id=campaign_id,
            count=3,
            task_type='comment'
        )
        
        if opportunities:
            print(f"✓ Found {len(opportunities)} opportunities")
            for i, opp in enumerate(opportunities, 1):
                print(f"\n  Opportunity {i}:")
                print(f"    ID: {opp.get('id')}")
                print(f"    URL: {opp.get('url')}")
                print(f"    PA: {opp.get('pa')}")
                print(f"    DA: {opp.get('da')}")
                print(f"    Site Type: {opp.get('site_type')}")
                print(f"    Daily Limit: {opp.get('daily_site_limit', 'N/A')}")
                print(f"    Categories: {opp.get('categories', [])}")
        else:
            print("✗ No opportunities found")
            print("  Possible reasons:")
            print("    - Campaign has no category/subcategory")
            print("    - No opportunities match campaign category")
            print("    - No opportunities within plan PA/DA limits")
            print("    - All opportunities reached daily limits")
            print("  Note: Create opportunities via admin panel CSV import")
    except Exception as e:
        print(f"✗ Error getting opportunities: {e}")
        import traceback
        traceback.print_exc()
    
    # Test 3: Test OpportunitySelector class
    print("\n[Test 3] Testing OpportunitySelector Class")
    print("-" * 60)
    try:
        campaign_id = 1  # Change this to a real campaign ID
        opportunity = selector.select_opportunity(
            campaign_id=campaign_id,
            task_type='comment'
        )
        
        if opportunity:
            print("✓ OpportunitySelector.select_opportunity() works")
            print(f"  Selected URL: {opportunity.get('url')}")
            print(f"  PA: {opportunity.get('pa')}, DA: {opportunity.get('da')}")
        else:
            print("✗ No opportunity selected")
            print("  (This is OK if no opportunities match criteria)")
    except Exception as e:
        print(f"✗ Error in OpportunitySelector: {e}")
        import traceback
        traceback.print_exc()
    
    # Test 4: Test multiple opportunities
    print("\n[Test 4] Testing Multiple Opportunity Selection")
    print("-" * 60)
    try:
        campaign_id = 1  # Change this to a real campaign ID
        opportunities = selector.select_opportunities(
            campaign_id=campaign_id,
            count=5,
            task_type='comment'
        )
        
        if opportunities:
            print(f"✓ Selected {len(opportunities)} opportunities")
            print(f"  URLs: {[opp.get('url') for opp in opportunities]}")
        else:
            print("✗ No opportunities selected")
    except Exception as e:
        print(f"✗ Error selecting multiple opportunities: {e}")
    
    print("\n" + "=" * 60)
    print("Test Complete")
    print("=" * 60)
    print("\nNext Steps:")
    print("1. Create a campaign with category/subcategory")
    print("2. Import opportunities via admin panel CSV")
    print("3. Assign categories to opportunities")
    print("4. Set plan PA/DA limits")
    print("5. Run this test again with a real campaign ID")
    
    return True


if __name__ == "__main__":
    test_opportunity_selection()

