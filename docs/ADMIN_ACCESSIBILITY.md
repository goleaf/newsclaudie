# Admin Interface Accessibility Guide

This document outlines the accessibility features implemented in the admin interface and provides guidelines for maintaining accessibility standards.

## Overview

The admin interface follows WCAG 2.1 Level AA standards and implements comprehensive accessibility features including:

- Keyboard navigation support
- Screen reader compatibility
- ARIA labels and landmarks
- Focus management
- Skip links
- High contrast support
- Semantic HTML

## Keyboard Navigation

### Global Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl+K` | Open create modal (context-dependent) |
| `Escape` | Close modal or clear search |
| `/` | Focus search input |
| `Tab` | Navigate forward through interactive elements |
| `Shift+Tab` | Navigate backward through interactive elements |

### Table Navigation

| Shortcut | Action |
|----------|--------|
| `Tab` | Move to next interactive element |
| `Enter` or `Space` | Activate button or toggle checkbox |
| `Arrow Keys` | Navigate between table cells (when focused) |

### Modal Navigation

- `Escape` - Close modal
- `Tab` - Navigate through modal elements
- Focus is trapped within modal when open
- Focus returns to trigger element when closed

## Screen Reader Support

### ARIA Labels

All interactive elements include appropriate ARIA labels:

```blade
<!-- Sortable column headers -->
<th aria-sort="ascending">
    <button aria-label="Sort by name">Name</button>
</th>

<!-- Checkboxes -->
<input type="checkbox" aria-label="Select all items">
<input type="checkbox" aria-label="Select item #123">

<!-- Loading states -->
<div role="status" aria-live="polite">Loading content...</div>

<!-- Error messages -->
<p role="alert" aria-live="assertive">Validation error</p>
```

### Landmarks

The admin interface uses semantic HTML5 landmarks:

- `<header>` - Page header with navigation
- `<main>` - Main content area
- `<nav>` - Navigation menus
- `<form>` - Form elements
- `<table>` - Data tables with proper structure

### Live Regions

Dynamic content updates use ARIA live regions:

```blade
<!-- Polite announcements (non-urgent) -->
<div role="status" aria-live="polite">
    {{ __('admin.action_success') }}
</div>

<!-- Assertive announcements (urgent) -->
<div role="alert" aria-live="assertive">
    {{ __('admin.action_failed') }}
</div>
```

## Focus Management

### Modal Focus Trap

Modals implement focus trapping using Alpine.js:

```blade
<div x-trap.inert.noscroll="show">
    <!-- Modal content -->
</div>
```

This ensures:
- Focus stays within the modal when open
- Background content is inert
- Focus returns to trigger element on close

### Focus Indicators

All interactive elements have visible focus indicators:

```css
.focus-visible:outline-none
.focus-visible:ring-2
.focus-visible:ring-indigo-500
.focus-visible:ring-offset-2
```

### Skip Links

Skip links allow keyboard users to bypass repetitive content:

```blade
<x-admin.skip-link target="#main-content" />
<x-admin.skip-link target="#data-table" label="Skip to table" />
```

## Component Accessibility

### Tables

Data tables include:

- `<caption>` or `aria-label` for table description
- `<thead>`, `<tbody>`, `<tfoot>` structure
- `scope` attributes on header cells
- `aria-sort` on sortable columns
- Row and column headers properly associated

Example:

```blade
<x-admin.table aria-label="Categories table">
    <x-slot name="head">
        <x-admin.sortable-header
            field="name"
            :sort-field="$sortField"
            :sort-direction="$sortDirection"
            label="Name"
        />
    </x-slot>
    
    <!-- Table rows -->
</x-admin.table>
```

### Forms

Forms implement comprehensive accessibility:

- Labels associated with inputs via `for` attribute
- Error messages linked via `aria-describedby`
- Required fields marked with `aria-required="true"`
- Invalid fields marked with `aria-invalid="true"`
- Helpful hints provided via `aria-describedby`

