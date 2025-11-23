# Tailwind & Local Asset Migration Checklist

The following Blade views still violate the “Tailwind-only, local assets only, no inline CSS/JS” requirements. Each item notes what must change so we can prioritize rewrites.

| File | Issue | Required action |
| --- | --- | --- |
| `resources/views/components/post-card.blade.php` | Inline `background-image` styling for `.featured-post-image`. | Convert to inline Tailwind-friendly style bindings (`style` attributes generated from sanitized data) or better: expose image via `<img>`/CSS class with `style` moved to CSS module. |
| `resources/views/components/dropdown.blade.php` | Uses `style="display: none;"` for Alpine.js dropdown. | Swap for `x-cloak` (already supported) so CSS handles hiding, remove inline style. |
| `resources/views/components/markdown-editor.blade.php` | Pulls EasyMDE CSS/JS from JSDelivr CDN. | Install EasyMDE via npm, import styles/scripts inside `resources/js/app.js`, and remove direct `<link>`/`<script>` tags from the Blade component. |
| _(removed)_ | Livewire modal was previously tracked here; component deleted as part of the Livewire purge. | n/a |

## Next steps
1. Add EasyMDE to `package.json`, import it via Vite, and expose component-specific initialization in `resources/js` so the markdown editor drops CDN links.
2. Introduce shared Tailwind component classes (e.g., `.scroll-panel` via `@layer components`) to replace remaining inline style usage in reusable components.

