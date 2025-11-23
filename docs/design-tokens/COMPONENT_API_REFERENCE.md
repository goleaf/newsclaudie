# Design System Component API Reference

Complete API reference for all design system UI components.

## Surface Component

**Path:** `resources/views/components/ui/surface.blade.php`  
**Version:** 2.0.0  
**Category:** Layout Primitives

### Synopsis

```blade
<x-ui.surface
    variant="default|subtle|ghost"
    elevation="none|sm|md|lg|xl"
    :interactive="bool"
    :glass="bool"
    class="additional-classes"
>
    {{ $slot }}
</x-ui.surface>
```

### Props

#### `variant`
- **Type:** `string`
- **Default:** `'default'`
- **Options:** `'default'`, `'subtle'`, `'ghost'`
- **Description:** Controls background color and style

#### `elevation`
- **Type:** `string`
- **Default:** `'sm'`
- **Options:** `'none'`, `'sm'`, `'md'`, `'lg'`, `'xl'`
- **Description:** Controls shadow depth and visual elevation

#### `interactive`
- **Type:** `bool`
- **Default:** `false`
- **Description:** Enables hover effects with lift animation

#### `glass`
- **Type:** `bool`
- **Default:** `false`
- **Description:** Enables glassmorphism effect with backdrop blur

### Slots

#### Default Slot
- **Required:** Yes
- **Description:** Main content area
- **Example:**
  ```blade
  <x-ui.surface>
      <h2>Title</h2>
      <p>Content</p>
  </x-ui.surface>
  ```

### Attributes

All additional HTML attributes are passed through to the root `<div>` element via `$attributes`.

**Common Attributes:**
- `class` - Additional CSS classes
- `id` - Element ID
- `data-*` - Data attributes
- `aria-*` - ARIA attributes (when needed)

### CSS Classes

**Base Classes (Always Applied):**
- `rounded-2xl` - Border radius
- `border` - Border width
- `border-slate-200/80` - Border color (light)
- `dark:border-slate-800/80` - Border color (dark)

**Variant Classes:**
- `default`: `bg-white dark:bg-slate-900`
- `subtle`: `bg-slate-50 dark:bg-slate-800/50`
- `ghost`: `bg-transparent`

**Elevation Classes:**
- `none`: (no shadow)
- `sm`: `shadow-sm`
- `md`: `shadow-md`
- `lg`: `shadow-lg shadow-slate-200/50 dark:shadow-slate-950/50`
- `xl`: `shadow-xl shadow-slate-200/50 dark:shadow-slate-950/50`

**Interactive Classes (when `interactive="true"`):**
- `transition-all`
- `duration-200`
- `hover:shadow-lg`
- `hover:-translate-y-0.5`
- `cursor-pointer`

**Glass Classes (when `glass="true"`):**
- `backdrop-blur-xl`
- `bg-white/80 dark:bg-slate-900/80`
- `border-white/20 dark:border-slate-700/20`

### Design Tokens

**From `config/design-tokens.php`:**
- Border Radius: `radius.2xl` (32px)
- Shadows: `elevation.sm`, `elevation.md`, `elevation.lg`, `elevation.xl`
- Colors: `colors.neutral.*`
- Transitions: `transitions.base` (200ms)

### Examples

**Basic Card:**
```blade
<x-ui.surface class="p-6">
    <h2 class="text-xl font-semibold">Card Title</h2>
    <p class="text-slate-600">Card content</p>
</x-ui.surface>
```

**Interactive Card:**
```blade
<x-ui.surface :interactive="true" elevation="md">
    <a href="/link" class="block p-6">
        <h3>Clickable Card</h3>
    </a>
</x-ui.surface>
```

**Glass Modal:**
```blade
<x-ui.surface :glass="true" elevation="xl" class="p-8">
    <h2>Modal Title</h2>
    <p>Modal content</p>
</x-ui.surface>
```

### Accessibility

- **WCAG 2.1 AA:** All variants meet contrast requirements
- **Keyboard:** Container is not focusable; children should be
- **Screen Reader:** Semantic `<div>` with no ARIA (presentational)
- **Motion:** Respects `prefers-reduced-motion`

### Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

### Performance

- Pure CSS (no JavaScript)
- GPU-accelerated transforms
- < 1KB memory footprint
- 60fps animations

### Related Components

- `x-ui.card` - Card with header/footer slots
- `x-ui.section` - Page section wrapper
- `x-ui.modal` - Modal dialog component

### Documentation

- **Full Reference:** `docs/design-tokens/SURFACE_COMPONENT.md`
- **Quick Reference:** `docs/design-tokens/SURFACE_COMPONENT_QUICK_REFERENCE.md`
- **Tests:** `tests/Unit/SurfaceComponentPropertyTest.php`

---

## Icon Component

**Path:** `resources/views/components/ui/icon.blade.php`  
**Version:** 1.0.0  
**Category:** Visual Elements

### Synopsis

```blade
<x-ui.icon
    name="icon-name"
    size="sm|md|lg|xl"
    class="additional-classes"
/>
```

### Props

#### `name`
- **Type:** `string`
- **Required:** Yes
- **Description:** Icon identifier from icon library

#### `size`
- **Type:** `string`
- **Default:** `'md'`
- **Options:** `'sm'`, `'md'`, `'lg'`, `'xl'`
- **Description:** Icon size

### Documentation

- **Full Reference:** (To be created)
- **Quick Reference:** (To be created)

---

## Spacer Component

**Path:** `resources/views/components/ui/spacer.blade.php`  
**Version:** 1.0.0  
**Category:** Layout Utilities

### Synopsis

```blade
<x-ui.spacer size="xs|sm|md|lg|xl|2xl|3xl" />
```

### Props

#### `size`
- **Type:** `string`
- **Default:** `'md'`
- **Options:** `'xs'`, `'sm'`, `'md'`, `'lg'`, `'xl'`, `'2xl'`, `'3xl'`
- **Description:** Spacing size from design tokens

### Documentation

- **Full Reference:** (To be created)
- **Quick Reference:** (To be created)

---

## Component Index

| Component | Path | Version | Status |
|-----------|------|---------|--------|
| Surface | `ui/surface.blade.php` | 2.0.0 | ✅ Complete |
| Icon | `ui/icon.blade.php` | 1.0.0 | 🚧 In Progress |
| Spacer | `ui/spacer.blade.php` | 1.0.0 | 🚧 In Progress |
| Card | `ui/card.blade.php` | 1.0.0 | 📋 Planned |
| Button | `ui/button.blade.php` | 1.0.0 | 📋 Planned |
| Input | `ui/input.blade.php` | 1.0.0 | 📋 Planned |
| Modal | `ui/modal.blade.php` | 1.0.0 | 📋 Planned |
| Badge | `ui/badge.blade.php` | 1.0.0 | 📋 Planned |
| Notification | `ui/notification.blade.php` | 1.0.0 | 📋 Planned |
| Skeleton | `ui/skeleton.blade.php` | 1.0.0 | 📋 Planned |

---

## Conventions

### Naming

- **Components:** kebab-case (`x-ui.surface`, `x-ui.card`)
- **Props:** camelCase (`variant`, `elevation`, `interactive`)
- **Classes:** Tailwind utilities (kebab-case)

### Props

- **Boolean:** Use `:prop="true"` or `:prop="false"`
- **String:** Use `prop="value"` or `:prop="$variable"`
- **Default Values:** Always provide sensible defaults

### Slots

- **Default Slot:** Primary content area
- **Named Slots:** Use for specific sections (header, footer, etc.)

### Attributes

- **Pass-through:** Use `$attributes` for HTML attributes
- **Class Merging:** Use `$attributes->class()` for CSS classes

### Documentation

- **Full Reference:** Complete guide in `docs/design-tokens/`
- **Quick Reference:** Cheat sheet for common patterns
- **Property Tests:** Validate all prop combinations

---

**Last Updated:** 2025-11-23  
**Maintainer:** Design System Team
