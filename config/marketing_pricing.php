<?php

return [
    'billing_cycles' => [
        ['value' => 'monthly', 'label' => 'Monthly'],
        ['value' => 'annual', 'label' => 'Annual', 'badge' => 'Save 15%'],
    ],

    'plans' => [
        [
            'id' => 'starter',
            'name' => 'Starter',
            'tagline' => 'For small sites testing guardrailed workflows.',
            'highlight' => false,
            'prices' => [
                'monthly' => ['amount' => 79, 'suffix' => '/mo'],
                'annual' => ['amount' => 67, 'suffix' => '/mo billed annually'],
            ],
            'limits' => [
                ['label' => 'Projects', 'value' => '1'],
                ['label' => 'Monthly actions', 'value' => '2,000'],
                ['label' => 'Team seats', 'value' => '1'],
            ],
            'includes' => [
                'Comment + Profile workflows',
                'Approvals (basic)',
                'Evidence logs',
                'Velocity caps + lists',
            ],
            'ctas' => [
                'primary' => ['label' => 'Start with Free Plan', 'href' => '/free-backlink-plan'],
                'secondary' => ['label' => 'View Workflows', 'href' => '/workflows'],
            ],
        ],
        [
            'id' => 'growth',
            'name' => 'Growth',
            'tagline' => 'For teams scaling with tighter controls and more workflows.',
            'highlight' => true,
            'badge' => 'Most popular',
            'prices' => [
                'monthly' => ['amount' => 199, 'suffix' => '/mo'],
                'annual' => ['amount' => 169, 'suffix' => '/mo billed annually'],
            ],
            'limits' => [
                ['label' => 'Projects', 'value' => '5'],
                ['label' => 'Monthly actions', 'value' => '8,000'],
                ['label' => 'Team seats', 'value' => '3'],
            ],
            'includes' => [
                'All Starter features',
                'Forum workflow',
                'Stronger approval gates (rules)',
                'Reporting export (placeholder)',
            ],
            'ctas' => [
                'primary' => ['label' => 'Generate my plan', 'href' => '/free-backlink-plan'],
                'secondary' => ['label' => 'See case studies', 'href' => '/case-studies'],
            ],
        ],
        [
            'id' => 'pro',
            'name' => 'Pro / Agency',
            'tagline' => 'For agencies and multi-project operations with audit needs.',
            'highlight' => false,
            'prices' => [
                'monthly' => ['amount' => 499, 'suffix' => '/mo'],
                'annual' => ['amount' => 424, 'suffix' => '/mo billed annually'],
            ],
            'limits' => [
                ['label' => 'Projects', 'value' => '20+'],
                ['label' => 'Monthly actions', 'value' => '25,000+'],
                ['label' => 'Team seats', 'value' => '10+'],
            ],
            'includes' => [
                'All Growth features',
                'Guest workflow',
                'Advanced approvals + audit trail (label placeholder if not shipped)',
                'White-label exports (label placeholder if not shipped)',
            ],
            'ctas' => [
                'primary' => ['label' => 'Talk to sales', 'href' => '/contact'],
                'secondary' => ['label' => 'Security & Trust', 'href' => '/security'],
            ],
        ],
    ],

    'feature_groups' => [
        [
            'title' => 'Core platform',
            'features' => [
                ['key' => 'projects', 'label' => 'Projects & rules'],
                ['key' => 'guardrails', 'label' => 'Risk thresholds, velocity caps, lists'],
                ['key' => 'approvals', 'label' => 'Approval queue'],
                ['key' => 'evidence', 'label' => 'Evidence logs'],
            ],
        ],
        [
            'title' => 'Workflows',
            'features' => [
                ['key' => 'comment', 'label' => 'Comment workflow'],
                ['key' => 'profile', 'label' => 'Profile workflow'],
                ['key' => 'forum', 'label' => 'Forum workflow'],
                ['key' => 'guest', 'label' => 'Guest workflow'],
            ],
        ],
        [
            'title' => 'Reporting & monitoring',
            'features' => [
                ['key' => 'monitoring', 'label' => 'Live/Lost tracking (placeholder)'],
                ['key' => 'exports', 'label' => 'CSV/PDF exports (placeholder)'],
                ['key' => 'weekly', 'label' => 'Weekly summaries (placeholder)'],
            ],
        ],
        [
            'title' => 'Team & governance',
            'features' => [
                ['key' => 'seats', 'label' => 'Team seats'],
                ['key' => 'roles', 'label' => 'Roles & permissions (placeholder)'],
                ['key' => 'audit', 'label' => 'Audit trail (placeholder)'],
            ],
        ],
    ],

    'matrix' => [
        'starter' => [
            'projects' => true,
            'guardrails' => true,
            'approvals' => true,
            'evidence' => true,
            'comment' => true,
            'profile' => true,
            'forum' => false,
            'guest' => false,
            'monitoring' => 'Basic (placeholder)',
            'exports' => false,
            'weekly' => false,
            'seats' => '1',
            'roles' => false,
            'audit' => false,
        ],
        'growth' => [
            'projects' => true,
            'guardrails' => true,
            'approvals' => 'Rules-based',
            'evidence' => true,
            'comment' => true,
            'profile' => true,
            'forum' => true,
            'guest' => false,
            'monitoring' => 'Standard (placeholder)',
            'exports' => 'CSV (placeholder)',
            'weekly' => 'Included (placeholder)',
            'seats' => '3',
            'roles' => false,
            'audit' => false,
        ],
        'pro' => [
            'projects' => true,
            'guardrails' => true,
            'approvals' => 'Advanced',
            'evidence' => true,
            'comment' => true,
            'profile' => true,
            'forum' => true,
            'guest' => true,
            'monitoring' => 'Advanced (placeholder)',
            'exports' => 'White-label (placeholder)',
            'weekly' => 'Included (placeholder)',
            'seats' => '10+',
            'roles' => 'Planned',
            'audit' => 'Planned',
        ],
    ],

    'add_ons' => [
        ['title' => 'Extra projects', 'desc' => 'Add more projects to your plan.', 'price' => '(placeholder)'],
        ['title' => 'Extra seats', 'desc' => 'Add seats for reviewers/approvers.', 'price' => '(placeholder)'],
        ['title' => 'White-label reporting', 'desc' => 'Agency-ready exports.', 'price' => '(placeholder / Pro)'],
    ],

    'guarantee_box' => [
        'title' => 'What we guarantee',
        'bullets' => [
            'Guardrails you control (risk thresholds, velocity, anchors)',
            'Transparent action logs with evidence artifacts (where applicable)',
            'Approval gates for risky actions',
            'No PBNs, no hacked sites, no spam blasts',
        ],
        'note' => 'Outcomes vary by niche and moderation. No guaranteed backlinks or rankings.',
    ],

    'faqs' => [
        [
            'q' => 'Do you guarantee backlinks?',
            'a' => 'No. Outcomes vary by niche, authority, and moderation. Plans include guardrails, approvals, and evidence logging for transparency.',
        ],
        [
            'q' => 'What is an "action"?',
            'a' => 'An executed workflow step (attempt/placement) tracked with evidence. Actions are not guaranteed links.',
        ],
        [
            'q' => 'Can I change plans later?',
            'a' => 'Yes. Upgrade/downgrade flows depend on your billing setup (implement later).',
        ],
        [
            'q' => 'Is monitoring included?',
            'a' => 'Some monitoring/reporting may be included; label advanced items as placeholder until shipped.',
        ],
        [
            'q' => 'Do you offer agency pricing?',
            'a' => 'Yes. Use Pro/Agency or contact sales for custom needs.',
        ],
    ],

    'disclosures' => [
        'Actions are not guaranteed links. Outcomes vary by niche and site moderation.',
        'Replace placeholder features/pricing with real values before launch.',
    ],
];
