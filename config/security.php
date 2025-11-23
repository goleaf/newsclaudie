<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains security-related configuration for the application.
    | These settings help protect against common web vulnerabilities.
    |
    */

    'comments' => [
        /*
         * Rate Limiting
         * 
         * Prevents spam and abuse by limiting comment creation frequency.
         */
        'rate_limit' => [
            'per_minute' => env('COMMENT_RATE_LIMIT_PER_MINUTE', 10),
            'per_hour' => env('COMMENT_RATE_LIMIT_PER_HOUR', 50),
        ],

        /*
         * Spam Detection
         * 
         * Automatic spam detection using heuristics.
         */
        'spam_detection' => [
            'enabled' => env('COMMENT_SPAM_DETECTION', true),
            'max_links' => env('COMMENT_MAX_LINKS', 3),
            'max_uppercase_ratio' => env('COMMENT_MAX_UPPERCASE_RATIO', 0.7),
            'min_length' => env('COMMENT_MIN_LENGTH', 3),
            'max_length' => env('COMMENT_MAX_LENGTH', 5000),
            'max_comments_per_ip' => env('COMMENT_MAX_PER_IP', 10),
        ],

        /*
         * Content Sanitization
         * 
         * Automatically strip HTML tags to prevent XSS attacks.
         */
        'sanitization' => [
            'strip_html' => env('COMMENT_STRIP_HTML', true),
            'allowed_tags' => env('COMMENT_ALLOWED_TAGS', ''), // No HTML allowed by default
        ],

        /*
         * IP Tracking
         * 
         * Track IP addresses for abuse prevention and spam detection.
         */
        'ip_tracking' => [
            'enabled' => env('COMMENT_IP_TRACKING', true),
            'mask_for_display' => env('COMMENT_IP_MASKING', true), // GDPR compliance
        ],

        /*
         * User Agent Tracking
         * 
         * Track user agents for bot detection.
         */
        'user_agent_tracking' => [
            'enabled' => env('COMMENT_USER_AGENT_TRACKING', true),
        ],
    ],

    /*
     * GDPR Compliance
     * 
     * Settings for GDPR and privacy compliance.
     */
    'gdpr' => [
        'ip_masking' => env('GDPR_IP_MASKING', true),
        'data_retention_days' => env('GDPR_DATA_RETENTION_DAYS', 365),
        'anonymize_deleted_users' => env('GDPR_ANONYMIZE_DELETED_USERS', true),
    ],

    /*
     * Content Security Policy (CSP)
     * 
     * Configure CSP headers to prevent XSS attacks.
     */
    'csp' => [
        'enabled' => env('CSP_ENABLED', true),
        'report_only' => env('CSP_REPORT_ONLY', false),
        'report_uri' => env('CSP_REPORT_URI', null),
    ],

    /*
     * Security Headers
     * 
     * Additional security headers to protect against common attacks.
     */
    'headers' => [
        'x_frame_options' => env('SECURITY_X_FRAME_OPTIONS', 'SAMEORIGIN'),
        'x_content_type_options' => env('SECURITY_X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        'x_xss_protection' => env('SECURITY_X_XSS_PROTECTION', '1; mode=block'),
        'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'hsts' => [
            'enabled' => env('SECURITY_HSTS_ENABLED', true),
            'max_age' => env('SECURITY_HSTS_MAX_AGE', 31536000), // 1 year
            'include_subdomains' => env('SECURITY_HSTS_SUBDOMAINS', true),
            'preload' => env('SECURITY_HSTS_PRELOAD', false),
        ],
    ],

    /*
     * Audit Logging
     * 
     * Log security-related events for monitoring and compliance.
     */
    'audit' => [
        'enabled' => env('SECURITY_AUDIT_ENABLED', true),
        'log_channel' => env('SECURITY_AUDIT_CHANNEL', 'stack'),
        'events' => [
            'comment_created' => true,
            'comment_updated' => true,
            'comment_deleted' => true,
            'comment_approved' => true,
            'comment_rejected' => true,
            'spam_detected' => true,
            'rate_limit_exceeded' => true,
        ],
    ],

    /*
     * Monitoring & Alerting
     * 
     * Thresholds for security monitoring and alerting.
     */
    'monitoring' => [
        'alert_on_spam_rate' => env('SECURITY_ALERT_SPAM_RATE', 0.5), // Alert if >50% spam
        'alert_on_rate_limit_hits' => env('SECURITY_ALERT_RATE_LIMIT_HITS', 100), // Alert after 100 hits
        'alert_on_failed_validations' => env('SECURITY_ALERT_FAILED_VALIDATIONS', 50), // Alert after 50 failures
    ],
];

