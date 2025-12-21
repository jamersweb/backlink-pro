"""Test API opportunities endpoint"""
import os
import sys
from api_client import LaravelAPIClient

# Set up environment
os.environ['LARAVEL_API_URL'] = 'http://127.0.0.1:8000'
os.environ['LARAVEL_API_TOKEN'] = 'your-secure-api-token-change-in-production'

api_client = LaravelAPIClient(
    base_url=os.getenv('LARAVEL_API_URL', 'http://127.0.0.1:8000'),
    api_token=os.getenv('LARAVEL_API_TOKEN', '')
)

print("Testing API opportunities endpoint...")
print(f"API URL: {api_client.base_url}")
print(f"Campaign ID: 1")

try:
    opportunities = api_client.get_opportunities_for_campaign(
        campaign_id=1,
        count=1,
        task_type='comment'
    )
    
    print(f"\nResponse: {opportunities}")
    print(f"Count: {len(opportunities)}")
    
    if opportunities:
        print(f"\nFirst opportunity:")
        for key, value in opportunities[0].items():
            print(f"  {key}: {value}")
    else:
        print("\nNo opportunities returned!")
        
except Exception as e:
    print(f"\nError: {e}")
    import traceback
    traceback.print_exc()

