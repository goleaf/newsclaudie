# Design Tokens Security Checklist

**Quick Reference**: Security best practices for Design Tokens system

## Development Checklist

### Code Review
- [ ] Using `DesignTokens` helper class (not direct config access)
- [ ] Validating user input before using as token keys
- [ ] Using Tailwind classes instead of inline styles where possible
- [ ] Escaping output when using inline styles: `{{ e(DesignTokens::brandColor('primary')) }}`
- [ ] Using CSP nonce for inline styles: `<style nonce="{{ csp_nonce() }}">`
- [ ] No hardcoded token values in code
- [ ] Error handling doesn't expose config structure
- [ ] Logging security events appropriately

### Testing
- [ ] Security tests passing: `php artisan test --filter=Security`
- [ ] Manual testing with invalid token keys
- [ ] Testing XSS prevention
- [ ] Testing CSS injection prevention
- [ ] Testing error handling
- [ ] Testing in production-like environment

## Deployment Checklist

### Pre-Deployment
- [ ] All security tests passing
- [ ] File permissions verified: `chmod 644 config/design-tokens.php`
- [ ] No debug mode: `APP_DEBUG=false` in `.env`
- [ ] Security logs reviewed
- [ ] Dependency audit: `composer audit`
- [ ] Code reviewed for security issues

### Deployment
- [ ] Config cached: `php artisan config:cache`
- [ ] Route cached: `php artisan route:cache`
- [ ] View cached: `php artisan view:cache`
- [ ] Composer autoload updated: `composer dump-autoload --optimize`
- [ ] File permissions set correctly
- [ ] Web server config verified (Nginx/Apache)
- [ ] OPcache enabled in php.ini
- [ ] CSP headers configured

### Post-Deployment
- [ ] CSP headers verified: `curl -I https://your-app.com`
- [ ] Token access tested
- [ ] Error logs checked
- [ ] Performance monitored
- [ ] Security headers verified: https://securityheaders.com
- [ ] Monitoring alerts configured
- [ ] Team notified of deployment

## Production Monitoring

### Daily
- [ ] Check error logs for security warnings
- [ ] Monitor invalid token key attempts
- [ ] Review performance metrics

### Weekly
- [ ] Review security logs
- [ ] Check for suspicious patterns
- [ ] Verify monitoring alerts working

### Monthly
- [ ] Run security audit: `php artisan security:check`
- [ ] Review and update dependencies
- [ ] Test incident response procedures

### Quarterly
- [ ] Full security audit
- [ ] Penetration testing
- [ ] Team security training
- [ ] Update security documentation

## Security Incidents

### If Config File Compromised
1. [ ] Restore from backup immediately
2. [ ] Clear all caches
3. [ ] Restart services
4. [ ] Review access logs
5. [ ] Investigate unauthorized access
6. [ ] Update security policies
7. [ ] Notify security team

### If XSS Detected
1. [ ] Enable CSP report-only mode
2. [ ] Review affected pages
3. [ ] Clear CDN cache
4. [ ] Apply security patch
5. [ ] Update CSP policy
6. [ ] Notify users if needed

## Quick Commands

```bash
# Run security tests
php artisan test --filter=Security

# Check file permissions
stat -c "%a %n" config/design-tokens.php

# Monitor security logs
tail -f storage/logs/laravel.log | grep "design token"

# Verify CSP headers
curl -I https://your-app.com | grep Content-Security-Policy

# Clear caches
php artisan config:clear && php artisan cache:clear

# Update autoload
composer dump-autoload
```

## Common Issues

### Issue: Invalid token key warnings in logs
**Solution**: Review code for user input validation

### Issue: CSP blocking inline styles
**Solution**: Use CSP nonce: `<style nonce="{{ csp_nonce() }}">`

### Issue: Cache not clearing in production
**Solution**: This is intentional security feature

### Issue: Performance degradation
**Solution**: Verify config caching enabled

## Resources

- [Security Audit Report](../DESIGN_TOKENS_SECURITY_AUDIT.md)
- [Security Guide](DESIGN_TOKENS_SECURITY.md)
- [Implementation Summary](../DESIGN_TOKENS_SECURITY_IMPLEMENTATION_COMPLETE.md)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)

---

**Last Updated**: 2025-11-23  
**Version**: 1.0.0
