<?php

return [
    'segments' => ['SaaS', 'Ecommerce', 'Local', 'Agency'],
    'stages' => ['Early', 'Growth', 'Enterprise'],
    'index_faq' => [
        [
            'q' => 'How are solutions different?',
            'a' => 'Solutions use the same guardrailed link engine but with presets tailored to your business type. SaaS focuses on authority and topic clusters, Ecommerce on product/category pages, Local on citations and local relevance, and Agency on multi-client management.',
        ],
        [
            'q' => 'Can I switch solutions later?',
            'a' => 'Yes. Solutions are presets—you can adjust settings, workflows, and controls at any time. Your data and evidence logs remain intact.',
        ],
        [
            'q' => 'Is this safe for long-term SEO?',
            'a' => 'Yes. All solutions include risk thresholds, approval gates, velocity controls, and evidence logging. We avoid spam tactics and PBNs.',
        ],
        [
            'q' => 'What is an action?',
            'a' => 'An action is a single placement attempt (comment, profile, forum post, guest pitch). Each action is logged with evidence, requires approval (if above threshold), and is monitored for live/lost status.',
        ],
        [
            'q' => 'Do you guarantee links?',
            'a' => 'No. We guarantee transparent logging and evidence per action. Placements depend on site moderation and relevance. Every attempt is logged and monitored.',
        ],
        [
            'q' => 'Do you support agencies?',
            'a' => 'Yes. The Agency solution includes multi-client management, roles/permissions, audit trails, and white-label reporting (Pro plan).',
        ],
        [
            'q' => 'Can I control anchor mix?',
            'a' => 'Yes. All solutions include anchor distribution rules: brand, partial, exact, and generic. You set the mix percentages.',
        ],
        [
            'q' => 'Do you monitor links?',
            'a' => 'Yes. All solutions include automated link health checks: live/lost status, follow/nofollow changes, and removal alerts.',
        ],
    ],
    'solutions' => [
        [
            'slug' => 'saas',
            'name' => 'SaaS',
            'summary' => 'Build authority and rankings with safe, consistent link velocity—approved and logged with evidence.',
            'whoFor' => ['B2B SaaS', 'Product-led growth teams', 'Content-led companies'],
            'goals' => [
                'Improve authority for product + integration pages',
                'Support topic clusters for long-term SEO',
                'Maintain consistent link velocity without risk spikes',
            ],
            'pageTargets' => [
                'Product pages',
                'Integration pages',
                'Comparison pages',
                'Feature documentation',
            ],
            'recommendedWorkflows' => [
                ['slug' => 'forum', 'label' => 'Forum', 'reason' => 'High relevance + contextual discussions'],
                ['slug' => 'guest', 'label' => 'Guest', 'reason' => 'Higher control, strong topical placements'],
                ['slug' => 'comment', 'label' => 'Comment', 'reason' => 'Engagement-based, safe relevance filters'],
            ],
            'presets' => [
                ['name' => 'Conservative', 'bullets' => ['Lower velocity cap', 'Higher risk threshold', 'Strict relevance']],
                ['name' => 'Balanced', 'bullets' => ['Moderate velocity', 'Standard risk threshold', 'Mixed workflows']],
                ['name' => 'Growth', 'bullets' => ['Higher velocity', 'More outreach steps', 'More guest workflow share']],
            ],
            'metrics' => [
                ['label' => 'Evidence logged', 'value' => '100%'],
                ['label' => 'Approval gates', 'value' => 'Included'],
                ['label' => 'Monitoring', 'value' => 'Live/Lost tracking'],
            ],
            'proofBlocks' => [
                ['title' => 'What gets logged', 'bullets' => ['Action attempt', 'Placement URL', 'Screenshot/snippet', 'Timestamp + operator']],
                ['title' => 'What you control', 'bullets' => ['Risk thresholds', 'Anchor distribution', 'Velocity caps', 'Whitelist/blacklist']],
            ],
            'disclosure' => 'No guaranteed links. Placements depend on site moderation and relevance. Every action is logged with evidence and monitoring.',
            'cta' => [
                'primary' => 'Run Free Backlink Plan',
                'secondary' => 'View Pricing',
                'tertiary' => 'Watch Demo',
            ],
            'heroMedia' => ['poster' => '/images/solutions/saas.webp', 'video' => '/videos/solutions/saas.mp4'],
            'seo' => [
                'title' => 'SaaS Backlink Automation — BacklinkPro',
                'description' => 'Guardrailed backlink workflows for SaaS: approvals, evidence logs, and monitoring.',
            ],
            'faq' => [
                ['q' => 'Is this safe for SaaS long-term SEO?', 'a' => 'Yes, because you control thresholds, approvals, and velocity. We avoid spam tactics.'],
                ['q' => 'Do you guarantee placements?', 'a' => 'No. We guarantee transparent logging and evidence per action; outcomes vary by niche and moderation.'],
                ['q' => 'What velocity is safe for SaaS?', 'a' => 'Depends on your domain authority and niche. Conservative preset starts at 5-10 actions/day. You can adjust based on results.'],
            ],
            'tags' => ['SaaS', 'Growth', 'Approval Required', 'Monitoring'],
        ],
        [
            'slug' => 'ecommerce',
            'name' => 'Ecommerce',
            'summary' => 'Build product and category page authority with safe placements—brand anchors, balanced velocity, and evidence logs.',
            'whoFor' => ['Ecommerce stores', 'Marketplace sellers', 'D2C brands'],
            'goals' => [
                'Improve rankings for product and category pages',
                'Build brand authority and trust signals',
                'Support seasonal campaigns with controlled velocity',
            ],
            'pageTargets' => [
                'Category pages',
                'Product pages',
                'Collections',
                'Brand pages',
            ],
            'recommendedWorkflows' => [
                ['slug' => 'comment', 'label' => 'Comment', 'reason' => 'Product review engagement, safe relevance'],
                ['slug' => 'profile', 'label' => 'Profile', 'reason' => 'Brand visibility, fast setup'],
                ['slug' => 'forum', 'label' => 'Forum', 'reason' => 'Product discussions, niche communities'],
            ],
            'presets' => [
                ['name' => 'Conservative', 'bullets' => ['Lower velocity', 'Brand anchor focus', 'Strict relevance']],
                ['name' => 'Balanced', 'bullets' => ['Moderate velocity', 'Mixed anchor types', 'Standard risk threshold']],
                ['name' => 'Growth', 'bullets' => ['Higher velocity', 'More product mentions', 'Seasonal campaign support']],
            ],
            'metrics' => [
                ['label' => 'Evidence logged', 'value' => '100%'],
                ['label' => 'Approval gates', 'value' => 'Included'],
                ['label' => 'Monitoring', 'value' => 'Live/Lost tracking'],
            ],
            'proofBlocks' => [
                ['title' => 'What gets logged', 'bullets' => ['Action attempt', 'Placement URL', 'Screenshot/snippet', 'Timestamp + operator']],
                ['title' => 'What you control', 'bullets' => ['Risk thresholds', 'Anchor distribution', 'Velocity caps', 'Whitelist/blacklist']],
            ],
            'disclosure' => 'No guaranteed links. Placements depend on site moderation and relevance. Every action is logged with evidence and monitoring.',
            'cta' => [
                'primary' => 'Run Free Backlink Plan',
                'secondary' => 'View Pricing',
                'tertiary' => 'Watch Demo',
            ],
            'heroMedia' => ['poster' => '/images/solutions/ecommerce.webp', 'video' => '/videos/solutions/ecommerce.mp4'],
            'seo' => [
                'title' => 'Ecommerce Backlink Automation — BacklinkPro',
                'description' => 'Guardrailed backlink workflows for ecommerce: product pages, brand anchors, and evidence logs.',
            ],
            'faq' => [
                ['q' => 'Can I target specific product pages?', 'a' => 'Yes. You can set page targets and anchor distribution rules to focus on specific products or categories.'],
                ['q' => 'Is this safe for ecommerce SEO?', 'a' => 'Yes. All workflows include risk thresholds and approval gates. We avoid spam and focus on relevant placements.'],
            ],
            'tags' => ['Ecommerce', 'Brand', 'Approval Required', 'Monitoring'],
        ],
        [
            'slug' => 'local',
            'name' => 'Local',
            'summary' => 'Build local relevance with citations-like placements—local blogs, forums, conservative velocity, and strict risk controls.',
            'whoFor' => ['Local businesses', 'Service providers', 'Multi-location brands'],
            'goals' => [
                'Improve local search visibility',
                'Build local authority and citations',
                'Support location-based campaigns',
            ],
            'pageTargets' => [
                'Service pages',
                'Location pages',
                'About pages',
                'Contact pages',
            ],
            'recommendedWorkflows' => [
                ['slug' => 'comment', 'label' => 'Comment', 'reason' => 'Local blog engagement, safe relevance'],
                ['slug' => 'profile', 'label' => 'Profile', 'reason' => 'Local directory profiles, fast setup'],
                ['slug' => 'forum', 'label' => 'Forum', 'reason' => 'Local community discussions'],
            ],
            'presets' => [
                ['name' => 'Conservative', 'bullets' => ['Lower velocity', 'Strict risk threshold', 'Local relevance focus']],
                ['name' => 'Balanced', 'bullets' => ['Moderate velocity', 'Standard risk threshold', 'Mixed local sources']],
                ['name' => 'Growth', 'bullets' => ['Higher velocity', 'More local outreach', 'Multi-location support']],
            ],
            'metrics' => [
                ['label' => 'Evidence logged', 'value' => '100%'],
                ['label' => 'Approval gates', 'value' => 'Included'],
                ['label' => 'Monitoring', 'value' => 'Live/Lost tracking'],
            ],
            'proofBlocks' => [
                ['title' => 'What gets logged', 'bullets' => ['Action attempt', 'Placement URL', 'Screenshot/snippet', 'Timestamp + operator']],
                ['title' => 'What you control', 'bullets' => ['Risk thresholds', 'Anchor distribution', 'Velocity caps', 'Whitelist/blacklist']],
            ],
            'disclosure' => 'No guaranteed links. Placements depend on site moderation and relevance. Every action is logged with evidence and monitoring.',
            'cta' => [
                'primary' => 'Run Free Backlink Plan',
                'secondary' => 'View Pricing',
                'tertiary' => 'Watch Demo',
            ],
            'heroMedia' => ['poster' => '/images/solutions/local.webp', 'video' => '/videos/solutions/local.mp4'],
            'seo' => [
                'title' => 'Local Business Backlink Automation — BacklinkPro',
                'description' => 'Guardrailed backlink workflows for local businesses: citations, local relevance, and evidence logs.',
            ],
            'faq' => [
                ['q' => 'Is this like citation building?', 'a' => 'Similar concept but with more control. We focus on local blogs, forums, and directories with strict relevance and risk controls.'],
                ['q' => 'Can I target multiple locations?', 'a' => 'Yes. You can set up multiple location targets and adjust anchor distribution per location.'],
            ],
            'tags' => ['Local', 'Conservative', 'Approval Required', 'Monitoring'],
        ],
        [
            'slug' => 'agency',
            'name' => 'Agency',
            'summary' => 'Manage multiple clients with roles, permissions, audit trails, and white-label reporting—all with the same guardrails.',
            'whoFor' => ['SEO agencies', 'Marketing agencies', 'Resellers'],
            'goals' => [
                'Manage multiple client projects efficiently',
                'Maintain audit trails for compliance',
                'Provide white-label reporting (Pro plan)',
                'Scale operations with bulk actions',
            ],
            'pageTargets' => [
                'Client project pages',
                'Reporting dashboards',
                'Approval queues',
                'Evidence logs',
            ],
            'recommendedWorkflows' => [
                ['slug' => 'comment', 'label' => 'Comment', 'reason' => 'Safe, scalable across clients'],
                ['slug' => 'profile', 'label' => 'Profile', 'reason' => 'Fast setup, low maintenance'],
                ['slug' => 'forum', 'label' => 'Forum', 'reason' => 'Relevant discussions, controlled risk'],
            ],
            'presets' => [
                ['name' => 'Conservative', 'bullets' => ['Lower velocity per client', 'Higher risk threshold', 'Strict approvals']],
                ['name' => 'Balanced', 'bullets' => ['Moderate velocity', 'Standard risk threshold', 'Mixed workflows']],
                ['name' => 'Growth', 'bullets' => ['Higher velocity', 'Bulk operations', 'More automation']],
            ],
            'metrics' => [
                ['label' => 'Evidence logged', 'value' => '100%'],
                ['label' => 'Approval gates', 'value' => 'Included'],
                ['label' => 'Monitoring', 'value' => 'Live/Lost tracking'],
            ],
            'proofBlocks' => [
                ['title' => 'What gets logged', 'bullets' => ['Action attempt', 'Placement URL', 'Screenshot/snippet', 'Timestamp + operator']],
                ['title' => 'What you control', 'bullets' => ['Risk thresholds', 'Anchor distribution', 'Velocity caps', 'Whitelist/blacklist']],
            ],
            'disclosure' => 'No guaranteed links. Placements depend on site moderation and relevance. Every action is logged with evidence and monitoring.',
            'cta' => [
                'primary' => 'Run Free Backlink Plan',
                'secondary' => 'View Pricing',
                'tertiary' => 'Watch Demo',
            ],
            'heroMedia' => ['poster' => '/images/solutions/agency.webp', 'video' => '/videos/solutions/agency.mp4'],
            'seo' => [
                'title' => 'Agency Backlink Automation — BacklinkPro',
                'description' => 'Multi-client backlink management for agencies: roles, permissions, audit trails, and white-label reporting.',
            ],
            'faq' => [
                ['q' => 'Do you support multi-client management?', 'a' => 'Yes. The Agency solution includes separate client projects, roles/permissions, and audit trails.'],
                ['q' => 'Can I white-label reports?', 'a' => 'Yes, on Pro plan. Reports can be branded with your agency logo and colors.'],
                ['q' => 'How do bulk operations work?', 'a' => 'You can apply settings, workflows, and approvals across multiple client projects. Each action is still logged individually.'],
            ],
            'tags' => ['Agency', 'Multi-Client', 'Approval Required', 'Monitoring'],
        ],
    ],
];
