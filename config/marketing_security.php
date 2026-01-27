<?php

return [
    'trust_points' => [
        [
            'title' => 'Human approvals',
            'desc' => 'Execution can require manual approval above your risk threshold.',
        ],
        [
            'title' => 'Evidence logs',
            'desc' => 'Each action records URL + proof placeholders + timestamps and operator/audit trail.',
        ],
        [
            'title' => 'Guardrails',
            'desc' => 'Whitelists/blacklists, velocity caps, and anchor rules help avoid risky behavior.',
        ],
        [
            'title' => 'Access controls',
            'desc' => 'Team roles and permissions (placeholder if not shipped yet).',
        ],
    ],
    'sections' => [
        [
            'id' => 'overview',
            'title' => 'Security-by-design',
            'lead' => 'BacklinkPro is built to be transparent and controllable: approvals, evidence logs, and auditability.',
            'cards' => [
                [
                    'title' => 'Controls',
                    'bullets' => [
                        'Risk thresholds',
                        'Velocity caps',
                        'Anchor distribution',
                        'Whitelist/blacklist',
                    ],
                ],
                [
                    'title' => 'Visibility',
                    'bullets' => [
                        'Evidence per action',
                        'Monitoring live/lost',
                        'Exportable reports',
                        'Audit trail',
                    ],
                ],
                [
                    'title' => 'Operational safety',
                    'bullets' => [
                        'Rate limits',
                        'Retries + error logging',
                        'Manual override',
                    ],
                ],
            ],
        ],
        [
            'id' => 'data',
            'title' => 'Data handling',
            'lead' => 'What we store and why (keep copy conservative and accurate).',
            'lists' => [
                [
                    'label' => 'We may store',
                    'items' => [
                        'Project URL and configuration (rules, thresholds)',
                        'Action logs and evidence references (screenshot/snippet placeholders)',
                        'System telemetry for reliability (errors, timings)',
                        'Lead form data (email, URL) when submitted',
                    ],
                ],
                [
                    'label' => 'We avoid storing',
                    'items' => [
                        'Unnecessary personal data',
                        'Sensitive secrets in logs',
                        'Credentials in plaintext (use env/secret storage patterns)',
                    ],
                ],
            ],
            'callout' => [
                'title' => 'Principle',
                'text' => 'Collect only what\'s needed for transparency, monitoring, and product reliability.',
            ],
        ],
        [
            'id' => 'access',
            'title' => 'Access control & auditability',
            'lead' => 'Make team actions reviewable and traceable.',
            'bullets' => [
                'Role-based access (Admin/Manager/Reviewer) — placeholder if not shipped',
                'Approval queue gates risky actions',
                'Audit trail: who approved/changed settings and when',
                'Evidence pack for each action attempt',
            ],
        ],
        [
            'id' => 'automation',
            'title' => 'Automation guardrails',
            'lead' => 'Automation is constrained by your rules.',
            'twoCol' => [
                [
                    'title' => 'Guardrails included',
                    'items' => [
                        'Risk score thresholds (gate execution)',
                        'Whitelist/blacklist enforcement',
                        'Anchor distribution rules',
                        'Velocity caps (actions/day)',
                        'Manual review gates (approvals)',
                    ],
                ],
                [
                    'title' => 'What we never do',
                    'items' => [
                        'PBNs',
                        'Hacked sites',
                        'Spam blasts',
                        'Hidden links',
                    ],
                ],
            ],
            'disclaimer' => 'Backlink outcomes vary. We do not guarantee links. We guarantee logging, evidence, and monitoring transparency (where applicable).',
        ],
        [
            'id' => 'reliability',
            'title' => 'Reliability & monitoring',
            'lead' => 'Designed for predictable operations.',
            'cards' => [
                [
                    'title' => 'Logging',
                    'desc' => 'Structured logs for actions, errors, and retries.',
                ],
                [
                    'title' => 'Rate limiting',
                    'desc' => 'Caps prevent spikes; scheduling controls help.',
                ],
                [
                    'title' => 'Monitoring',
                    'desc' => 'Track statuses like live/lost/pending, follow/nofollow changes (placeholder).',
                ],
            ],
        ],
        [
            'id' => 'vuln',
            'title' => 'Vulnerability reporting',
            'lead' => 'A clear path for responsible disclosure.',
            'cta' => [
                'label' => 'Contact security',
                'hint' => 'security@backlinkpro.example (placeholder)',
            ],
            'note' => 'Replace placeholder email with real address before launch.',
        ],
    ],
    'faqs' => [
        [
            'q' => 'Do you guarantee backlinks?',
            'a' => 'No. Placements depend on moderation and relevance. We focus on safe workflows, approvals, evidence logging, and monitoring transparency.',
        ],
        [
            'q' => 'What data do you store?',
            'a' => 'Project settings and action logs needed for transparency and reliability. We avoid storing unnecessary personal data and never store secrets in plaintext.',
        ],
        [
            'q' => 'Can my team approve actions?',
            'a' => 'Yes, an approval queue can gate actions above a risk threshold.',
        ],
        [
            'q' => 'Is automation constrained?',
            'a' => 'Yes. Velocity caps, risk thresholds, whitelists/blacklists, and anchor rules constrain execution.',
        ],
        [
            'q' => 'How do you prevent spam behavior?',
            'a' => 'By enforcing relevance filters, velocity caps, and risk gating, plus human approvals and audit logs.',
        ],
    ],
    'disclosures' => [
        'Outcomes vary by niche, site authority, and moderation. No guaranteed links.',
        'Replace placeholder items with your actual implemented capabilities before launch.',
    ],
];
