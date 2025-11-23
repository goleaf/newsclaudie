# Admin UI Components Guide

## Overview

This document provides a comprehensive guide to the reusable UI components used throughout the admin interface. All components follow consistent styling patterns and accessibility best practices.

## Core Components

### 1. Admin Table (`x-admin.table`)

The primary data table component with built-in pagination, sorting, and filtering support.

**Features:**
- Responsive design with horizontal scrolling
- Integrated pagination controls
- Per-page selection dropdown
- Query string persistence for bookmarkable URLs
- Consistent dark mode support
- ARIA labels for accessibility

**Usage:**
```blade
<x-admin.table
    :pagination="$items"
    per-page-mode="livewire"
    per-page-field="perPage"
    :per-page-options="$this->perPageOptions"
    :per-page-value="$perPage"
    aria-label="{{ __('admin.table.aria_label') }}"
>
    <x-slot name="toolbar">
        <!-- Search, filters, and actions -->
    </x-slot>

    <x-slot name="head">
        <!-- Table headers -->
    </x-slot>

    <!-- Table rows -->
</x-admin.table>
```

**Props:**
- `pagination` - Paginator instance
- `per-page-mode` - 'livewire' or 'http'
- `per-page-field` - Field name for per-page value
- `per-page-options` - Array of per-page options
- `per-page-value` - Current per-page value
- `aria-label` - Accessibility label

### 2. Sortable Header (`x-admin.sortable-header`)

Interactive column headers with sort indicators.

**Features:**
- Visual sort direction indicators (up/down arrows)
- Active state highlighting
- Keyboard accessible
- ARIA sort attributes
- Hover states

**Usage:**
```blade
<x-admin.sortable-header
    field="name"
    :sort-field="$sortField"
    :sort-direction="$sortDirection"
    label="{{ __('admin.table.name') }}"
    align="left"
/>
```

**Props:**
- `field` - Column field name for sorting
- `sortField` - Currently active sort field
- `sortDirection` - Current sort direction ('asc' or 'desc')
- `label` - Column label text
- `align` - Text alignment ('left', 'center', 'right')

### 3. Status Badge (`x-admin.status-badge`)

Colored badges for displaying status information.

**Features:**
- Pre-configured status colors
- Multiple size options
- Optional icons
- Consistent styling across all admin pages

**Usage:**
```blade
<x-admin.status-badge status="published" size="md" />
<x-admin.status-badge status="draft" size="sm" />
<x-admin.status-badge status="approved" />
```

**Supported Statuses:**
- `published` - Green badge
- `draft` - Amber badge
- `approved` - Green badge
- `pending` - Amber badge
- `rejected` - Red badge
- `active` - Green badge
- `banned` - Red badge
- `admin` - Orange badge
- `author` - Blue badge
- `reader` - Slate badge

**Props:**
- `status` - Status type (see supported statuses)
- `size` - Badge size ('sm', 'md', 'lg')
- `icon` - Optional icon name

### 4. Table Empty State (`x-admin.table-empty`)

Empty state component for tables with no data.

**Features:**
- Centered message display
- Consistent styling
- Configurable colspan
- Custom message support

**Usage:**
```blade
@forelse ($items as $item)
    <!-- Table rows -->
@empty
    <x-admin.table-empty 
        colspan="6" 
        :message="__('admin.items.empty')" 
    />
@endforelse
```

**Props:**
- `colspan` - Number of columns to span
- `message` - Empty state message text

### 5. Table Row (`x-admin.table-row`)

Styled table row component with hover states.

**Features:**
- Interactive hover effects
- Consistent row styling
- Data attributes for testing
- Dark mode support

**Usage:**
```blade
<x-admin.table-row
    wire:key="item-{{ $item->id }}"
    :interactive="true"
    data-row-id="{{ $item->id }}"
    data-row-label="{{ $item->name }}"
>
    <!-- Table cells -->
</x-admin.table-row>
```

**Props:**
- `interactive` - Enable hover effects (boolean)
- Additional data attributes for testing

### 6. Table Head (`x-admin.table-head`)

Simplified table header component with sortable column support.

**Features:**
- Automatic sortable header generation
- Consistent header styling
- ARIA attributes
- Custom class support

