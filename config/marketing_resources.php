<?php

return [
    'types' => [
        ['slug' => 'playbooks', 'name' => 'Playbooks', 'desc' => 'Step-by-step systems to scale safely.', 'icon' => 'book'],
        ['slug' => 'templates', 'name' => 'Templates', 'desc' => 'Safe templates for outreach and engagement.', 'icon' => 'file'],
        ['slug' => 'guides', 'name' => 'Guides', 'desc' => 'Deep dives on backlinks, safety, and monitoring.', 'icon' => 'map'],
        ['slug' => 'tools', 'name' => 'Tools', 'desc' => 'Calculators and planners for link strategy.', 'icon' => 'wrench'],
    ],

    'topics' => ['Safety', 'Approvals', 'Evidence', 'Monitoring', 'Anchors', 'Velocity', 'GEO/AI Search'],

    'featured' => [
        'guardrailed-link-building-system',
        'risk-score-calculator',
        'anchor-mix-best-practices',
    ],

    'items' => [
        [
            'type' => 'playbooks',
            'slug' => 'guardrailed-link-building-system',
            'title' => 'The Guardrailed Link Building System',
            'excerpt' => 'A practical system: discover → score → execute → approve → evidence → monitor.',
            'readingTime' => '8 min',
            'date' => '2026-01-01',
            'topics' => ['Safety', 'Approvals', 'Evidence', 'Monitoring'],
            'heroMedia' => ['poster' => '/images/resources/playbook-1.webp'],
            'sections' => [
                [
                    'h2' => 'Why guardrails matter',
                    'p' => [
                        'Guardrails ensure every link building action is safe, relevant, and logged. Without guardrails, you risk penalties, wasted effort, and poor link quality.',
                        'Our system combines risk scoring, approval gates, evidence logging, and monitoring to create a repeatable, safe process.',
                    ],
                ],
                [
                    'h2' => 'Step 1: Discovery',
                    'p' => [
                        'Find relevant opportunities using AI-driven discovery. The system identifies blogs, forums, and guest post opportunities based on your target keywords and niche.',
                        'Discovery includes relevance scoring, domain authority checks, and spam signal detection.',
                    ],
                ],
                [
                    'h2' => 'Step 2: Scoring + filters',
                    'p' => [
                        'Every opportunity is scored for risk and relevance. Low-risk, high-relevance opportunities move forward automatically.',
                        'Filters include: domain authority thresholds, spam signals, relevance matching, and historical performance data.',
                    ],
                ],
                [
                    'h2' => 'Step 3: Execution + templates',
                    'p' => [
                        'Safe templates are personalized with context from the target page. Templates avoid spam signals and maintain natural language.',
                        'Execution includes account creation (for profiles/forums), content generation, and submission tracking.',
                    ],
                ],
                [
                    'h2' => 'Step 4: Approval queue',
                    'p' => [
                        'Actions above your risk threshold require human approval. Review each action before execution.',
                        'Low-risk actions can be auto-approved per your configured rules. This balances safety with efficiency.',
                    ],
                ],
                [
                    'h2' => 'Step 5: Evidence pack',
                    'p' => [
                        'Every action is logged with evidence: placement URL, screenshot or HTML snippet, timestamp, and operator/audit trail.',
                        'Evidence packs provide full transparency and compliance documentation for teams and clients.',
                    ],
                ],
                [
                    'h2' => 'Step 6: Monitoring',
                    'p' => [
                        'Automated monitoring tracks link health: live/lost status, follow/nofollow changes, and removal alerts.',
                        'Monitoring helps you understand placement success rates and adjust strategy over time.',
                    ],
                ],
            ],
            'cta' => ['primary' => 'Run Free Backlink Plan', 'secondary' => 'View Pricing'],
            'seo' => [
                'title' => 'Guardrailed Link Building System — BacklinkPro Playbook',
                'description' => 'A safe, repeatable link building workflow with approvals, evidence logs, and monitoring.',
            ],
        ],
        [
            'type' => 'playbooks',
            'slug' => 'approval-workflow-setup',
            'title' => 'Setting Up Your Approval Workflow',
            'excerpt' => 'Configure risk thresholds, auto-approval rules, and approval queues for safe link building.',
            'readingTime' => '6 min',
            'date' => '2026-01-02',
            'topics' => ['Approvals', 'Safety'],
            'heroMedia' => ['poster' => '/images/resources/playbook-2.webp'],
            'sections' => [
                [
                    'h2' => 'Understanding risk thresholds',
                    'p' => [
                        'Risk thresholds determine which actions require manual approval. Lower thresholds mean more approvals but higher safety.',
                        'Start conservative and adjust based on results and your risk tolerance.',
                    ],
                ],
                [
                    'h2' => 'Auto-approval rules',
                    'p' => [
                        'Configure auto-approval for low-risk actions to improve efficiency. Always review high-risk actions manually.',
                        'Auto-approval rules can be based on risk score, workflow type, domain authority, and historical performance.',
                    ],
                ],
            ],
            'cta' => ['primary' => 'Run Free Backlink Plan', 'secondary' => 'View Pricing'],
            'seo' => [
                'title' => 'Approval Workflow Setup — BacklinkPro Playbook',
                'description' => 'Configure approval workflows for safe, efficient link building with guardrails.',
            ],
        ],
        [
            'type' => 'templates',
            'slug' => 'safe-comment-templates',
            'title' => 'Safe Comment Templates Library',
            'excerpt' => 'Professional comment templates that avoid spam signals and maintain natural engagement.',
            'readingTime' => '5 min',
            'date' => '2026-01-03',
            'topics' => ['Templates', 'Safety'],
            'heroMedia' => ['poster' => '/images/resources/template-1.webp'],
            'sections' => [
                [
                    'h2' => 'Template principles',
                    'p' => [
                        'Safe templates prioritize value, relevance, and natural language. Avoid promotional language, excessive links, and generic responses.',
                        'Templates are personalized with context from the target page to maintain authenticity.',
                    ],
                ],
                [
                    'h2' => 'Template categories',
                    'p' => [
                        'Neutral: Balanced, informative responses that add value.',
                        'Helpful: Value-first contributions with related insights.',
                        'Question: Engaging questions that encourage discussion.',
                        'Resource mention: Natural resource sharing with context.',
                    ],
                ],
            ],
            'cta' => ['primary' => 'Run Free Backlink Plan', 'secondary' => 'View Pricing'],
            'seo' => [
                'title' => 'Safe Comment Templates — BacklinkPro',
                'description' => 'Professional comment templates for safe, effective link building engagement.',
            ],
        ],
        [
            'type' => 'templates',
            'slug' => 'guest-post-pitch-templates',
            'title' => 'Guest Post Pitch Templates',
            'excerpt' => 'Value-first pitch templates that increase acceptance rates while maintaining editorial standards.',
            'readingTime' => '7 min',
            'date' => '2026-01-04',
            'topics' => ['Templates', 'Approvals'],
            'heroMedia' => ['poster' => '/images/resources/template-2.webp'],
            'sections' => [
                [
                    'h2' => 'Pitch structure',
                    'p' => [
                        'Effective pitches lead with value, demonstrate expertise, and propose relevant topics.',
                        'Avoid generic pitches, excessive self-promotion, and irrelevant topic suggestions.',
                    ],
                ],
            ],
            'cta' => ['primary' => 'Run Free Backlink Plan', 'secondary' => 'View Pricing'],
            'seo' => [
                'title' => 'Guest Post Pitch Templates — BacklinkPro',
                'description' => 'Professional guest post pitch templates for higher acceptance rates.',
            ],
        ],
        [
            'type' => 'guides',
            'slug' => 'anchor-mix-best-practices',
            'title' => 'Anchor Text Mix Best Practices',
            'excerpt' => 'How to balance brand, partial, exact, and generic anchors for safe, natural link profiles.',
            'readingTime' => '10 min',
            'date' => '2026-01-05',
            'topics' => ['Anchors', 'Safety'],
            'heroMedia' => ['poster' => '/images/resources/guide-1.webp'],
            'sections' => [
                [
                    'h2' => 'Why anchor diversity matters',
                    'p' => [
                        'Natural link profiles include a mix of anchor types. Over-reliance on exact match anchors can trigger penalties.',
                        'Aim for: 40-50% brand, 20-30% partial, 10-20% exact, 10-20% generic.',
                    ],
                ],
                [
                    'h2' => 'Setting anchor distribution',
                    'p' => [
                        'Configure anchor distribution rules in your workflow settings. The system enforces these rules automatically.',
                        'Monitor anchor distribution over time and adjust based on results and risk tolerance.',
                    ],
                ],
            ],
            'cta' => ['primary' => 'Run Free Backlink Plan', 'secondary' => 'View Pricing'],
            'seo' => [
                'title' => 'Anchor Text Mix Best Practices — BacklinkPro Guide',
                'description' => 'Learn how to create safe, natural anchor text distributions for link building.',
            ],
        ],
        [
            'type' => 'guides',
            'slug' => 'velocity-controls-explained',
            'title' => 'Velocity Controls Explained',
            'excerpt' => 'How velocity caps prevent unnatural link spikes and maintain safe, consistent growth.',
            'readingTime' => '8 min',
            'date' => '2026-01-06',
            'topics' => ['Velocity', 'Safety'],
            'heroMedia' => ['poster' => '/images/resources/guide-2.webp'],
            'sections' => [
                [
                    'h2' => 'What is velocity?',
                    'p' => [
                        'Velocity refers to the rate of link acquisition over time. Unnatural spikes can trigger penalties.',
                        'Velocity controls limit actions per day/week to maintain natural growth patterns.',
                    ],
                ],
                [
                    'h2' => 'Setting velocity caps',
                    'p' => [
                        'Start conservative: 5-10 actions/day for new projects. Increase gradually based on results.',
                        'Consider domain authority, niche competitiveness, and historical performance when setting caps.',
                    ],
                ],
            ],
            'cta' => ['primary' => 'Run Free Backlink Plan', 'secondary' => 'View Pricing'],
            'seo' => [
                'title' => 'Velocity Controls Explained — BacklinkPro Guide',
                'description' => 'Learn how velocity controls prevent unnatural link spikes and maintain safe growth.',
            ],
        ],
        [
            'type' => 'guides',
            'slug' => 'evidence-logging-guide',
            'title' => 'Evidence Logging Guide',
            'excerpt' => 'How evidence logs provide transparency, compliance, and audit trails for link building campaigns.',
            'readingTime' => '9 min',
            'date' => '2026-01-07',
            'topics' => ['Evidence', 'Monitoring'],
            'heroMedia' => ['poster' => '/images/resources/guide-3.webp'],
            'sections' => [
                [
                    'h2' => 'What gets logged',
                    'p' => [
                        'Every action is logged with: placement URL, screenshot or HTML snippet, timestamp, operator/audit trail.',
                        'Evidence logs provide full transparency and compliance documentation.',
                    ],
                ],
                [
                    'h2' => 'Using evidence logs',
                    'p' => [
                        'Review evidence logs to verify placements, track success rates, and identify issues.',
                        'Export evidence logs for client reporting, compliance audits, and team documentation.',
                    ],
                ],
            ],
            'cta' => ['primary' => 'Run Free Backlink Plan', 'secondary' => 'View Pricing'],
            'seo' => [
                'title' => 'Evidence Logging Guide — BacklinkPro',
                'description' => 'Learn how evidence logs provide transparency and compliance for link building.',
            ],
        ],
        [
            'type' => 'tools',
            'slug' => 'risk-score-calculator',
            'title' => 'Risk Score Calculator',
            'excerpt' => 'Calculate risk scores for link opportunities based on relevance, moderation, domain trust, and velocity.',
            'readingTime' => '5 min',
            'date' => '2026-01-08',
            'topics' => ['Safety', 'Approvals'],
            'heroMedia' => ['poster' => '/images/resources/tool-1.webp'],
            'toolWidget' => 'riskScoreCalculator',
            'sections' => [
                [
                    'h2' => 'How risk scoring works',
                    'p' => [
                        'Risk scores combine multiple factors: relevance, moderation strictness, domain trust, and velocity.',
                        'Lower scores indicate safer opportunities. Higher scores require manual approval.',
                    ],
                ],
            ],
            'cta' => ['primary' => 'Run Free Backlink Plan', 'secondary' => 'View Pricing'],
            'seo' => [
                'title' => 'Risk Score Calculator — BacklinkPro Tool',
                'description' => 'Calculate risk scores for link opportunities to inform approval decisions.',
            ],
        ],
        [
            'type' => 'tools',
            'slug' => 'anchor-mix-planner',
            'title' => 'Anchor Mix Planner',
            'excerpt' => 'Plan and validate anchor text distributions for safe, natural link profiles.',
            'readingTime' => '4 min',
            'date' => '2026-01-09',
            'topics' => ['Anchors', 'Safety'],
            'heroMedia' => ['poster' => '/images/resources/tool-2.webp'],
            'toolWidget' => 'anchorMixPlanner',
            'sections' => [
                [
                    'h2' => 'Planning anchor distribution',
                    'p' => [
                        'Use this tool to plan anchor text distributions and validate they sum to 100%.',
                        'The tool recommends presets based on your distribution: Conservative, Balanced, or Growth.',
                    ],
                ],
            ],
            'cta' => ['primary' => 'Run Free Backlink Plan', 'secondary' => 'View Pricing'],
            'seo' => [
                'title' => 'Anchor Mix Planner — BacklinkPro Tool',
                'description' => 'Plan safe anchor text distributions for natural link profiles.',
            ],
        ],
        [
            'type' => 'tools',
            'slug' => 'velocity-planner',
            'title' => 'Velocity Planner',
            'excerpt' => 'Calculate recommended action velocity based on project count and target intensity.',
            'readingTime' => '5 min',
            'date' => '2026-01-10',
            'topics' => ['Velocity', 'Safety'],
            'heroMedia' => ['poster' => '/images/resources/tool-3.webp'],
            'toolWidget' => 'velocityPlanner',
            'sections' => [
                [
                    'h2' => 'Planning velocity',
                    'p' => [
                        'Use this tool to calculate recommended weekly action ranges based on your project count and intensity.',
                        'The tool warns if your settings are too aggressive and suggests safer alternatives.',
                    ],
                ],
            ],
            'cta' => ['primary' => 'Run Free Backlink Plan', 'secondary' => 'View Pricing'],
            'seo' => [
                'title' => 'Velocity Planner — BacklinkPro Tool',
                'description' => 'Calculate safe action velocity for link building campaigns.',
            ],
        ],
    ],

    'glossary' => [
        ['term' => 'Action', 'def' => 'One executed workflow step (attempt/placement) tracked with evidence. Not a guaranteed link.'],
        ['term' => 'Risk Score', 'def' => 'A score used to gate execution and approvals based on relevance and safety signals (placeholder).'],
        ['term' => 'Evidence Log', 'def' => 'Proof pack containing placement URL, screenshot/snippet, timestamp, and operator/audit trail.'],
        ['term' => 'Velocity Cap', 'def' => 'A limit controlling actions per day/week to avoid unnatural spikes.'],
        ['term' => 'Anchor Distribution', 'def' => 'The mix of brand, partial, exact, and generic anchor text in your link profile.'],
        ['term' => 'Approval Queue', 'def' => 'A workflow step where actions above risk threshold require human review before execution.'],
        ['term' => 'Auto-Approval', 'def' => 'Automatic approval of low-risk actions based on configured rules, without manual review.'],
        ['term' => 'Blacklist', 'def' => 'A list of domains or patterns that are always blocked from link building actions.'],
        ['term' => 'Whitelist', 'def' => 'A list of domains or patterns that are always allowed for link building actions.'],
        ['term' => 'Guardrails', 'def' => 'Safety controls including risk scoring, relevance filtering, approval gates, and evidence logging.'],
        ['term' => 'Placement', 'def' => 'A successfully executed link building action that results in a live link (not guaranteed).'],
        ['term' => 'Monitoring', 'def' => 'Automated tracking of link health: live/lost status, follow/nofollow changes, and removal alerts.'],
        ['term' => 'Relevance Score', 'def' => 'A metric indicating how well an opportunity matches your target keywords and niche.'],
        ['term' => 'Domain Authority', 'def' => 'A metric (0-100) indicating the relative strength of a domain for SEO purposes.'],
        ['term' => 'Spam Signal', 'def' => 'Indicators that a domain or page may be low-quality, penalized, or unsafe for link building.'],
        ['term' => 'Template', 'def' => 'A pre-written, safe content template used for comments, profiles, forum posts, or guest pitches.'],
        ['term' => 'Workflow', 'def' => 'A defined process for link building: comment, profile, forum, or guest post workflows.'],
        ['term' => 'Evidence Pack', 'def' => 'A collection of evidence for a placement: URL, screenshot, snippet, timestamp, and audit trail.'],
        ['term' => 'Follow Link', 'def' => 'A link that passes SEO value (link juice) to the target page.'],
        ['term' => 'Nofollow Link', 'def' => 'A link with rel="nofollow" attribute that does not pass SEO value but may provide brand visibility.'],
        ['term' => 'Link Health', 'def' => 'The status of a placed link: live (active), lost (removed), or pending (awaiting moderation).'],
        ['term' => 'Topic Cluster', 'def' => 'A group of related content pages targeting a core topic and supporting subtopics for SEO.'],
        ['term' => 'PBN', 'def' => 'Private Blog Network - a network of low-quality sites used for link building (we avoid these).'],
        ['term' => 'Link Farm', 'def' => 'A low-quality site with excessive outbound links (we avoid these).'],
        ['term' => 'Moderation', 'def' => 'The process by which site owners review and approve user-generated content (comments, posts).'],
        ['term' => 'Outreach', 'def' => 'The process of contacting site owners to request link placements or guest post opportunities.'],
        ['term' => 'Personalization', 'def' => 'Customizing templates with context from target pages to maintain authenticity and relevance.'],
        ['term' => 'Audit Trail', 'def' => 'A record of who performed an action, when, and with what settings for compliance and accountability.'],
        ['term' => 'Compliance', 'def' => 'Adherence to SEO best practices, search engine guidelines, and legal requirements for link building.'],
        ['term' => 'White-Label', 'def' => 'Branded reporting and interfaces customized with agency/client branding (Pro plan feature).'],
        ['term' => 'Multi-Client', 'def' => 'Managing multiple client projects with separate settings, approvals, and reporting (Agency solution).'],
        ['term' => 'GEO/AI Search', 'def' => 'Geographic and AI-driven search features for discovering location-specific or AI-optimized opportunities.'],
    ],
];
