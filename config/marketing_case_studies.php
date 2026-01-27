<?php

return [
    'filters' => [
        'segments' => ['SaaS', 'Ecommerce', 'Local', 'Agency'],
        'goals' => ['Authority', 'Rankings', 'Brand', 'Money pages'],
        'riskModes' => ['Conservative', 'Balanced', 'Growth'],
    ],

    'items' => [
        [
            'slug' => 'saas-integration-pages',
            'title' => 'SaaS: Supporting integration pages with guardrails',
            'excerpt' => 'A safe workflow mix to support integration + comparison pages while keeping approvals and evidence logs.',
            'segment' => 'SaaS',
            'riskMode' => 'Balanced',
            'duration' => '8 weeks',
            'stack' => ['Comment', 'Forum', 'Guest'],
            'startingPoint' => [
                'bullets' => [
                    'New integration pages needed authority support',
                    'Strict anchor policy required',
                    'Moderation cycles unpredictable',
                ],
            ],
            'guardrails' => [
                'bullets' => [
                    'Velocity cap: moderate',
                    'Anchor mix: brand-heavy',
                    'Approval gate for medium+ risk',
                    'Whitelist targets only',
                ],
            ],
            'execution' => [
                'phases' => [
                    ['title' => 'Weeks 1–2: Setup + filters', 'bullets' => ['Target discovery', 'Whitelist', 'Approval rules']],
                    ['title' => 'Weeks 3–6: Mixed execution', 'bullets' => ['Forum discussions', 'Selective guest outreach', 'Evidence capture']],
                    ['title' => 'Weeks 7–8: Monitoring + pruning', 'bullets' => ['Live/lost review', 'Adjust targets', 'Report export (placeholder)']],
                ],
            ],
            'evidence' => [
                'items' => [
                    ['label' => 'Evidence pack', 'desc' => 'Placement URL + proof artifact placeholders + timestamps.'],
                    ['label' => 'Approval trail', 'desc' => 'Who approved which action and why (placeholder if not shipped).'],
                ],
            ],
            'outcomes' => [
                'note' => 'Outcomes vary. This case study uses directional metrics and time windows.',
                'metrics' => [
                    ['label' => 'Actions executed', 'value' => '~4,800'],
                    ['label' => 'Approved actions', 'value' => '~3,900'],
                    ['label' => 'Live placements (observed)', 'value' => 'Varies by moderation'],
                    ['label' => 'Time to first outcomes', 'value' => '2–4 weeks'],
                ],
                'charts' => [
                    ['label' => 'Weekly actions', 'data' => [420, 560, 610, 640, 700, 720, 620, 530]],
                ],
            ],
            'takeaways' => [
                'bullets' => [
                    'Brand-heavy anchors reduced risk while building consistency.',
                    'Approvals prevented low-quality targets from scaling.',
                    'Monitoring/pruning kept velocity stable.',
                ],
            ],
            'cta' => [
                'primary' => 'Generate my free plan',
                'secondary' => 'Book demo',
            ],
            'seo' => [
                'title' => 'SaaS Case Study — BacklinkPro',
                'description' => 'A guardrailed workflow mix for SaaS integration pages with approvals and evidence logs.',
            ],
        ],
        [
            'slug' => 'ecommerce-collections-support',
            'title' => 'Ecommerce: Supporting category pages with balanced risk',
            'excerpt' => 'A balanced workflow approach to support product category pages while maintaining brand safety.',
            'segment' => 'Ecommerce',
            'riskMode' => 'Balanced',
            'duration' => '10 weeks',
            'stack' => ['Comment', 'Profile', 'Forum'],
            'startingPoint' => [
                'bullets' => [
                    'Category pages needed more authority signals',
                    'Product-focused anchor mix required',
                    'Competitive landscape analysis needed',
                ],
            ],
            'guardrails' => [
                'bullets' => [
                    'Velocity cap: moderate',
                    'Anchor mix: brand + partial',
                    'Approval gate for medium+ risk',
                    'Blacklist competitor domains',
                ],
            ],
            'execution' => [
                'phases' => [
                    ['title' => 'Weeks 1–3: Discovery + setup', 'bullets' => ['Competitor analysis', 'Target whitelist', 'Anchor rules']],
                    ['title' => 'Weeks 4–8: Execution', 'bullets' => ['Profile signals', 'Forum engagement', 'Evidence logging']],
                    ['title' => 'Weeks 9–10: Review', 'bullets' => ['Outcome tracking', 'Adjust strategy', 'Export reports (placeholder)']],
                ],
            ],
            'evidence' => [
                'items' => [
                    ['label' => 'Evidence pack', 'desc' => 'Placement URL + proof artifact placeholders + timestamps.'],
                    ['label' => 'Approval trail', 'desc' => 'Who approved which action and why (placeholder if not shipped).'],
                ],
            ],
            'outcomes' => [
                'note' => 'Outcomes vary. This case study uses directional metrics and time windows.',
                'metrics' => [
                    ['label' => 'Actions executed', 'value' => '~6,200'],
                    ['label' => 'Approved actions', 'value' => '~5,100'],
                    ['label' => 'Live placements (observed)', 'value' => 'Varies by moderation'],
                    ['label' => 'Time to first outcomes', 'value' => '3–5 weeks'],
                ],
                'charts' => [
                    ['label' => 'Weekly actions', 'data' => [450, 580, 650, 720, 780, 800, 750, 680, 600, 550]],
                ],
            ],
            'takeaways' => [
                'bullets' => [
                    'Profile signals provided strong brand consistency.',
                    'Forum engagement required patience for moderation cycles.',
                    'Blacklist prevented competitor overlap.',
                ],
            ],
            'cta' => [
                'primary' => 'Generate my free plan',
                'secondary' => 'Book demo',
            ],
            'seo' => [
                'title' => 'Ecommerce Case Study — BacklinkPro',
                'description' => 'A balanced workflow approach for ecommerce category pages with guardrails and approvals.',
            ],
        ],
        [
            'slug' => 'local-services-location-pages',
            'title' => 'Local: Supporting location pages with conservative guardrails',
            'excerpt' => 'A conservative approach to support local service location pages with strict approval gates.',
            'segment' => 'Local',
            'riskMode' => 'Conservative',
            'duration' => '12 weeks',
            'stack' => ['Comment', 'Profile'],
            'startingPoint' => [
                'bullets' => [
                    'Multiple location pages needed local authority',
                    'Very strict anchor policy required',
                    'Slow moderation expected',
                ],
            ],
            'guardrails' => [
                'bullets' => [
                    'Velocity cap: low',
                    'Anchor mix: brand-only',
                    'Approval gate for all actions',
                    'Whitelist + strict relevance filters',
                ],
            ],
            'execution' => [
                'phases' => [
                    ['title' => 'Weeks 1–4: Setup + discovery', 'bullets' => ['Local target discovery', 'Strict whitelist', 'Approval rules']],
                    ['title' => 'Weeks 5–10: Slow execution', 'bullets' => ['Comment placements', 'Profile signals', 'Evidence capture']],
                    ['title' => 'Weeks 11–12: Review', 'bullets' => ['Outcome tracking', 'Adjust velocity', 'Export reports (placeholder)']],
                ],
            ],
            'evidence' => [
                'items' => [
                    ['label' => 'Evidence pack', 'desc' => 'Placement URL + proof artifact placeholders + timestamps.'],
                    ['label' => 'Approval trail', 'desc' => 'Who approved which action and why (placeholder if not shipped).'],
                ],
            ],
            'outcomes' => [
                'note' => 'Outcomes vary. This case study uses directional metrics and time windows.',
                'metrics' => [
                    ['label' => 'Actions executed', 'value' => '~2,400'],
                    ['label' => 'Approved actions', 'value' => '~2,200'],
                    ['label' => 'Live placements (observed)', 'value' => 'Varies by moderation'],
                    ['label' => 'Time to first outcomes', 'value' => '4–6 weeks'],
                ],
                'charts' => [
                    ['label' => 'Weekly actions', 'data' => [180, 200, 220, 240, 250, 260, 270, 280, 270, 250, 240, 200]],
                ],
            ],
            'takeaways' => [
                'bullets' => [
                    'Conservative velocity prevented spam signals.',
                    'Brand-only anchors maintained safety.',
                    'Slow execution required patience but reduced risk.',
                ],
            ],
            'cta' => [
                'primary' => 'Generate my free plan',
                'secondary' => 'Book demo',
            ],
            'seo' => [
                'title' => 'Local Services Case Study — BacklinkPro',
                'description' => 'A conservative workflow approach for local service location pages with strict guardrails.',
            ],
        ],
        [
            'slug' => 'agency-multi-client-governance',
            'title' => 'Agency: Multi-client governance with Pro features',
            'excerpt' => 'An agency workflow using advanced approvals, audit trails, and white-label reporting (placeholders).',
            'segment' => 'Agency',
            'riskMode' => 'Growth',
            'duration' => '16 weeks',
            'stack' => ['Comment', 'Profile', 'Forum', 'Guest'],
            'startingPoint' => [
                'bullets' => [
                    'Multiple clients with different risk profiles',
                    'Need for audit trails and reporting',
                    'White-label exports required (placeholder)',
                ],
            ],
            'guardrails' => [
                'bullets' => [
                    'Velocity cap: higher with monitoring',
                    'Anchor mix: varies by client',
                    'Advanced approval rules per client',
                    'Client-specific whitelists/blacklists',
                ],
            ],
            'execution' => [
                'phases' => [
                    ['title' => 'Weeks 1–4: Client setup', 'bullets' => ['Client profiles', 'Risk rules per client', 'Approval workflows']],
                    ['title' => 'Weeks 5–12: Multi-client execution', 'bullets' => ['Mixed workflows', 'Client-specific evidence', 'Audit logging (placeholder)']],
                    ['title' => 'Weeks 13–16: Reporting + review', 'bullets' => ['White-label exports (placeholder)', 'Client reports', 'Strategy adjustments']],
                ],
            ],
            'evidence' => [
                'items' => [
                    ['label' => 'Evidence pack', 'desc' => 'Placement URL + proof artifact placeholders + timestamps.'],
                    ['label' => 'Approval trail', 'desc' => 'Who approved which action and why (placeholder if not shipped).'],
                    ['label' => 'Audit trail', 'desc' => 'Full activity log per client (placeholder if not shipped).'],
                ],
            ],
            'outcomes' => [
                'note' => 'Outcomes vary. This case study uses directional metrics and time windows.',
                'metrics' => [
                    ['label' => 'Actions executed', 'value' => '~18,500'],
                    ['label' => 'Approved actions', 'value' => '~15,200'],
                    ['label' => 'Live placements (observed)', 'value' => 'Varies by moderation'],
                    ['label' => 'Time to first outcomes', 'value' => '2–4 weeks'],
                ],
                'charts' => [
                    ['label' => 'Weekly actions', 'data' => [800, 1000, 1200, 1400, 1500, 1600, 1700, 1800, 1900, 2000, 1950, 1900, 1850, 1800, 1750, 1700]],
                ],
            ],
            'takeaways' => [
                'bullets' => [
                    'Advanced approvals enabled client-specific governance.',
                    'Audit trails provided transparency (placeholder).',
                    'White-label reporting supported agency needs (placeholder).',
                ],
            ],
            'cta' => [
                'primary' => 'Talk to sales',
                'secondary' => 'Book demo',
            ],
            'seo' => [
                'title' => 'Agency Case Study — BacklinkPro',
                'description' => 'An agency workflow using advanced approvals and audit trails for multi-client governance.',
            ],
        ],
    ],

    'faqs' => [
        [
            'q' => 'Do these results guarantee my outcomes?',
            'a' => 'No. Outcomes vary by niche, authority, and moderation. We show workflows, guardrails, and observed ranges.',
        ],
        [
            'q' => 'Are actions the same as backlinks?',
            'a' => 'No. Actions are tracked workflow steps; placements may or may not be approved or remain live.',
        ],
        [
            'q' => 'Can I see sample evidence?',
            'a' => 'Yes. Each action can include an evidence pack (URL + proof artifacts placeholders).',
        ],
    ],
];