**Usage:**
```blade
<x-admin.table-head 
    :columns="[
        ['label' => __('admin.table.name'), 'sortable' => true, 'field' => 'name'],
        ['label' => __('admin.table.status'), 'sortable' => true, 'field' => 'status'],
        ['label' => __('admin.table.actions'), 'class' => 'text-right'],
    ]" 
    :sort-field="$sortField" 
    :sort-direction="$sortDirection" 
/>
```

**Column Array Structure:**
- `label` - Column header text
- `sortable` - Enable sorting (boolean)
- `field` - Field name for sorting
- `class` - Additional CSS classes
- `align` - Text alignment

## Loading States

### 1. Loading Spinner (`x-admin.loading-spinner`)

Inline loading spinner for buttons and small areas.

**Usage:**
```blade
<x-admin.loading-spinner />
```

### 2. Loading Overlay (`x-admin.loading-overlay`)

Full-screen loading overlay with message.

**Features:**
- Backdrop blur effect
- Centered spinner and message
- Optional delay before showing
- Wire:loading integration

**Usage:**
```blade
<x-admin.loading-overlay 
    target="savePost" 
    delay="true"
>
    {{ __('admin.saving') }}
</x-admin.loading-overlay>
```

**Props:**
- `target` - Livewire action to watch
- `delay` - Add 500ms delay before showing (boolean)

### 3. Inline Loading States

Use `wire:loading` directives for inline loading states:

```blade
<flux:button 
    wire:click="save" 
    wire:loading.attr="disabled" 
    wire:target="save"
>
    <span wire:loading.remove wire:target="save">
        {{ __('admin.save') }}
    </span>
    <span wire:loading wire:target="save" class="inline-flex items-center gap-1">
        <svg class="h-3 w-3 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        {{ __('admin.processing') }}
    </span>
</flux:button>
```

## Flux Components

The admin interface uses Flux UI components for consistent styling:

### Badges

```blade
<flux:badge color="green">Published</flux:badge>
<flux:badge color="amber">Draft</flux:badge>
<flux:badge color="red">Rejected</flux:badge>
<flux:badge color="indigo" size="sm">Admin</flux:badge>
```

**Available Colors:**
- `green` - Success states
- `amber` - Warning/pending states
- `red` - Error/danger states
- `indigo` - Info/primary states
- `blue` - Secondary info
- `orange` - Special roles
- `slate` - Neutral states

### Buttons

```blade
<flux:button color="primary" icon="plus" wire:click="create">
    {{ __('admin.create') }}
</flux:button>

<flux:button color="secondary" size="sm" wire:click="cancel">
    {{ __('admin.cancel') }}
</flux:button>

<flux:button color="red" icon="trash" wire:click="delete">
    {{ __('admin.delete') }}
</flux:button>
```

**Button Colors:**
- `primary` - Main actions (indigo)
- `secondary` - Secondary actions (slate)
- `green` - Approve/confirm actions
- `amber` - Warning actions
- `red` - Delete/danger actions

**Button Sizes:**
- `xs` - Extra small
- `sm` - Small
- `md` - Medium (default)
- `lg` - Large

### Cards

```blade
<flux:card class="space-y-6">
    <flux:heading size="md">{{ __('admin.title') }}</flux:heading>
    <flux:text>{{ __('admin.description') }}</flux:text>
    <!-- Card content -->
</flux:card>
```

### Page Headers

```blade
<flux:page-header
    :heading="__('admin.posts.heading')"
    :description="__('admin.posts.description')"
>
    <flux:button color="primary" icon="plus" wire:click="create">
        {{ __('admin.create') }}
    </flux:button>
</flux:page-header>
```

### Callouts

```blade
<flux:callout color="green">
    {{ __('admin.success_message') }}
</flux:callout>

<flux:callout color="amber">
    {{ __('admin.warning_message') }}
</flux:callout>

<flux:callout color="red">
    {{ __('admin.error_message') }}
</flux:callout>
```

## Accessibility Features

### ARIA Labels

All interactive elements include appropriate ARIA labels:

