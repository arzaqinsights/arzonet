<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Arzonet Pricing Plans
    |--------------------------------------------------------------------------
    |
    | 3 fixed plans (Starter, Growth, Business) + 1 Custom plan.
    | All plans include CRM + Email + WhatsApp.
    | Custom plan lets user pick their own quantities.
    |
    | Per-unit rates (for custom scaling & overage):
    |   CRM:      ₹699 / user / month
    |   Contacts: ₹12 per 1,000 contacts / month
    |   Email:    ₹78 per 1,000 emails / month
    |   WhatsApp: ₹499 / number / month + ₹0.90 per marketing message
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Per-Unit Scaling Rates
    |--------------------------------------------------------------------------
    */
    'rates' => [
        'crm_per_user'             => 699,     // ₹699 per extra CRM user / month
        'crm_per_1k_contacts'      => 12,      // ₹12 per 1000 extra contacts / month
        'email_per_1k'             => 78,      // ₹78 per 1,000 emails / month
        'whatsapp_per_number'      => 499,     // ₹499 per WhatsApp number / month
        'whatsapp_per_message'     => 0,       // ₹0 (Billed directly by Meta)
    ],

    /*
    |--------------------------------------------------------------------------
    | Fixed Plans
    |--------------------------------------------------------------------------
    */
    'plans' => [
        'starter' => [
            'name'    => 'Starter',
            'tagline' => 'Perfect for starting your business',
            'price'   => 1999,
            'period'  => 'month',
            'limits'  => [
                'crm_users'          => 1,
                'crm_contacts'       => 5000,
                'emails_per_month'   => 10000,
                'whatsapp_numbers'   => 1,
                'whatsapp_messages'  => 1000,
            ],
            'features' => [
                'Save & manage customer contacts',
                'Import contacts from Excel / CSV',
                'Create and send email campaigns',
                'Use ready-made email templates',
                'Track email opens and clicks',
                'Send WhatsApp template messages',
                'Connect 1 WhatsApp number',
                'View basic reports & analytics',
                'Auto-manage bounces & unsubscribes',
                'Email support (24-hour response time)',
            ],
            'not_included' => [
                'Auto follow-up email sequences',
                'Multiple email gateways (SendGrid, SES)',
                'Team roles & permissions',
                'Dedicated sending IP',
                'WhatsApp media messages (images, PDFs)',
            ],
        ],

        'growth' => [
            'name'     => 'Growth',
            'tagline'  => 'Take your marketing to the next level',
            'price'    => 7749,
            'period'   => 'month',
            'popular'  => true,
            'limits'   => [
                'crm_users'          => 3,
                'crm_contacts'       => 20000,
                'emails_per_month'   => 50000,
                'whatsapp_numbers'   => 3,
                'whatsapp_messages'  => 10000,
            ],
            'features' => [
                'All Starter features included',
                'Create smart contact groups & filters',
                'Send via multiple email gateways (SendGrid, SES, SMTP)',
                'Auto-schedule emails at the best time',
                'Campaign pause / resume / retry controls',
                'Assign roles & permissions to team members',
                'Send images, PDFs, & buttons on WhatsApp',
                'Shared WhatsApp inbox for the team',
                'Email health monitoring (domain & IP checks)',
                'Campaign performance heatmaps',
                'Priority support (6-hour response time)',
            ],
            'not_included' => [
                'Auto drip campaigns & workflow builder',
                'Dedicated sending IP address',
                'Custom tracking domain',
                'WhatsApp green-tick verification help',
            ],
        ],

        'business' => [
            'name'     => 'Business',
            'tagline'  => 'Full-power marketing automation',
            'price'    => 28999,
            'period'   => 'month',
            'limits'   => [
                'crm_users'          => 10,
                'crm_contacts'       => 100000,
                'emails_per_month'   => 200000,
                'whatsapp_numbers'   => 10,
                'whatsapp_messages'  => 50000,
            ],
            'features' => [
                'All Growth features included',
                'Auto follow-up email sequences (drip campaigns)',
                'Drag & drop workflow builder',
                'Get AI-powered subject line suggestions',
                'Dedicated sending IP address',
                'Custom tracking domain (yourdomain.com)',
                'WhatsApp analytics & conversation tracking',
                'Assistance with WhatsApp green-tick verification',
                'Multi-branch workspace system',
                'Team hierarchy controls',
                'API access (100,000 requests/day)',
                'Live chat support (1-hour response time)',
            ],
            'not_included' => [
                'White label branding',
                'Reseller panel',
                'Dedicated server deployment',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Plan Configuration (Build Your Own)
    |--------------------------------------------------------------------------
    | Users pick their own quantities. All Business-level features included.
    | Price = sum of per-unit costs for chosen quantities.
    */
    'custom' => [
        'name'    => 'Custom',
        'tagline' => 'Build a plan tailored to your needs',
        'features_label' => 'All Business features + custom limits',
        'features' => [
            'All Business features included',
            'Choose your own limits for CRM, emails, and WhatsApp',
            'Scale up or down at any time',
            'Pay only for what you use',
        ],
        'sliders' => [
            'crm_users' => [
                'label' => 'CRM Team Members',
                'min'   => 1,
                'max'   => 100,
                'step'  => 1,
                'default' => 5,
                'unit_label' => 'users',
                'rate_key' => 'crm_per_user',
            ],
            'crm_contacts' => [
                'label' => 'CRM Contacts',
                'min'   => 1000,
                'max'   => 500000,
                'step'  => 1000,
                'default' => 10000,
                'unit_label' => 'contacts',
                'rate_key' => 'crm_per_1k_contacts',
                'rate_per' => 1000,
            ],
            'emails_per_month' => [
                'label' => 'Emails Per Month',
                'min'   => 5000,
                'max'   => 1000000,
                'step'  => 5000,
                'default' => 25000,
                'unit_label' => 'emails/mo',
                'rate_key' => 'email_per_1k',
                'rate_per' => 1000,
            ],
            'whatsapp_numbers' => [
                'label' => 'WhatsApp Numbers',
                'min'   => 1,
                'max'   => 50,
                'step'  => 1,
                'default' => 2,
                'unit_label' => 'numbers',
                'rate_key' => 'whatsapp_per_number',
            ],
            'whatsapp_messages' => [
                'label' => 'WhatsApp Messages / Month',
                'min'   => 1000,
                'max'   => 500000,
                'step'  => 1000,
                'default' => 5000,
                'unit_label' => 'messages/mo',
                'rate_key' => 'whatsapp_per_message',
                'rate_per' => 1,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Comparison Table (for /pricing page)
    |--------------------------------------------------------------------------
    */
    'comparison' => [
        'CRM & Contacts' => [
            'CRM Users (Team Members)'         => ['1', '5', '10', 'Custom'],
            'Contacts Store Limit'              => ['5,000', '25,000', '1,00,000', 'Custom'],
            'Excel / CSV Contact Import'        => [true, true, true, true],
            'Smart Contact Groups & Filters'    => [false, true, true, true],
            'Contact Activity Timeline'         => [false, true, true, true],
            'Team Roles & Permissions'          => [false, true, true, true],
            'Multi-branch Workspace'            => [false, false, true, true],
            'Team Hierarchy Controls'           => [false, false, true, true],
        ],
        'Email Marketing' => [
            'Emails Per Month'                  => ['10,000', '50,000', '2,00,000', 'Custom'],
            'Email Campaign Builder'            => [true, true, true, true],
            'Ready-made Templates'              => [true, true, true, true],
            'Open & Click Tracking'             => [true, true, true, true],
            'Multiple Gateways (SES, SendGrid)' => [false, true, true, true],
            'Auto Schedule at Best Time'        => [false, true, true, true],
            'Campaign Pause / Resume'           => [false, true, true, true],
            'Domain & IP Health Monitoring'     => [false, true, true, true],
            'Auto Follow-up Sequences'          => [false, false, true, true],
            'Drag & Drop Workflow Builder'      => [false, false, true, true],
            'AI Subject Line Suggestions'       => [false, false, true, true],
            'Dedicated Sending IP'              => [false, false, true, true],
            'Custom Tracking Domain'            => [false, false, true, true],
        ],
        'WhatsApp Marketing' => [
            'Connected WhatsApp Numbers'        => ['1', '3', '10', 'Custom'],
            'Messages Per Month'                => ['1,000', '10,000', '50,000', 'Custom'],
            'WhatsApp Template Messages'        => [true, true, true, true],
            'Live Chat Inbox'                   => [true, true, true, true],
            'Media Messages (Images, PDFs)'     => [false, true, true, true],
            'Shared Team Inbox'                 => [false, true, true, true],
            'Analytics & Conversation Tracking' => [false, false, true, true],
            'Green-tick Verification Help'      => [false, false, true, true],
        ],
        'Platform & Support' => [
            'API Access'                        => ['—', '10K req/day', '1L req/day', '1L req/day'],
            'Support Level'                     => ['Email (24hr)', 'Priority (6hr)', 'Live Chat (1hr)', 'Live Chat (1hr)'],
            'White Label Branding'              => [false, false, false, 'Add-on'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Add-Ons
    |--------------------------------------------------------------------------
    */
    'addons' => [
        'extra_contacts' => [
            'name' => 'Extra CRM Contacts',
            'desc' => 'Store more contacts in your CRM.',
            'price_label' => '₹12 / 1,000 contacts',
            'icon' => 'fa-users',
        ],
        'extra_emails' => [
            'name' => 'Extra Email Volume',
            'desc' => 'Increase your monthly email sending limit.',
            'price_label' => '₹78 / 1,000 emails',
            'icon' => 'fa-envelope',
        ],
        'extra_whatsapp_number' => [
            'name' => 'Extra WhatsApp Number',
            'desc' => 'Connect additional WhatsApp numbers.',
            'price_label' => '₹499 / number / month',
            'icon' => 'fa-whatsapp',
        ],
        'extra_team' => [
            'name' => 'Extra Team Members',
            'desc' => 'Add more members to your team.',
            'price_label' => '₹699 / user / month',
            'icon' => 'fa-user-plus',
        ],
        'dedicated_ip' => [
            'name' => 'Dedicated IP Address',
            'desc' => 'Get a dedicated sending IP address for better email deliverability.',
            'price_label' => '₹2,000 / month',
            'icon' => 'fa-server',
        ],
        'white_label' => [
            'name' => 'White Label Branding',
            'desc' => 'Remove Arzonet branding and use your own brand assets.',
            'price_label' => '₹5,000 / month',
            'icon' => 'fa-eye-slash',
        ],
    ],
];
