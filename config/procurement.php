<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kenya School Procurement System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains Kenya-specific configuration for procurement,
    | compliance, tax, and governance controls.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Currency Configuration
    |--------------------------------------------------------------------------
    */
    'currency' => [
        'default' => env('DEFAULT_CURRENCY', 'KES'),
        'supported' => explode(',', env('SUPPORTED_CURRENCIES', 'KES,USD,GBP,EUR')),
        'display_format' => [
            'KES' => 'KES %s',
            'USD' => 'USD $%s',
            'GBP' => 'GBP £%s',
            'EUR' => 'EUR €%s',
        ],
        'cache_exchange_rates_minutes' => env('CACHE_EXCHANGE_RATES_MINUTES', 1440), // 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Kenya Tax Configuration
    |--------------------------------------------------------------------------
    */
    'tax' => [
        'vat' => [
            'default_rate' => env('DEFAULT_VAT_RATE', 16),
            'enabled' => true,
        ],
        'wht' => [
            'enabled' => true,
            'rates' => [
                'services' => env('DEFAULT_WHT_RATE_SERVICES', 5),
                'professional_fees' => env('DEFAULT_WHT_RATE_PROFESSIONAL', 5),
                'management_fees' => env('DEFAULT_WHT_RATE_MANAGEMENT', 2),
                'training' => env('DEFAULT_WHT_RATE_TRAINING', 5),
                'consultancy' => env('DEFAULT_WHT_RATE_PROFESSIONAL', 5),
                'rent' => 10,
                'dividends' => 5,
                'interest' => 15,
            ],
            'default_rate' => env('DEFAULT_WHT_RATE_SERVICES', 5),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | KRA / eTIMS Integration Configuration
    |--------------------------------------------------------------------------
    */
    'etims' => [
        'enabled' => env('ETIMS_ENABLED', false),
        'api_url' => env('ETIMS_API_URL', ''),
        'api_key' => env('ETIMS_API_KEY', ''),
        'pin' => env('ETIMS_PIN', ''),
        'timeout' => 30,
        'retry_attempts' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Procurement Thresholds (KES)
    |--------------------------------------------------------------------------
    */
    'thresholds' => [
        'hod_approval' => env('THRESHOLD_HOD_APPROVAL', 50000),
        'principal_approval' => env('THRESHOLD_PRINCIPAL_APPROVAL', 200000),
        'board_approval' => env('THRESHOLD_BOARD_APPROVAL', 1000000),
        'tender_required' => env('THRESHOLD_TENDER_REQUIRED', 500000),
        'quotations_required' => 3, // Minimum number of quotations
        'single_source_threshold' => 50000, // Max for single source
    ],

    /*
    |--------------------------------------------------------------------------
    | Workflow & Governance Controls
    |--------------------------------------------------------------------------
    */
    'governance' => [
        'segregation_of_duties' => [
            'enforce' => true,
            'allow_override' => false,
        ],
        'three_way_match' => [
            'enforce' => true,
            'tolerance_percentage' => 2, // 2% variance allowed
        ],
        'approval_timeout_hours' => 72,
        'requisition_validity_days' => 90,
        'po_validity_days' => 180,
        'grn_entry_deadline_days' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Emergency & Exception Controls
    |--------------------------------------------------------------------------
    */
    'emergency' => [
        'enabled' => env('EMERGENCY_PROCUREMENT_ENABLED', true),
        'max_amount' => 100000, // KES
        'requires_retrospective_approval' => true,
        'notification_required' => true,
    ],

    'budget' => [
        'overrun_allowed' => env('BUDGET_OVERRUN_ALLOWED', false),
        'overrun_max_percentage' => 5,
        'overrun_requires_approval' => true,
    ],

    'single_source' => [
        'allowed' => env('SINGLE_SOURCE_ALLOWED', true),
        'requires_justification' => env('SINGLE_SOURCE_REQUIRES_JUSTIFICATION', true),
        'requires_approval' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit & Compliance Configuration
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'enabled' => true,
        'immutable' => env('IMMUTABLE_AUDIT_LOGS', true),
        'retention_days' => env('AUDIT_LOG_RETENTION_DAYS', 2555), // 7 years
        'auto_archive' => env('AUTO_ARCHIVE_ENABLED', true),
        'archive_after_days' => 365,
        'log_all_queries' => false, // Performance consideration
        'log_failed_logins' => true,
        'log_permission_denials' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'sms' => [
            'enabled' => env('NOTIFY_SMS_ENABLED', false),
            'driver' => env('SMS_DRIVER', 'africastalking'),
            'queue' => true,
        ],
        'email' => [
            'enabled' => env('NOTIFY_EMAIL_ENABLED', true),
            'queue' => true,
        ],
        'channels' => [
            'requisition_submitted' => ['email'],
            'requisition_approved' => ['email', 'sms'],
            'requisition_rejected' => ['email', 'sms'],
            'po_created' => ['email'],
            'po_approved' => ['email'],
            'grn_created' => ['email'],
            'payment_approved' => ['email', 'sms'],
            'budget_threshold_exceeded' => ['email', 'sms'],
            'approval_pending' => ['email'],
            'emergency_procurement' => ['email', 'sms'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Inventory Configuration
    |--------------------------------------------------------------------------
    */
    'inventory' => [
        'reorder_lead_time_days' => 14,
        'stock_valuation_method' => 'FIFO', // FIFO, LIFO, Average
        'allow_negative_stock' => false,
        'cycle_count_frequency_days' => 90,
        'require_approval_for_adjustments' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Supplier Configuration
    |--------------------------------------------------------------------------
    */
    'suppliers' => [
        'require_kra_pin' => true,
        'require_tax_compliance_cert' => true,
        'cert_validity_days' => 365,
        'performance_rating_enabled' => true,
        'blacklist_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporting Configuration
    |--------------------------------------------------------------------------
    */
    'reporting' => [
        'queue_large_reports' => true,
        'cache_reports' => true,
        'cache_duration_minutes' => 60,
        'export_formats' => ['pdf', 'excel', 'csv'],
    ],

    /*
    |--------------------------------------------------------------------------
    | System Behavior
    |--------------------------------------------------------------------------
    */
    'system' => [
        'fiscal_year_start_month' => 1, // January
        'fiscal_year_end_month' => 12, // December
        'allow_backdating' => false,
        'max_backdate_days' => 7,
        'timezone' => env('APP_TIMEZONE', 'Africa/Nairobi'),
        'date_format' => 'd/m/Y',
        'datetime_format' => 'd/m/Y H:i:s',
    ],

];
