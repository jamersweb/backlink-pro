<?php

return [
    'hero_badges' => [
        'Human approvals',
        'Evidence logs',
        'Guardrails',
        'Monitoring',
    ],
    'modules' => [
        [
            'id' => 'projects',
            'title' => 'Projects & Rules',
            'summary' => 'Add sites, set thresholds, velocity, anchors, and lists.',
            'bullets' => [
                'Project settings & targets',
                'Whitelist/blacklist',
                'Anchor distribution rules',
                'Velocity caps',
            ],
            'mock' => 'settings',
        ],
        [
            'id' => 'analyzer',
            'title' => 'Website Analyzer',
            'summary' => 'Audit inputs that guide safer actions (placeholder scope).',
            'bullets' => [
                'Crawl/sitemap discovery (as available)',
                'Technical signals summary (placeholder)',
                'Recommendations checklist',
            ],
            'mock' => 'audit',
            'learnMore' => ['label' => 'Learn more', 'url' => '/how-it-works#workflow-diagram'],
        ],
        [
            'id' => 'opportunities',
            'title' => 'Opportunity Discovery',
            'summary' => 'Build a queue of relevant targets and filter aggressively.',
            'bullets' => [
                'Relevance filtering',
                'Risk scoring gate',
                'Block spam patterns',
            ],
            'mock' => 'queue',
            'learnMore' => ['label' => 'Learn more', 'url' => '/how-it-works#workflow-diagram'],
        ],
        [
            'id' => 'decisioning',
            'title' => 'AI Action Selection',
            'summary' => 'Route actions to the right workflow (comment/profile/forum/guest).',
            'bullets' => [
                'Risk-based routing',
                'Template selection',
                'Human override',
            ],
            'mock' => 'decision',
        ],
        [
            'id' => 'approvals',
            'title' => 'Approval Queue',
            'summary' => 'Review and approve actions before execution.',
            'bullets' => [
                'Approve/reject with reasons',
                'Role-based approvals (label if roadmap)',
                'Audit trail (label if roadmap)',
            ],
            'mock' => 'approvals',
            'learnMore' => ['label' => 'Learn more', 'url' => '/security'],
        ],
        [
            'id' => 'evidence',
            'title' => 'Evidence Logs',
            'summary' => 'Every action is tracked with proof artifacts (where applicable).',
            'bullets' => [
                'Placement URL',
                'Screenshot/snippet placeholders',
                'Timestamps',
            ],
            'mock' => 'evidence',
        ],
        [
            'id' => 'monitoring',
            'title' => 'Monitoring & Reports',
            'summary' => 'Track outcomes and export reports.',
            'bullets' => [
                'Live/lost tracking (label if placeholder)',
                'CSV/PDF exports (label if placeholder)',
                'Weekly summaries (placeholder)',
            ],
            'mock' => 'reports',
        ],
    ],
    'workflows' => [
        [
            'slug' => 'comment',
            'label' => 'Comment',
            'desc' => 'Engagement-based placements with strict relevance.',
        ],
        [
            'slug' => 'profile',
            'label' => 'Profile',
            'desc' => 'Brand signals with strong spam avoidance.',
        ],
        [
            'slug' => 'forum',
            'label' => 'Forum',
            'desc' => 'Contextual discussions; slower moderation cycles.',
        ],
        [
            'slug' => 'guest',
            'label' => 'Guest',
            'desc' => 'Higher control; longer timeline.',
        ],
    ],
    'guardrails' => [
        'do' => [
            'Approvals',
            'Velocity caps',
            'Risk thresholds',
            'Anchor rules',
            'Lists',
        ],
        'never' => [
            'PBNs',
            'Hacked sites',
            'Spam blasts',
            'Hidden links',
        ],
    ],
    'reports' => [
        [
            'title' => 'Evidence pack',
            'bullets' => [
                'URL',
                'Proof',
                'Timestamp',
                'Notes',
            ],
        ],
        [
            'title' => 'Monitoring snapshot',
            'bullets' => [
                'Live/Lost/Pending',
                'Follow/Nofollow (placeholder)',
                'Changes',
            ],
        ],
        [
            'title' => 'Weekly summary',
            'bullets' => [
                'Actions executed',
                'Approvals',
                'Notes',
            ],
        ],
    ],
    'faqs' => [
        [
            'q' => 'Do you guarantee backlinks?',
            'a' => 'No. Outcomes vary by niche and moderation. We focus on guardrails, approvals, evidence logging, and transparency.',
        ],
        [
            'q' => 'What is an "action"?',
            'a' => 'One executed workflow step tracked with evidence. It is not a guaranteed link.',
        ],
        [
            'q' => 'Can I control risk?',
            'a' => 'Yes. Use thresholds, velocity caps, whitelists/blacklists, and approvals.',
        ],
    ],
    'disclosures' => [
        'Outcomes vary. No guaranteed links.',
        'Replace "placeholder/roadmap" labels with actual shipped capabilities before launch.',
    ],
];
