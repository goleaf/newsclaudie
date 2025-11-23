# Design Tokens Security Guide

**Last Updated**: 2025-11-23  
**Version**: 1.0.0  
**Status**: ✅ Security Hardened

## Overview

This document provides security guidelines for using the Design Tokens system safely. While the system is secure by design (static configuration), following these best practices ensures defense-in-depth protection.

## Security Features

### 1. Input Validation

All token keys are validated against a whitelist:

```php
// ✅ Valid - key exists in whitelist
$color = DesignTokens::brandColor('primary');

// ⚠️ Invalid - logs warning, returns safe default
$color = DesignTokens::brandColor('malicious');
```

### 2. Output Sanitization

All CSS values are sanitized to prevent injection:

```php
// Removes dangerous characters: {};<>
// Validates against safe CSS pattern
$sanitized = DesignTokens::brandColor('primary');
```

### 3. Safe Error Handling

Errors don't expose internal structure:

```php
// ❌ Bad - exposes config structure
return config('design-tokens.colors.brand.invalid');

// ✅ Good - safe fallback, logs internally
return DesignTokens::brandColor('invalid');
```

### 4. Environment-Aware Cache Control

Cache clearing restricted in production:

```php
// Only works in development/testing
DesignTokens::clearCache();

// In production: logs warning, no-op
```

### 5. Integrity Validation

Config loaded with validation:

```php
// Validates structure
// Falls back to safe defaults on error
// Logs issues for monitoring
```

## Safe Usage Patterns

### Blade Templates

**✅ SAFE - Using Tailwind Classes (Preferred)**:
```blade
<div class="p-6 bg-brand-500 text-white rounded-lg">
    Content
</div>
```

**✅ SAFE - Escaped Output**:
```blade
<div style="color: {{ e(DesignTokens::brandColor('primary')) }}">
    Content
</div>
```

**⚠️ AVOID - Unescaped Inline Styles**:
```blade
{{-- Values are already sanitized, but double-escape for safety --}}
<div style="color: {{ DesignTokens::brandColor('primary') }}">
    Content
</div>
```


### JavaScript Integration

**✅ SAFE - JSON Encoding**:
```blade
<script>
    window.designTokens = @json([
        'primary' => DesignTokens::brandColor('primary'),
    ]);
</script>
```

**✅ SAFE - With CSP Nonce**:
```blade
<script nonce="{{ csp_nonce() }}">
    window.designTokens = @json([
        'primary' => DesignTokens::brandColor('primary'),
    ]);
</script>
```

### Component Usage

**✅ SAFE - Validated Keys**:
```php
public function mount(string $color = 'primary'): void
{
    // Validate user input
    $validColors = ['primary', 'secondary', 'accent'];
    $color = in_array($color, $validColors) ? $color : 'primary';
    
    $this->color = DesignTokens::brandColor($color);
}
```

## Security Checklist

### Development

- [ ] Use DesignTokens helper class (not direct config access)
- [ ] Validate any user input before using as token key
- [ ] Prefer Tailwind classes over inline styles
- [ ] Use `@json()` for passing tokens to JavaScript
- [ ] Test with invalid token keys
- [ ] Review error logs for warnings

### Deployment

- [ ] Verify config file permissions (644)
- [ ] Enable config caching: `php artisan config:cache`
- [ ] Verify OPcache is enabled
- [ ] Set up log monitoring for token warnings
- [ ] Review CSP headers configuration
- [ ] Test in production-like environment

### Production

- [ ] Config caching enabled
- [ ] File permissions correct (644)
- [ ] Monitoring alerts configured
- [ ] CSP headers active
- [ ] Error logging working
- [ ] No debug mode enabled

## Content Security Policy

### Recommended CSP Headers

```php
// app/Http/Middleware/SecurityHeaders.php
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    $response->headers->set('Content-Security-Policy', 
        "default-src 'self'; " .
        "style-src 'self' 'unsafe-inline'; " .  // For inline styles
        "script-src 'self' 'nonce-" . csp_nonce() . "'; " .
        "font-src 'self' data:; " .
        "img-src 'self' data: https:;"
    );
    
    return $response;
}
```

### Using CSP with Design Tokens

```blade
{{-- Generate nonce for inline styles --}}
@php
$nonce = csp_nonce();
@endphp

<style nonce="{{ $nonce }}">
    :root {
        --color-primary: {{ DesignTokens::brandColor('primary') }};
    }
</style>
```


## File Permissions

### Recommended Permissions

```bash
# Config file (read-only for web server)
chmod 644 config/design-tokens.php

# Helper class (read-only for web server)
chmod 644 app/Support/DesignTokens.php

# Verify ownership
chown www-data:www-data config/design-tokens.php
```

### Deployment Checklist

```bash
#!/bin/bash
# deploy-security-check.sh

# Check file permissions
if [ "$(stat -c %a config/design-tokens.php)" != "644" ]; then
    echo "❌ Incorrect permissions on design-tokens.php"
    exit 1
fi

# Verify config is cached
if [ ! -f bootstrap/cache/config.php ]; then
    echo "⚠️  Config not cached - run: php artisan config:cache"
fi

# Check for debug mode
if grep -q "APP_DEBUG=true" .env; then
    echo "❌ Debug mode enabled in production"
    exit 1
fi

echo "✅ Security checks passed"
```

## Monitoring & Logging

### Log Monitoring

Monitor these log patterns:

```bash
# Invalid token key attempts
grep "Invalid design token key requested" storage/logs/laravel.log

# Cache clear attempts in production
grep "Attempted to clear design token cache in production" storage/logs/laravel.log

# Unsafe CSS values
grep "Potentially unsafe CSS value detected" storage/logs/laravel.log

# Config load failures
grep "Failed to load design tokens" storage/logs/laravel.log
```

### Alert Configuration

```php
// config/logging.php
'channels' => [
    'security' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'username' => 'Security Alerts',
        'level' => 'warning',
    ],
],
```

### Usage in DesignTokens

```php
// Log security events to dedicated channel
Log::channel('security')->warning('Invalid design token key requested', [
    'category' => $category,
    'key' => $key,
    'ip' => request()->ip(),
]);
```

## Attack Scenarios & Mitigations

### 1. CSS Injection Attack

**Scenario**: Attacker modifies config file to inject malicious CSS.

**Mitigation**:
- File permissions (644) prevent unauthorized modification
- Output sanitization removes dangerous characters
- CSP headers limit inline style impact
- Integrity validation detects tampering

### 2. XSS via Font Family

**Scenario**: Malicious script in font family name.

**Mitigation**:
- Font family sanitization removes unsafe characters
- Only alphanumeric and safe characters allowed
- Validation logs suspicious values

### 3. Information Disclosure

**Scenario**: Error messages expose config structure.

**Mitigation**:
- Safe error handling with fallbacks
- Errors logged internally, not exposed to users
- Production mode hides detailed errors

### 4. Denial of Service

**Scenario**: Repeated cache clearing degrades performance.

**Mitigation**:
- Cache clearing disabled in production
- Attempts logged for monitoring
- Rate limiting on sensitive operations