```blade
<input
    id="search"
    type="search"
    aria-label="{{ __('admin.search.label') }}"
    wire:model.live.debounce.300ms="search"
/>

<button
    type="button"
    wire:click="delete({{ $id }})"
    aria-label="{{ __('admin.delete_item', ['name' => $item->name]) }}"
>
    {{ __('admin.delete') }}
</button>
```

### Keyboard Navigation

- All interactive elements are keyboard accessible
- Tab order follows logical flow
- Enter key submits forms
- Escape key cancels modals and inline edits
- Arrow keys navigate sortable headers

### Screen Reader Support

- Table headers use `scope="col"` attribute
- Sort indicators include `aria-sort` attribute
- Loading states announce to screen readers
- Form validation errors are associated with inputs

### Focus Management

- Focus indicators visible on all interactive elements
- Focus trapped in modals
- Focus restored after modal close
- Skip links available for table navigation

## Styling Patterns

### Color Scheme

**Light Mode:**
- Background: `bg-white`, `bg-slate-50`
- Text: `text-slate-900`, `text-slate-700`
- Borders: `border-slate-200`
- Hover: `hover:bg-slate-100`

**Dark Mode:**
- Background: `dark:bg-slate-900`, `dark:bg-slate-800`
- Text: `dark:text-slate-100`, `dark:text-slate-300`
- Borders: `dark:border-slate-800`
- Hover: `dark:hover:bg-slate-800/70`

### Spacing

- Card padding: `p-4` to `p-6`
- Section gaps: `space-y-4` to `space-y-6`
- Button gaps: `gap-2` to `gap-3`
- Form field spacing: `space-y-2` to `space-y-4`

### Border Radius

- Inputs: `rounded-xl`
- Buttons: `rounded-lg`
- Cards: `rounded-2xl`
- Badges: `rounded-full`

### Shadows

- Cards: `shadow-sm`
- Modals: `shadow-2xl`
- Dropdowns: `shadow-lg`
- Hover states: `hover:shadow-md`

## Best Practices

### 1. Consistent Component Usage

Always use the provided components rather than custom HTML:

```blade
<!-- ✅ Good -->
<x-admin.status-badge status="published" />

<!-- ❌ Bad -->
<span class="badge badge-green">Published</span>
```

### 2. Loading States

Always provide loading feedback for async actions:

```blade
<flux:button 
    wire:click="save" 
    wire:loading.attr="disabled"
    wire:target="save"
>
    <span wire:loading.remove wire:target="save">Save</span>
    <span wire:loading wire:target="save">Saving...</span>
</flux:button>
```

### 3. Empty States

Always provide meaningful empty states:

```blade
@forelse ($items as $item)
    <!-- Content -->
@empty
    <x-admin.table-empty 
        colspan="6" 
        :message="__('admin.no_items_found')" 
    />
@endforelse
```

### 4. Accessibility

Always include ARIA labels and keyboard support:

```blade
<button
    type="button"
    wire:click="action"
    aria-label="{{ __('admin.action_description') }}"
>
    <flux:icon name="icon-name" />
</button>
```

### 5. Responsive Design

Use responsive utilities for mobile-friendly layouts:

```blade
<div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <!-- Content adapts to screen size -->
</div>
```

## Testing Selectors

Components include data attributes for testing:

```blade
<x-admin.table data-admin-table="true" data-admin-table-id="posts">
    <!-- Table content -->
</x-admin.table>

<flux:button data-admin-create-trigger wire:click="create">
    Create
</flux:button>

<x-admin.table-row data-row-id="{{ $item->id }}" data-row-label="{{ $item->name }}">
    <!-- Row content -->
</x-admin.table-row>
```

## Component Locations

All admin UI components are located in:
- `resources/views/components/admin/` - Admin-specific components
- `resources/views/components/ui/` - Shared UI components
- Flux components are provided by the Flux UI package

## Related Documentation

- [Livewire Traits Guide](../livewire/LIVEWIRE_TRAITS_GUIDE.md)
- [Volt Component Guide](../volt/VOLT_COMPONENT_GUIDE.md)
- [Admin Configuration](./ADMIN_CONFIGURATION.md)
- [Accessibility Implementation](../accessibility/ACCESSIBILITY_IMPLEMENTATION_SUMMARY.md)
