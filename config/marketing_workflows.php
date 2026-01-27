<?php

return [
    'items' => [
        [
            'slug' => 'comment',
            'title' => 'Comment Workflow',
            'excerpt' => 'Engagement-based placements with strict relevance and approvals to avoid spam.',
            'bestFor' => ['Content clusters', 'Brand signals', 'Early-stage authority support'],
            'notFor' => ['Spam volume', 'Exact-match anchor targeting'],
            'safetyProfile' => [
                'risk' => 'Low–Medium',
                'moderationVariance' => 'High (depends on site moderation)',
                'timeToFirstSignals' => '1–3 weeks',
            ],
            'steps' => [
                ['title' => 'Discover targets', 'desc' => 'Find relevant pages where commenting is appropriate.'],
                ['title' => 'Score + filter', 'desc' => 'Apply relevance + risk thresholds; exclude spam patterns.'],
                ['title' => 'Draft comment', 'desc' => 'Use a natural, value-add comment template.'],
                ['title' => 'Approval gate', 'desc' => 'Approve medium+ risk comments before posting.'],
                ['title' => 'Execute + evidence', 'desc' => 'Log action with URL + proof placeholders + timestamp.'],
                ['title' => 'Monitor', 'desc' => 'Track pending/live/lost status (placeholder if not shipped).'],
            ],
            'guardrails' => [
                'bullets' => [
                    'Relevance must be high; no generic comments',
                    'Velocity cap per domain/day',
                    'Anchor rules: mostly brand/naked URLs',
                    'Blacklist spam footprints',
                ],
            ],
            'templates' => [
                ['name' => 'Value-add reply', 'snippet' => 'Short insight + question + optional brand mention (no hard sell).'],
                ['name' => 'Correction/clarification', 'snippet' => 'Polite correction + cite + invite discussion.'],
            ],
            'evidencePack' => [
                'fields' => ['Target URL', 'Comment text', 'Timestamp', 'Outcome status', 'Proof placeholder'],
            ],
            'cta' => [
                'primary' => ['label' => 'Generate my plan', 'href' => '/free-backlink-plan'],
                'secondary' => ['label' => 'View pricing', 'href' => '/pricing'],
            ],
            'seo' => [
                'title' => 'Comment Backlink Workflow — BacklinkPro',
                'description' => 'A guardrailed comment workflow with approvals and evidence logging. Outcomes vary by moderation.',
            ],
        ],
        [
            'slug' => 'profile',
            'title' => 'Profile Workflow',
            'excerpt' => 'Controlled brand placements on relevant platforms with strong spam avoidance.',
            'bestFor' => ['Brand/naked link signals', 'Low-risk baseline links', 'Diversification'],
            'notFor' => ['Aggressive money anchors', 'High velocity spikes'],
            'safetyProfile' => [
                'risk' => 'Low',
                'moderationVariance' => 'Medium',
                'timeToFirstSignals' => '1–2 weeks',
            ],
            'steps' => [
                ['title' => 'Discover platforms', 'desc' => 'Find relevant, legitimate communities/directories.'],
                ['title' => 'Score + verify', 'desc' => 'Check platform quality; avoid footprints.'],
                ['title' => 'Create profile', 'desc' => 'Complete profile with consistent brand info.'],
                ['title' => 'Approval gate', 'desc' => 'Approve platform + copy before publishing.'],
                ['title' => 'Evidence log', 'desc' => 'Store profile URL + screenshot placeholder + timestamp.'],
                ['title' => 'Monitor', 'desc' => 'Track live/lost (placeholder).'],
            ],
            'guardrails' => [
                'bullets' => [
                    'Whitelist allowed platforms',
                    'Uniform brand data (name, site, socials)',
                    'Velocity cap by platform category',
                    'No keyword-stuffed bios',
                ],
            ],
            'templates' => [
                ['name' => 'Brand bio', 'snippet' => 'Clear description + value proposition + neutral link mention.'],
                ['name' => 'Founder/Team bio', 'snippet' => 'Short professional summary + brand connection.'],
            ],
            'evidencePack' => [
                'fields' => ['Profile URL', 'Bio text', 'Timestamp', 'Platform name', 'Proof placeholder'],
            ],
            'cta' => [
                'primary' => ['label' => 'Generate my plan', 'href' => '/free-backlink-plan'],
                'secondary' => ['label' => 'Security & trust', 'href' => '/security'],
            ],
            'seo' => [
                'title' => 'Profile Workflow — BacklinkPro',
                'description' => 'A low-risk profile workflow with strict platform filtering and evidence logs.',
            ],
        ],
        [
            'slug' => 'forum',
            'title' => 'Forum Workflow',
            'excerpt' => 'Contextual discussions with higher moderation variance—best when relevance is strong.',
            'bestFor' => ['Expert positioning', 'Contextual mentions', 'Community-driven discovery'],
            'notFor' => ['Fast wins', 'High automation without review'],
            'safetyProfile' => [
                'risk' => 'Medium',
                'moderationVariance' => 'High',
                'timeToFirstSignals' => '2–6 weeks',
            ],
            'steps' => [
                ['title' => 'Identify communities', 'desc' => 'Select forums where your niche is active.'],
                ['title' => 'Account warm-up', 'desc' => 'Build trust: participate before linking.'],
                ['title' => 'Thread selection', 'desc' => 'Pick threads where your input is relevant.'],
                ['title' => 'Draft response', 'desc' => 'High value response; link only if truly helpful.'],
                ['title' => 'Approval gate', 'desc' => 'Approve link usage + anchor text rules.'],
                ['title' => 'Evidence + monitoring', 'desc' => 'Log thread URL + proof placeholder; monitor status.'],
            ],
            'guardrails' => [
                'bullets' => [
                    'Warm-up requirement before link attempts',
                    'Strict relevance gating',
                    'Velocity cap + per-community limits',
                    'No exact-match anchors unless explicitly approved',
                ],
            ],
            'templates' => [
                ['name' => 'Helpful answer', 'snippet' => 'Step-by-step answer + optional resource link at end.'],
                ['name' => 'Case example', 'snippet' => 'Share experience + lessons + optional brand mention.'],
            ],
            'evidencePack' => [
                'fields' => ['Thread URL', 'Reply text', 'Timestamp', 'Moderator outcome', 'Proof placeholder'],
            ],
            'cta' => [
                'primary' => ['label' => 'Generate my plan', 'href' => '/free-backlink-plan'],
                'secondary' => ['label' => 'View case studies', 'href' => '/case-studies'],
            ],
            'seo' => [
                'title' => 'Forum Workflow — BacklinkPro',
                'description' => 'A context-first forum workflow with approvals, warm-up steps, and evidence logging.',
            ],
        ],
        [
            'slug' => 'guest',
            'title' => 'Guest Workflow',
            'excerpt' => 'Higher-effort placements with clearer governance and longer timelines.',
            'bestFor' => ['Authority growth', 'Topical relevance', 'Strategic placements'],
            'notFor' => ['Cheap volume', 'Short timelines'],
            'safetyProfile' => [
                'risk' => 'Low–Medium',
                'moderationVariance' => 'Medium',
                'timeToFirstSignals' => '4–12 weeks',
            ],
            'steps' => [
                ['title' => 'Prospect list', 'desc' => 'Find relevant sites with editorial standards.'],
                ['title' => 'Qualification', 'desc' => 'Filter by relevance, quality signals, and footprint checks.'],
                ['title' => 'Pitch drafting', 'desc' => 'Use safe outreach templates (no spam).'],
                ['title' => 'Approval gate', 'desc' => 'Approve pitch + target list before sending.'],
                ['title' => 'Content + placement', 'desc' => 'Draft content; record agreed placement details.'],
                ['title' => 'Evidence + monitoring', 'desc' => 'Log published URL + proof placeholder; monitor changes.'],
            ],
            'guardrails' => [
                'bullets' => [
                    'Whitelist editorial-quality targets',
                    'Anchor rules: brand/partial preferred',
                    'Rate limits on outreach',
                    'Manual approval required by default',
                ],
            ],
            'templates' => [
                ['name' => 'Short pitch', 'snippet' => 'Personalized opener + 2 topic ideas + credibility proof.'],
                ['name' => 'Follow-up', 'snippet' => 'Polite follow-up with one new topic option.'],
            ],
            'evidencePack' => [
                'fields' => ['Published URL', 'Pitch log', 'Timestamp', 'Notes', 'Proof placeholder'],
            ],
            'cta' => [
                'primary' => ['label' => 'Talk to sales', 'href' => '/contact'],
                'secondary' => ['label' => 'Security & trust', 'href' => '/security'],
            ],
            'seo' => [
                'title' => 'Guest Posting Workflow — BacklinkPro',
                'description' => 'A higher-effort guest workflow with approvals, target qualification, and evidence logging.',
            ],
        ],
    ],

    'comparison' => [
        'columns' => [
            ['key' => 'workflow', 'label' => 'Workflow'],
            ['key' => 'risk', 'label' => 'Risk'],
            ['key' => 'time', 'label' => 'Time to signals'],
            ['key' => 'variance', 'label' => 'Moderation variance'],
            ['key' => 'bestFor', 'label' => 'Best for'],
        ],
    ],

    'faqs' => [
        [
            'q' => 'Do you guarantee backlinks with these workflows?',
            'a' => 'No. Outcomes vary by niche and moderation. We provide guardrails, approvals, evidence logs, and monitoring transparency where applicable.',
        ],
        [
            'q' => 'Which workflow should I start with?',
            'a' => 'Start with lower-risk workflows (Profile/Comment) and ramp based on evidence quality and approvals. Use the Free Plan generator for a tailored mix.',
        ],
        [
            'q' => 'What is logged as evidence?',
            'a' => 'Action logs include target URL, content/pitch text, timestamp, and proof placeholders (screenshot/snippet) depending on workflow.',
        ],
        [
            'q' => 'Can I disable a workflow?',
            'a' => 'Yes—your plan should allow enabling/disabling workflows per project rules.',
        ],
    ],

    'disclosures' => [
        'Actions are not guaranteed links. Outcomes vary by niche, authority, and moderation.',
        'Templates are examples; ensure compliance with platform rules and your internal policies.',
        'Label monitoring/export items as placeholder until shipped.',
    ],
];
