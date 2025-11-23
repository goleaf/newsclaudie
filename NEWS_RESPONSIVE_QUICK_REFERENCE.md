# News Page Responsive Design - Quick Reference

## Breakpoint Summary

```
Mobile:  < 768px   → Collapsible filters, 1-column grid
Tablet:  768-1023px → Sidebar filters, 2-column grid  
Desktop: ≥ 1024px   → Sticky sidebar, 2-3 column grid
```

## Key Classes

### Layout Container
```blade
<div class="md:grid md:grid-cols-12 md:gap-6 lg:gap-8">
```

### Filter Sidebar
```blade
<aside class="md:col-span-4 lg:col-span-3">
    <div class="lg:sticky lg:top-8">
        <!-- Filters -->
    </div>
</aside>
```

### Main Content
```blade
<div class="mt-6 md:col-span-8 md:mt-0 lg:col-span-9">
```

### News Grid
```blade
<div class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3">
```

## Alpine.js State

### Filter Panel Toggle
```blade
<div x-data="{ mobileOpen: false }">
    <!-- Mobile toggle button -->
    <div class="md:hidden">
        <button @click="mobileOpen = !mobileOpen">
    </div>
    
    <!-- Collapsible form -->
    <form x-show="mobileOpen" x-transition x-cloak class="md:block!">
    </form>
</div>
```

## Responsive Behavior

### Mobile (< 768px)
- Filter toggle button visible
- Filters hidden by default
- Click toggle to show/hide filters
- News cards stack vertically (1 column)
- Full-width cards

### Tablet (768px - 1023px)
- Filter toggle hidden
- Filters always visible in sidebar (4/12 columns)
- News grid shows 2 columns
- Main content uses 8/12 columns

### Desktop (≥ 1024px)
- Filters in sticky sidebar (3/12 columns)
- Sidebar stays visible while scrolling
- News grid shows 2 columns (3 on XL screens)
- Main content uses 9/12 columns
- Increased spacing (gap-8)

## Testing Responsive Layouts

### Manual Testing
1. Open `/news` in browser
2. Resize window to test breakpoints:
   - < 768px: Check collapsible filters
   - 768-1023px: Check sidebar layout
   - ≥ 1024px: Check sticky sidebar

### Browser DevTools
```
Mobile:  375px × 667px (iPhone SE)
Tablet:  768px × 1024px (iPad)
Desktop: 1440px × 900px (Laptop)
```

## Common Issues & Solutions

### Issue: Filters not collapsing on mobile
**Solution:** Ensure Alpine.js is loaded and `x-data` is on parent element

### Issue: Sidebar not sticky on desktop
**Solution:** Check `lg:sticky lg:top-8` classes are applied to wrapper div

### Issue: Grid not responsive
**Solution:** Verify all breakpoint classes: `grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3`

### Issue: Flash of unstyled content
**Solution:** Add `x-cloak` directive to collapsible elements

## Accessibility

- ✅ ARIA attributes on toggle button (`aria-expanded`)
- ✅ Semantic HTML structure maintained
- ✅ Keyboard navigation supported
- ✅ Focus management preserved
- ✅ Screen reader friendly

## Performance

- ✅ CSS-only responsive behavior (no JS for layout)
- ✅ Minimal Alpine.js state (single boolean)
- ✅ Smooth transitions with `x-transition`
- ✅ Sticky positioning uses native CSS
- ✅ No layout shift with `x-cloak`
