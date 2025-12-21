"""
Example: Shadow Mode Usage

Demonstrates how shadow mode works and how to analyze results
"""

from shadow_mode_logger import ShadowModeLogger, get_shadow_logger
from opportunity_selector import OpportunitySelector
from api_client import LaravelAPIClient
import os

def example_shadow_mode_workflow():
    """Example of shadow mode workflow"""
    print("=" * 70)
    print("Shadow Mode Example Workflow")
    print("=" * 70)
    
    # Enable shadow mode
    os.environ['SHADOW_MODE'] = 'true'
    
    # Initialize
    api_client = LaravelAPIClient('http://nginx', 'your-token')
    selector = OpportunitySelector(api_client, shadow_mode=True)
    shadow_logger = get_shadow_logger()
    
    # Simulate task processing
    task_id = 123
    campaign_id = 1
    task_type = 'comment'  # Rule-based will use this
    
    # Select opportunity (AI predicts, rules execute)
    opportunity = selector.select_opportunity(
        campaign_id=campaign_id,
        task_type=task_type
    )
    
    if opportunity:
        print(f"\nOpportunity selected: {opportunity.get('id')}")
        print(f"Rule-based action: {task_type}")
        print(f"AI predicted action: {opportunity.get('ai_recommended_action_type')}")
        print(f"AI confidence: {opportunity.get('ai_probability', 0):.2%}")
        
        # Log prediction
        ai_prediction = {
            'action': opportunity.get('ai_recommended_action_type'),
            'probability': opportunity.get('ai_probability', 0.5),
            'probabilities': opportunity.get('ai_probabilities', {}),
        }
        
        shadow_logger.log_prediction(
            task_id=task_id,
            campaign_id=campaign_id,
            backlink=opportunity,
            rule_based_action=task_type,
            ai_prediction=ai_prediction
        )
        
        # Simulate execution (rule-based action executes)
        # ... automation runs with task_type='comment' ...
        
        # Log result
        task_result = 'success'  # or 'failed'
        shadow_logger.log_result(
            task_id=task_id,
            rule_based_action=task_type,
            task_result=task_result,
            execution_time=12.45,
            retry_count=0,
            ai_prediction=ai_prediction
        )
        
        print(f"\nTask result: {task_result}")
        print(f"AI correct: {ai_prediction['action'] == task_type}")
    
    # Get accuracy stats
    print("\n" + "=" * 70)
    print("Shadow Mode Accuracy Statistics")
    print("=" * 70)
    
    stats = shadow_logger.get_accuracy_stats()
    if stats:
        print(f"Total tasks: {stats['total_tasks']}")
        print(f"AI correct: {stats['ai_correct_count']} ({stats['ai_correct_rate']:.2%})")
        print(f"AI different: {stats['ai_different_count']} ({stats['ai_different_rate']:.2%})")
        print(f"AI different when rule failed: {stats['ai_different_when_rule_failed']}")
    else:
        print("No statistics available yet")


if __name__ == "__main__":
    try:
        example_shadow_mode_workflow()
    except Exception as e:
        print(f"Error: {e}")
        import traceback
        traceback.print_exc()

