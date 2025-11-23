# Design Tokens Documentation Index

Welcome to the Design Tokens documentation. This directory contains comprehensive documentation for the design system, including design tokens, components, and implementation guides.

## Quick Start

**New to design tokens?** Start here:
1. [Design Tokens Overview](DESIGN_TOKENS.md) - Complete guide to the token system
2. [Surface Component Quick Reference](SURFACE_COMPONENT_QUICK_REFERENCE.md) - Fast lookup for common patterns
3. [Component API Reference](COMPONENT_API_REFERENCE.md) - API documentation for all components

## Documentation Structure

### Core Documentation

#### [Design Tokens](DESIGN_TOKENS.md)
Complete reference for the design token system including colors, spacing, typography, shadows, and animations.

**Topics:**
- Token categories and hierarchy
- Usage examples in PHP and Blade
- Tailwind CSS integration
- Accessibility considerations
- Dark mode support
- Performance optimization
- Troubleshooting guide

**Quick Links:**
- [Color System](DESIGN_TOKENS.md#1-colors)
- [Spacing Scale](DESIGN_TOKENS.md#2-spacing)
- [Typography](DESIGN_TOKENS.md#3-typography)
- [Shadows & Elevation](DESIGN_TOKENS.md#5-shadows)

---

### Component Documentation

#### [Surface Component](SURFACE_COMPONENT.md)
Complete reference for the `x-ui.surface` component - the foundational UI primitive.

**Topics:**
- Props and variants
- Elevation system
- Interactive states
- Glassmorphism effects
- Usage examples
- Accessibility guidelines
- Performance metrics
- Migration guide

**Quick Links:**
- [Props Reference](SURFACE_COMPONENT.md#props)
- [Usage Examples](SURFACE_COMPONENT.md#usage-examples)
- [Design Tokens](SURFACE_COMPONENT.md#design-tokens)
- [Accessibility](SURFACE_COMPONENT.md#accessibility)

#### [Surface Component Quick Reference](SURFACE_COMPONENT_QUICK_REFERENCE.md)
Fast lookup guide with common patterns and prop tables.

**Use this for:**
- Quick prop lookup
- Common usage patterns
- Variant and elevation reference
- Copy-paste examples

#### [Component API Reference](COMPONENT_API_REFERENCE.md)
API documentation for all design system components.

**Includes:**
- Surface component API
- Icon component API (planned)
- Spacer component API (planned)
- Component index

---

### Implementation Guides

#### [Surface Component Implementation Summary](SURFACE_COMPONENT_IMPLEMENTATION_SUMMARY.md)
Complete implementation summary including features, documentation, tests, and next steps.

**Topics:**
- Implementation details
- Documentation created
- Testing coverage
- Spec updates
- Success metrics
- Next steps

#### [Surface Component Changelog](CHANGELOG_SURFACE_COMPONENT.md)
Version history and migration guide for the Surface component.

**Topics:**
- Version 2.0.0 changes
- Breaking changes
- Migration guide
- Upgrade checklist

---

### Performance Documentation

#### [Design Tokens Performance](DESIGN_TOKENS_PERFORMANCE.md)
Performance optimization guide for design tokens.

**Topics:**
- Static caching
- Production optimization
- OPcache configuration
- Performance metrics

#### [Design Tokens Performance Analysis](DESIGN_TOKENS_PERFORMANCE_ANALYSIS.md)
Detailed performance analysis and benchmarks.

---

### Security Documentation

#### [Design Tokens Security](DESIGN_TOKENS_SECURITY.md)
Security guidelines for design tokens.

**Topics:**
- Input validation
- Output sanitization
- Cache security
- Production hardening

#### [Design Tokens Security Audit](DESIGN_TOKENS_SECURITY_AUDIT.md)
Security audit results and recommendations.

#### [Design Tokens Security Checklist](DESIGN_TOKENS_SECURITY_CHECKLIST.md)
Security checklist for implementation.

#### [Design Tokens Security Implementation Complete](DESIGN_TOKENS_SECURITY_IMPLEMENTATION_COMPLETE.md)
Security implementation summary.

---

### Usage Guides

#### [Design Tokens Usage Guide](DESIGN_TOKENS_USAGE_GUIDE.md)
Practical guide for using design tokens in your code.

**Topics:**
- Accessing tokens in PHP
- Using tokens in Blade
- Tailwind integration
- Helper functions
- Best practices

---

## Component Status

| Component | Version | Status | Documentation |
|-----------|---------|--------|---------------|
| Surface | 2.0.0 | ✅ Complete | [Docs](SURFACE_COMPONENT.md) |
| Icon | 1.0.0 | 🚧 In Progress | Planned |
| Spacer | 1.0.0 | 🚧 In Progress | Planned |
| Card | 1.0.0 | 📋 Planned | Planned |
| Button | 1.0.0 | 📋 Planned | Planned |
| Input | 1.0.0 | 📋 Planned | Planned |
| Modal | 1.0.0 | 📋 Planned | Planned |
| Badge | 1.0.0 | 📋 Planned | Planned |

---

## Quick Reference

### Design Tokens

```php
// Colors
config('design-tokens.colors.brand.primary')      // #6366f1
config('design-tokens.colors.semantic.success')   // #10b981
config('design-tokens.colors.neutral.500')        // #64748b

// Spacing
config('design-tokens.spacing.md')                // 1rem
config('design-tokens.spacing.xl')                // 2rem

// Typography
config('design-tokens.typography.sizes.base')     // 1rem
config('design-tokens.typography.weights.bold')   // 700

// Shadows
config('design-tokens.shadows.lg')                // CSS shadow value
config('design-tokens.elevation.lg')              // Tailwind classes
```

### Surface Component

```blade
<!-- Basic card -->
<x-ui.surface class="p-6">
    Content
</x-ui.surface>

<!-- Interactive card -->
<x-ui.surface :interactive="true" elevation="md">
    <a href="/link" class="block p-6">Clickable</a>
</x-ui.surface>

<!-- Glass modal -->
<x-ui.surface :glass="true" elevation="xl" class="p-8">
    Modal content
</x-ui.surface>

<!-- Subtle panel -->
<x-ui.surface variant="subtle" elevation="none" class="p-4">
    Sidebar
</x-ui.surface>
```

---

## Testing

### Run Surface Component Tests

```bash
# All surface tests
php artisan test --filter=SurfaceComponentPropertyTest

# With coverage
php artisan test --filter=SurfaceComponentPropertyTest --coverage

# Property tests group
php artisan test --group=property,surface
```

### Test Results

✅ **22 tests passing** (123 assertions)
- All variants produce valid output
- Correct background classes applied
- Elevation shadows work correctly
- Interactive mode functions properly
- Glass effect applies correctly
- Dark mode support verified
- Accessibility validated

---

## File Locations

### Configuration
- **Design Tokens:** `config/design-tokens.php`
- **Tailwind Config:** `tailwind.config.js`

### Components
- **Surface:** `resources/views/components/ui/surface.blade.php`
- **Icon:** `resources/views/components/ui/icon.blade.php` (in progress)
- **Spacer:** `resources/views/components/ui/spacer.blade.php` (in progress)

### Tests
- **Surface Tests:** `tests/Unit/SurfaceComponentPropertyTest.php`
- **Property Testing Helper:** `tests/Helpers/PropertyTesting.php`

### Documentation
- **Design Tokens:** `docs/design-tokens/`
- **API Reference:** `docs/api/`
- **Specs:** `.kiro/specs/design-system-upgrade/`

---

## Related Documentation

### Project Documentation
- [Project Structure](../../.kiro/steering/structure.md)
- [Technology Stack](../../.kiro/steering/tech.md)
- [Quality Playbook](../../.kiro/steering/quality.md)
- [Operating Principles](../../.kiro/steering/operating-principles.md)

### Design System
- [Design System Spec](.kiro/specs/design-system-upgrade/requirements.md)
- [Design System Tasks](.kiro/specs/design-system-upgrade/tasks.md)
- [Design System Design](.kiro/specs/design-system-upgrade/design.md)

### Interface Documentation
- [Interface Architecture](../interface/INTERFACE_ARCHITECTURE.md)
- [Interface Audit](../interface/INTERFACE_AUDIT.md)
- [Interface Migration](../interface/INTERFACE_MIGRATION.md)

---

## Contributing

### Adding New Components

1. **Create component file** in `resources/views/components/ui/`
2. **Integrate design tokens** from `config/design-tokens.php`
3. **Write property tests** in `tests/Unit/`
4. **Create documentation**:
   - Full reference guide
   - Quick reference
   - Update API reference
   - Add changelog
5. **Update specs** in `.kiro/specs/design-system-upgrade/tasks.md`

### Documentation Standards

- **Complete:** Cover all props, variants, and use cases
- **Examples:** Include 5+ practical examples
- **Accessibility:** Document WCAG compliance
- **Performance:** Include performance notes
- **Testing:** Document test coverage
- **Migration:** Provide upgrade guides for breaking changes

---

## Support

### Getting Help

1. **Check documentation** in this directory
2. **Review examples** in component docs
3. **Run tests** to verify behavior
4. **Check specs** in `.kiro/specs/`
5. **Contact maintainers** for complex issues

### Common Issues

**Tokens not updating?**
- Clear config cache: `php artisan config:clear`
- Rebuild assets: `npm run build`
- Hard refresh browser

**Tailwind classes not working?**
- Verify token sync in `tailwind.config.js`
- Check content paths
- Rebuild: `npm run dev`

**Dark mode issues?**
- Verify `darkMode: 'class'` in Tailwind config
- Check `<html class="dark">` when active
- Test color contrast

---

## Changelog

### 2025-11-23
- ✅ Created comprehensive documentation structure
- ✅ Documented Surface component (v2.0.0)
- ✅ Added quick reference guides
- ✅ Created API reference
- ✅ Added implementation summary
- ✅ Created this index document

---

**Last Updated:** 2025-11-23  
**Maintained By:** Design System Team  
**Documentation Version:** 1.0.0
