# Surface Component Quick Reference

Fast lookup for the `x-ui.surface` component.

## Basic Usage

```blade
<x-ui.surface class="p-6">
    Content
</x-ui.surface>
```

## Props

| Prop | Type | Default | Options |
|------|------|---------|---------|
| `variant` | string | `'default'` | `'default'`, `'subtle'`, `'ghost'` |
| `elevation` | string | `'sm'` | `'none'`, `'sm'`, `'md'`, `'lg'`, `'xl'` |
| `interactive` | bool | `false` | `true`, `false` |
| `glass` | bool | `false` | `true`, `false` |

## Common Patterns

### Default Card
```blade
<x-ui.surface class="p-6">
    <h2>Title</h2>
    <p>Content</p>
</x-ui.surface>
```

### Interactive Card
```blade
<x-ui.surface :interactive="true" elevation="md">
    <a href="/link" class="block p-6">
        Clickable content
    </a>
</x-ui.surface>
```

### Subtle Panel
```blade
<x-ui.surface variant="subtle" elevation="none" class="p-4">
    Sidebar content
</x-ui.surface>
```

### Glass Modal
```blade
<x-ui.surface :glass="true" elevation="xl" class="p-8">
    Modal content
</x-ui.surface>
```

### Ghost Container
```blade
<x-ui.surface variant="ghost" elevation="none">
    Transparent wrapper
</x-ui.surface>
```

## Variants

| Variant | Light | Dark | Use Case |
|---------|-------|------|----------|
| `default` | White | Slate-900 | Primary cards |
| `subtle` | Slate-50 | Slate-800/50 | Sidebars |
| `ghost` | Transparent | Transparent | Borderless |

## Elevation

| Level | Shadow | Use Case |
|-------|--------|----------|
| `none` | None | Flat surfaces |
| `sm` | Subtle | Default cards |
| `md` | Medium | Elevated cards |
| `lg` | Large | Modals |
| `xl` | Extra large | Overlays |

## Interactive Effects

When `interactive="true"`:
- Hover: Shadow increases to `lg`
- Hover: Lifts by 0.5px
- Cursor: Pointer
- Transition: 200ms

## Glass Effect

When `glass="true"`:
- Backdrop blur: `xl`
- Background: 80% opacity
- Border: 20% opacity
- Overrides variant background

## Accessibility

- ✅ WCAG 2.1 AA contrast
- ✅ Respects reduced motion
- ✅ Keyboard navigable (via children)
- ✅ Screen reader friendly

## Performance

- Pure CSS (no JS)
- GPU-accelerated transforms
- < 1KB memory footprint
- 60fps animations

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Related

- `x-ui.card` - Card with header/footer
- `x-ui.section` - Page section wrapper
- `x-ui.modal` - Modal dialog

---

**Version:** 2.0.0  
**Updated:** 2025-11-23