Example:

```blade
<flux:input
    id="title"
    name="title"
    wire:model.live="title"
    :invalid="(bool) $titleError"
    @if ($titleError) aria-describedby="title-error" @endif
    aria-required="true"
/>
@if ($titleError)
    <p id="title-error" role="alert">{{ $titleError }}</p>
@endif
```

### Buttons

Buttons include:

- Descriptive text or `aria-label`
- Loading states announced to screen readers
- Disabled state properly communicated

Example:

```blade
<x-admin.action-button
    action="save"
    icon="check"
    aria-label="Save changes"
>
    <span wire:loading.remove>Save</span>
    <span wire:loading>Saving...</span>
</x-admin.action-button>
```

### Badges

Status badges use semantic colors and text:

```blade
<x-admin.status-badge status="published">
    Published
</x-admin.status-badge>
```

Color is not the only indicator - text labels are always present.

## Loading States

Loading states are announced to screen readers:

```blade
<div wire:loading wire:target="save" role="status" aria-live="polite">
    <x-admin.loading-spinner />
    <span class="sr-only">{{ __('admin.saving') }}</span>
</div>
```

## Color Contrast

All text meets WCAG AA contrast requirements:

- Normal text: 4.5:1 minimum
- Large text: 3:1 minimum
- UI components: 3:1 minimum

Dark mode maintains the same contrast ratios.

## Testing Accessibility

### Manual Testing

1. **Keyboard Navigation**
   - Navigate entire interface using only keyboard
   - Verify all interactive elements are reachable
   - Check focus indicators are visible

2. **Screen Reader Testing**
   - Test with NVDA (Windows) or VoiceOver (Mac)
   - Verify all content is announced correctly
   - Check ARIA labels are descriptive

3. **Zoom Testing**
   - Test at 200% zoom
   - Verify no content is cut off
   - Check horizontal scrolling is minimal

### Automated Testing

Use tools like:

- **axe DevTools** - Browser extension for accessibility testing
- **WAVE** - Web accessibility evaluation tool
- **Lighthouse** - Chrome DevTools accessibility audit

### Browser Testing

Test in multiple browsers:

- Chrome/Edge (Chromium)
- Firefox
- Safari

## Best Practices

### When Adding New Features

1. **Use Semantic HTML**
   - Use appropriate HTML elements (`<button>`, `<nav>`, etc.)
   - Don't use `<div>` for interactive elements

2. **Provide Text Alternatives**
   - Add `alt` text for images
   - Use `aria-label` for icon-only buttons
   - Provide visible labels for form inputs

3. **Maintain Focus Order**
   - Ensure tab order is logical
   - Don't use positive `tabindex` values
   - Use `tabindex="-1"` only for programmatic focus

4. **Test with Keyboard**
   - Verify all functionality works without mouse
   - Check focus indicators are visible
   - Ensure modals trap focus properly

5. **Announce Dynamic Changes**
   - Use ARIA live regions for updates
   - Choose appropriate `aria-live` value
   - Keep announcements concise

### Common Pitfalls to Avoid

❌ **Don't:**
- Use `<div>` or `<span>` for buttons
- Rely on color alone to convey information
- Remove focus indicators
- Use positive `tabindex` values
- Create keyboard traps (except in modals)
- Use `role="button"` on `<div>` when `<button>` works

✅ **Do:**
- Use semantic HTML elements
- Provide text labels for all interactive elements
- Maintain logical focus order
- Test with keyboard and screen readers
- Use ARIA attributes appropriately
- Keep accessibility in mind from the start

## Resources

- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [ARIA Authoring Practices](https://www.w3.org/WAI/ARIA/apg/)
- [WebAIM Articles](https://webaim.org/articles/)
- [A11y Project Checklist](https://www.a11yproject.com/checklist/)

## Support

For accessibility issues or questions, please:

1. Check this documentation
2. Review WCAG guidelines
3. Test with assistive technologies
4. Consult with accessibility experts if needed
