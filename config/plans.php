<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Arzonet Pricing & Subscription Plans
    |--------------------------------------------------------------------------
    |
    | This file contains the complete configurations of all subscription plans
    | including features, limit thresholds, taglines, and pricing details,
    | as well as promotional add-on modules for secondary monetization.
    |
    */

    'plans' => [
        'starter' => [
            'name' => 'STARTER PLAN',
            'tagline' => 'For Small Businesses & Beginners',
            'best_for' => ['Small businesses', 'Coaches', 'Local shops', 'Beginners'],
            'price' => '₹1299',
            'period' => 'month',
            'limits' => [
                'contacts' => '5,000 Email Contacts',
                'emails' => '10,000 Emails/month',
                'whatsapp' => '1 WhatsApp Number',
                'team' => '3 Team Members',
            ],
            'features' => [
                'Contact CRM',
                'CSV & Excel Contact Import',
                'Smart Column Detection',
                'Email Campaign Builder',
                'HTML Email Templates',
                'Test Send Feature',
                'Basic Personalization Tags',
                'Email Scheduling',
                'Campaign Draft Saving',
                'Open & Click Tracking',
                'Basic Analytics Dashboard',
                'Unsubscribe Management',
                'Bounce Handling',
                'Global Suppression List',
                'WhatsApp Template Sync',
                'Basic WhatsApp Broadcasting',
                'Live Chat Inbox',
                'Mobile Responsive Dashboard',
            ],
            'not_included' => [
                'Automation Workflows',
                'Advanced Analytics',
                'Multiple Sending Gateways',
                'Team Permissions',
                'White Label',
            ],
        ],
        'growth' => [
            'name' => 'GROWTH PLAN',
            'tagline' => 'For Growing Companies & Marketing Teams',
            'price' => '₹4,999',
            'period' => 'month',
            'limits' => [
                'contacts' => '10,000 Contacts',
                'emails' => '50,000 Emails/month',
                'whatsapp' => '3 WhatsApp Numbers',
                'team' => '10 Team Members',
            ],
            'features' => [
                'Multi-Gateway Email Routing (SendGrid, Amazon SES, SMTP)',
                'Smart Delivery Throttling',
                'Advanced Audience Filtering',
                'Dynamic Segmentation',
                'WhatsApp Bulk Campaign Scheduling',
                'Rich Media WhatsApp Messages (PDFs, Images, Buttons)',
                'Multi-Agent Live Inbox',
                'Internal CRM Notes',
                'Campaign Pause/Resume/Retry',
                'Advanced Open & Click Reports',
                'ISP Reputation Monitoring',
                'Peak Engagement Heatmaps',
                'Shared Media Library',
                'Contact Activity Timeline',
                'Advanced Export System',
                'Role-Based Team Permissions',
            ],
            'not_included' => [
                'Advanced Marketing Automation',
                'Dedicated IP Support',
                'Custom Tracking Domains',
                'White Label Reports',
                'Multi-Branch Workspace System',
            ],
        ],
        'business' => [
            'name' => 'BUSINESS PLAN',
            'tagline' => 'For Serious Brands & High Volume Marketing',
            'price' => '₹9,999',
            'period' => 'month',
            'limits' => [
                'contacts' => '20,000 Contacts',
                'emails' => '100,000 Emails/month',
                'whatsapp' => '10 WhatsApp Numbers',
                'team' => 'Unlimited Team Members',
            ],
            'features' => [
                'Advanced Marketing Automation (Welcome Series, Cart Recovery, Re-engagement, Birthday Campaigns, Drip Campaigns)',
                'Visual Workflow Builder',
                'Auto Follow-Up Sequences',
                'Real-Time Deliverability Center',
                'Domain Reputation Monitoring',
                'Dedicated IP Support',
                'Advanced WhatsApp Analytics',
                'Conversation Assignment System',
                'SLA-Based Support Tools',
                'AI Subject Line Suggestions',
                'Smart Send-Time Optimization',
                'Custom Tracking Domains',
                'White Label Reports',
                'Custom Branding',
                'Priority Queue Sending',
                'Multi-Branch Workspace System',
                'Custom SMTP Pool Management',
            ],
            'not_included' => [
                'Dedicated Server Deployment',
                'White Label SaaS Dashboard',
                'Client Billing System / Reseller Panel',
            ],
        ],
        'enterprise' => [
            'name' => 'ENTERPRISE PLAN',
            'tagline' => 'Agencies, SaaS Platforms & Large Enterprises',
            'price' => 'Custom',
            'period' => 'contact sales',
            'limits' => [
                'contacts' => 'Unlimited Contacts',
                'emails' => 'Unlimited Sending Infrastructure',
                'whatsapp' => 'Official Partner Support',
                'team' => 'Team Hierarchy System',
            ],
            'features' => [
                'Dedicated Server Deployment',
                'White Label SaaS Dashboard',
                'Custom Domain Branding',
                'Multi-Tenant Architecture',
                'Client Billing System',
                'Reseller Panel',
                'Advanced Security Logs',
                'SSO Login',
                'Audit Trails',
                'Dedicated Account Manager',
                'SLA Guarantees',
                'Custom Integrations',
                'ERP/CRM Integrations',
                'AI-Powered Insights Engine',
                'Custom Feature Development',
                'On-Premise Deployment Option',
            ],
            'not_included' => [],
        ],
    ],

    'addons' => [
        'extra_contacts' => [
            'name' => 'Extra Contacts',
            'desc' => 'Scale your CRM limits with dedicated additional contact capacities.',
            'icon' => 'user-group'
        ],
        'extra_emails' => [
            'name' => 'Extra Email Volume',
            'desc' => 'Boost monthly credit volumes for high-density transactional flows.',
            'icon' => 'envelope-open'
        ],
        'dedicated_ip' => [
            'name' => 'Dedicated IP',
            'desc' => 'Isolate your reputation profile with an exclusive SMTP delivery address.',
            'icon' => 'globe-alt'
        ],
        'additional_whatsapp' => [
            'name' => 'Additional WhatsApp Number',
            'desc' => 'Connect more customer care numbers or brand channels to Live Chat.',
            'icon' => 'phone'
        ],
        'ai_content' => [
            'name' => 'AI Content Generator',
            'desc' => 'Leverage smart GPT models for copy recommendations and drafts.',
            'icon' => 'sparkles'
        ],
        'extra_team' => [
            'name' => 'Extra Team Members',
            'desc' => 'Onboard more support reps and agents to coordinate campaigns.',
            'icon' => 'users'
        ],
        'premium_templates' => [
            'name' => 'Premium Templates',
            'desc' => 'Gain access to modern, pre-designed high-converting templates.',
            'icon' => 'document-duplicate'
        ],
        'priority_support' => [
            'name' => 'Priority Support',
            'desc' => 'Get 24/7 dedicated support via phone, Slack, or WhatsApp.',
            'icon' => 'chat'
        ],
        'white_label' => [
            'name' => 'White Label',
            'desc' => 'Remove brand credits and power layouts under your custom identity.',
            'icon' => 'tag'
        ],
        'custom_domain' => [
            'name' => 'Custom Domain Tracking',
            'desc' => 'Track opens and click indicators under your corporate domain structure.',
            'icon' => 'link'
        ],
        'managed_deliverability' => [
            'name' => 'Managed Deliverability Service',
            'desc' => 'Dedicated delivery specialist team to warm-up and audit domains.',
            'icon' => 'shield-check'
        ],
    ]
];
