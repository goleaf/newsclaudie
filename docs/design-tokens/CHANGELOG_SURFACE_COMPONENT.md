# Surface Component Changelog

All notable changes to the Surface component.

## [2.0.0] - 2025-11-23

### Added
- **Variant System**: Three background variants (default, subtle, ghost)
- **Elevation Levels**: Five shadow levels (none, sm, md, lg, xl)
- **Interactive Mode**: Hover effects with lift animation and cursor pointer
- **Glassmorphism**: Backdrop blur effect for modern glass aesthetic
- **Dark Mode**: Full support for all variants and elevations
- **Design Tokens**: Integration with `config/design-tokens.php`
- **Property Tests**: Comprehensive test coverage in `tests/Unit/SurfaceComponentPropertyTest.php`
- **Documentation**: Complete reference guide and quick reference

### Changed
- **Border Radius**: Changed from `rounded-3xl` to `rounded-2xl` for consistency
- **Padding**: Removed built-in `p-6`, now applied via classes for flexibility
- **Element**: Always renders `<div>` (removed `tag` prop)
- **Shadows**: Enhanced with slate tints for better dark mode appearance

### Removed
- **Tag Prop**: Removed `tag` prop (always renders `<div>`)
- **Built-in Padding**: Removed default `p-6` padding

### Fixed
- Dark mode border contrast improved
- Shadow visibility in dark mode enhanced
- Interactive state transitions smoothed

### Security
- All CSS values validated and sanitized
- No inline styles or JavaScript
- WCAG 2.1 AA compliant color contrast

### Performance
- Pure CSS implementation (no JavaScript)
- GPU-accelerated transforms for smooth animations
- Minimal class composition overhead
- < 1KB memory footprint

## [1.0.0] - 2024-01-01

### Added
- Initial surface component implementation
- Basic styling with `rounded-3xl` border radius
- Built-in `p-6` padding
- `tag` prop for semantic HTML elements
- Light and dark mode support

---

## Migration Guide

### From v1.0 to v2.0

**Breaking Changes:**

1. **Removed `tag` prop**:
   ```blade
   <!-- Before -->
   <x-ui.surface tag="article">Content</x-ui.surface>
   
   <!-- After -->
   <article>
       <x-ui.surface>Content</x-ui.surface>
   </article>
   ```

2. **Removed built-in padding**:
   ```blade
   <!-- Before (had built-in p-6) -->
   <x-ui.surface>Content</x-ui.surface>
   
   <!-- After (add padding explicitly) -->
   <x-ui.surface class="p-6">Content</x-ui.surface>
   ```

3. **Border radius changed**:
   ```blade
   <!-- Before (was rounded-3xl) -->
   <x-ui.surface>Content</x-ui.surface>
   
   <!-- After (now rounded-2xl, or override) -->
   <x-ui.surface class="rounded-3xl">Content</x-ui.surface>
   ```

**New Features:**

1. **Variant system**:
   ```blade
   <x-ui.surface variant="subtle">Subtle background</x-ui.surface>
   <x-ui.surface variant="ghost">Transparent</x-ui.surface>
   ```

2. **Elevation levels**:
   ```blade
   <x-ui.surface elevation="lg">Elevated card</x-ui.surface>
   <x-ui.surface elevation="xl">Modal overlay</x-ui.surface>
   ```

3. **Interactive mode**:
   ```blade
   <x-ui.surface :interactive="true">
       <a href="/link" class="block p-6">Clickable card</a>
   </x-ui.surface>
   ```

4. **Glassmorphism**:
   ```blade
   <x-ui.surface :glass="true" elevation="xl">
       Modal with glass effect
   </x-ui.surface>
   ```

---

## Upgrade Checklist

- [ ] Update all `<x-ui.surface>` instances to add explicit padding
- [ ] Replace `tag` prop with semantic HTML wrapper
- [ ] Test all surfaces in light and dark modes
- [ ] Verify interactive surfaces have focusable children
- [ ] Check color contrast meets WCAG AA standards
- [ ] Run property tests: `php artisan test --filter=SurfaceComponentPropertyTest`
- [ ] Update any custom styles that depend on `rounded-3xl`

---

## Version History

| Version | Date | Status | Notes |
|---------|------|--------|-------|
| 2.0.0 | 2025-11-23 | Current | Major redesign with design tokens |
| 1.0.0 | 2024-01-01 | Deprecated | Initial implementation |

---

**Last Updated:** 2025-11-23  
**Maintainer:** Design System Team
