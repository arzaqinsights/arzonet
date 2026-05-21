<?php

return [
    'groups' => [
        'crm' => [
            'title' => 'CRM & Contacts',
            'icon' => 'fa-users',
            'permissions' => [
                'crm.view' => [
                    'label' => 'View CRM & Lists',
                    'description' => 'Allows viewing of contact lists, individual contacts, segment rules, and general audience details.',
                ],
                'crm.create' => [
                    'label' => 'Create Lists & Contacts',
                    'description' => 'Allows adding new contacts manually or creating new empty lists.',
                ],
                'crm.edit' => [
                    'label' => 'Edit Contacts & Lists',
                    'description' => 'Allows editing contact details, notes, tag labels, and renaming lists.',
                ],
                'crm.delete' => [
                    'label' => 'Delete Contacts & Lists',
                    'description' => 'Allows deleting lists, individual contacts, and purging records.',
                ],
                'crm.import' => [
                    'label' => 'Import Contacts',
                    'description' => 'Allows importing bulk contacts from CSV/Excel sheets into lists.',
                ],
                'crm.export' => [
                    'label' => 'Export Contacts',
                    'description' => 'Allows downloading / exporting contacts from lists.',
                ],
                'crm.scrub' => [
                    'label' => 'Scrub Contacts List',
                    'description' => 'Allows list cleaning/scrubbing operations to detect invalid domains/emails.',
                ],
                'crm.bulk' => [
                    'label' => 'Perform Bulk Actions',
                    'description' => 'Allows running operations (delete, blacklist) on multiple selected contacts.',
                ],
            ]
        ],
        'campaigns' => [
            'title' => 'Email Campaigns',
            'icon' => 'fa-paper-plane',
            'permissions' => [
                'campaigns.view' => [
                    'label' => 'View Email Campaigns',
                    'description' => 'Allows viewing the campaign list, status reports, delivery metrics, and analytics logs.',
                ],
                'campaigns.create' => [
                    'label' => 'Create & Edit Campaigns',
                    'description' => 'Allows using the campaign setup wizard, cloning existing campaigns, and customizing templates.',
                ],
                'campaigns.send' => [
                    'label' => 'Send & Test Campaigns',
                    'description' => 'Allows sending/scheduling campaigns, running test emails, or retrying failed deliveries.',
                ],
                'campaigns.delete' => [
                    'label' => 'Delete Campaigns',
                    'description' => 'Allows deleting email campaigns and their reports permanently.',
                ],
                'campaigns.pause_resume' => [
                    'label' => 'Pause & Resume Sending',
                    'description' => 'Allows pausing, resuming, or cancelling active email campaign broadcasts.',
                ],
            ]
        ],
        'templates' => [
            'title' => 'Email Templates',
            'icon' => 'fa-file-code',
            'permissions' => [
                'templates.view' => [
                    'label' => 'View Templates',
                    'description' => 'Allows browsing and previewing available email templates.',
                ],
                'templates.create' => [
                    'label' => 'Create Templates',
                    'description' => 'Allows adding new email templates using the template builder.',
                ],
                'templates.edit' => [
                    'label' => 'Edit Templates',
                    'description' => 'Allows modifying template structures, HTML layouts, and style parameters.',
                ],
                'templates.delete' => [
                    'label' => 'Delete Templates',
                    'description' => 'Allows removing email templates.',
                ],
            ]
        ],
        'senders' => [
            'title' => 'Senders & Domains',
            'icon' => 'fa-server',
            'permissions' => [
                'senders.view' => [
                    'label' => 'View Senders & Domains',
                    'description' => 'Allows viewing connected SMTP/SES senders and verified domains.',
                ],
                'senders.create' => [
                    'label' => 'Add Senders & Domains',
                    'description' => 'Allows connecting new sending emails, setting up SMTP profiles, and registering domains.',
                ],
                'senders.verify' => [
                    'label' => 'Verify Senders & Domains',
                    'description' => 'Allows triggering DNS checks, DKIM/SPF verification, and SMTP test connection prompts.',
                ],
                'senders.delete' => [
                    'label' => 'Delete Senders & Domains',
                    'description' => 'Allows deleting active senders or unlinking verified domains.',
                ],
            ]
        ],
        'whatsapp' => [
            'title' => 'WhatsApp Operations',
            'icon' => 'fa-whatsapp',
            'permissions' => [
                'whatsapp.view' => [
                    'label' => 'View Live Chat & Analytics',
                    'description' => 'Allows reading incoming messages in the team inbox and viewing engagement statistics.',
                ],
                'whatsapp.chat.reply' => [
                    'label' => 'Reply in Live Chat',
                    'description' => 'Allows replying to customer queries in live chats and initiating conversations.',
                ],
                'whatsapp.accounts' => [
                    'label' => 'Manage Phone Numbers',
                    'description' => 'Allows connecting, registering, and deleting WhatsApp Business API phone numbers.',
                ],
                'whatsapp.templates' => [
                    'label' => 'Manage WhatsApp Templates',
                    'description' => 'Allows creating meta templates and syncing them from Meta Business Suite.',
                ],
                'whatsapp.campaigns' => [
                    'label' => 'Manage & Send Campaigns',
                    'description' => 'Allows setting up and launching bulk WhatsApp template message broadcasts.',
                ],
            ]
        ],
        'billing' => [
            'title' => 'Billing & General Settings',
            'icon' => 'fa-credit-card',
            'permissions' => [
                'billing.view' => [
                    'label' => 'View Plans & Invoices',
                    'description' => 'Allows viewing current plan details, limit usages, and checking invoices.',
                ],
                'billing.purchase' => [
                    'label' => 'Upgrade & Buy Addons',
                    'description' => 'Allows purchasing subscription upgrades, add-ons, or paying balances.',
                ],
                'settings.view' => [
                    'label' => 'View General Settings',
                    'description' => 'Allows viewing company details, API configurations, and metadata.',
                ],
                'settings.update' => [
                    'label' => 'Update General Settings',
                    'description' => 'Allows editing business settings, GSTIN info, and system defaults.',
                ],
            ]
        ]
    ]
];
