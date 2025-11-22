# Security Quick Start Guide

## For Developers

### What Changed?

The NewsController now has comprehensive security measures. Here's what you need to know:

### 1. Rate Limiting is Active

**What it means:** Users can only make 60 requests per minute to `/news`

**What you need to do:**
- Nothing! It's automatic
- If testing locally, you might hit the limit - just wait a minute

**Testing tip:**
```bash
# Test rate limiting
for i in {1..65}; do curl http://localhost:8000/news; done
```

### 2. Filter Limits

**What it means:** Users can only select up to 10 categories and 10 authors

**What you need to do:**
- Update UI to show this limit
- Add validation messages in forms

**Example:**
```html
<p class="text-sm text-gray-500">
    Select up to 10 categories
</p>
```

### 3. Caching is Enabled

**What it means:** Filter options (categories/authors) are cached for 1 hour

**What you need to do:**
- Clear cache after adding new categories/authors in admin
- Use `php artisan cache:forget news.filter.categories`

**Quick commands:**
```bash
# Clear news caches
php artisan cache:forget news.filter.categories
php artisan cache:forget news.filter.authors

# Or clear all
php artisan cache:clear
```

### 4. Security Logging

**What it means:** Suspicious activity is logged to `storage/logs/security.log`

**What you need to do:**
- Check logs if users report issues
- Monitor for unusual patterns

**View logs:**
```bash
tail -f storage/logs/security.log
```

### 5. Performance Monitoring

**What it means:** Slow queries (>1 second) are logged in production

**What you need to do:**
- Optimize queries if you see warnings
- Check security logs for slow query alerts

## Common Tasks

### Adding a New Filter

1. Update `NewsIndexRequest` validation
2. Add filter logic to `NewsController::buildNewsQuery()`
3. Add corresponding scope to `Post` model
4. Update tests
5. Clear cache

### Adjusting Rate Limits

Edit `app/Providers/RouteServiceProvider.php`:

```php
RateLimiter::for('news', function (Request $request) {
    return Limit::perMinute(120) // Change from 60 to 120
        ->by($anonymizedIp);
});
```

### Debugging Rate Limit Issues

```bash
# Check current rate limiter config
php artisan route:list | grep news

# Clear rate limit cache
php artisan cache:clear

# Test from different IP
curl -H "X-Forwarded-For: 1.2.3.4" http://localhost:8000/news
```

### Checking Security Logs

```bash
# View all security logs
cat storage/logs/security.log

# View today's logs
grep "$(date +%Y-%m-%d)" storage/logs/security.log

# View suspicious activity
grep "Suspicious" storage/logs/security.log

# View slow queries
grep "Slow query" storage/logs/security.log
```

## Testing

### Run Security Tests

```bash
# All security tests
php artisan test --filter=NewsControllerSecurityTest

# Specific test
php artisan test --filter=it_rate_limits_requests
```

### Manual Testing Checklist

- [ ] Visit `/news` - should load
- [ ] Apply filters - should work
- [ ] Try >10 categories - should show error
- [ ] Try future date - should show error
- [ ] Refresh 65 times quickly - should get 429 error
- [ ] Check security logs - should see entries

## Troubleshooting

### "Too Many Requests" Error

**Problem:** Getting 429 errors  
**Solution:** Wait 1 minute or clear rate limit cache

```bash
php artisan cache:clear
```

### Filters Not Updating

**Problem:** New categories/authors not showing  
**Solution:** Clear filter cache

```bash
php artisan cache:forget news.filter.categories
php artisan cache:forget news.filter.authors
```

### Slow Performance

**Problem:** Page loading slowly  
**Solution:** Check security logs for slow queries

```bash
grep "Slow query" storage/logs/security.log
```

### Validation Errors

**Problem:** Unexpected validation errors  
**Solution:** Check `NewsIndexRequest` rules

```bash
# View validation rules
cat app/Http/Requests/NewsIndexRequest.php
```

## Environment Setup

### Development

```env
APP_DEBUG=true
CACHE_DRIVER=file
LOG_LEVEL=debug
```

### Production

```env
APP_DEBUG=false
CACHE_DRIVER=redis
LOG_LEVEL=warning
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict
```

## Quick Reference

### Important Files

- **Controller:** `app/Http/Controllers/NewsController.php`
- **Request:** `app/Http/Requests/NewsIndexRequest.php`
- **Model:** `app/Models/Post.php`
- **Routes:** `routes/web.php`
- **Rate Limiting:** `app/Providers/RouteServiceProvider.php`
- **Logging:** `config/logging.php`
- **Tests:** `tests/Feature/NewsControllerSecurityTest.php`

### Important Commands

```bash
# Clear caches
php artisan cache:clear
php artisan cache:forget news.filter.categories
php artisan cache:forget news.filter.authors

# View logs
tail -f storage/logs/security.log
tail -f storage/logs/laravel.log

# Run tests
php artisan test --filter=NewsControllerSecurityTest

# Check routes
php artisan route:list | grep news
```

### Security Limits

- **Rate Limit:** 60 requests/minute per IP
- **Categories:** Max 10 per request
- **Authors:** Max 10 per request
- **Pagination:** Max page 1000
- **Filter Options:** Max 100 categories/authors loaded
- **Cache TTL:** 1 hour for filter options

## Need Help?

- **Documentation:** See `docs/SECURITY_IMPLEMENTATION.md`
- **Checklist:** See `docs/SECURITY_CHECKLIST.md`
- **Summary:** See `SECURITY_AUDIT_SUMMARY.md`
- **Security Team:** security@yourdomain.com

## Version

**Last Updated:** 2025-11-23  
**Version:** 1.0.0
