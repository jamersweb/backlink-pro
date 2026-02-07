<?php

namespace App\Services\Backlinks;

use App\Models\Audit;
use App\Models\BacklinkCampaign;
use App\Models\BacklinkProspect;

class BacklinkStrategyEngine
{
    /**
     * Generate strategy for a campaign
     */
    public function generateStrategy(BacklinkCampaign $campaign, Audit $audit): array
    {
        // Analyze audit to extract themes
        $themes = $this->extractThemes($audit);
        
        // Generate prospect recommendations
        $prospects = $this->generateProspects($campaign, $themes);
        
        // Generate outreach templates
        $templates = $this->generateTemplates($campaign, $audit);
        
        // Create weekly plan
        $weeklyPlan = $this->createWeeklyPlan($prospects);

        return [
            'anchor_mix' => $this->recommendAnchorMix($audit),
            'weekly_plan' => $weeklyPlan,
            'prospects' => $prospects,
            'templates' => $templates,
        ];
    }

    /**
     * Extract content themes from audit
     */
    protected function extractThemes(Audit $audit): array
    {
        $themes = [];
        
        // Extract from page titles and content
        $pages = $audit->pages()->limit(10)->get();
        
        foreach ($pages as $page) {
            if ($page->title) {
                $keywords = $this->extractKeywords($page->title);
                $themes = array_merge($themes, $keywords);
            }
        }

        return array_unique($themes);
    }

    /**
     * Generate prospects (simplified - would use ML in production)
     */
    protected function generateProspects(BacklinkCampaign $campaign, array $themes): array
    {
        $prospects = [];
        
        // Simplified prospect generation
        // In production, would query historical backlink database and rank by ML model
        
        $types = ['directory', 'resource_page', 'partner', 'guest', 'press'];
        
        foreach ($types as $type) {
            for ($i = 0; $i < 5; $i++) {
                $prospect = BacklinkProspect::create([
                    'campaign_id' => $campaign->id,
                    'prospect_url' => "https://example-{$type}-{$i}.com",
                    'domain' => "example-{$type}-{$i}.com",
                    'type' => $type,
                    'relevance_score' => rand(60, 90),
                    'authority_score' => rand(40, 80),
                    'risk_score' => rand(10, 30),
                    'outreach_status' => 'new',
                ]);
                
                $prospects[] = $prospect;
            }
        }

        return $prospects;
    }

    /**
     * Generate outreach templates
     */
    protected function generateTemplates(BacklinkCampaign $campaign, Audit $audit): array
    {
        return [
            'email' => $this->generateEmailTemplate($campaign, $audit),
            'contact_form' => $this->generateContactFormTemplate($campaign, $audit),
        ];
    }

    /**
     * Generate email template
     */
    protected function generateEmailTemplate(BacklinkCampaign $campaign, Audit $audit): string
    {
        $domain = $campaign->target_domain;
        
        return "Subject: Partnership Opportunity - {$domain}\n\n" .
               "Hi,\n\n" .
               "I noticed your site covers [topic]. We've recently published comprehensive content on {$domain} that would be valuable for your readers.\n\n" .
               "Would you be interested in featuring our resource?\n\n" .
               "Best regards";
    }

    /**
     * Generate contact form template
     */
    protected function generateContactFormTemplate(BacklinkCampaign $campaign, Audit $audit): string
    {
        return "I'm reaching out about a potential partnership opportunity. Our site {$campaign->target_domain} has content that aligns with your audience. Would love to discuss collaboration.";
    }

    /**
     * Create weekly plan
     */
    protected function createWeeklyPlan(array $prospects): array
    {
        return [
            'week_1' => array_slice($prospects, 0, 5),
            'week_2' => array_slice($prospects, 5, 5),
            'week_3' => array_slice($prospects, 10, 5),
            'week_4' => array_slice($prospects, 15, 5),
        ];
    }

    /**
     * Recommend anchor mix
     */
    protected function recommendAnchorMix(Audit $audit): array
    {
        return [
            'branded' => 30,
            'exact_match' => 20,
            'partial_match' => 30,
            'generic' => 20,
        ];
    }

    /**
     * Extract keywords from text
     */
    protected function extractKeywords(string $text): array
    {
        // Simplified - would use NLP in production
        $words = str_word_count(strtolower($text), 1);
        return array_filter($words, fn($w) => strlen($w) > 4);
    }
}
