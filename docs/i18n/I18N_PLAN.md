# JSON Translation Coverage Plan

## Current coverage snapshot
- `lang/en.json` + `lang/es.json` now include navigation copy, locale validation, dashboard labels, comment flashes, and post filter validation.
- Legacy PHP-based translation files (`lang/en/validation.php`, `lang/en/blog.php`, etc.) still contain a large portion of historic strings referenced from Blade templates and FormRequests.

## Required actions
1. **Map Blade strings to JSON keys**
   - Audit `resources/views/**` for hard-coded English phrases (dashboard cards, post/editor labels, CTA buttons).
   - For each unique string, add an entry to `lang/en.json` (and future locales), then swap the Blade text for the `__('key')` helper.
2. **Migrate custom validation lines**
   - Move bespoke keys currently defined at the bottom of `lang/en/validation.php` (e.g., `category_*`) into JSON so that every FormRequest relies on the same translation source.
3. **Locale switcher UX**
   - Add per-locale descriptions (e.g., menu hints, badge copy) so future languages can reuse the same component without hard-coded strings.
4. **Controller / notification copy**
   - Standardize flash messages (`with('success', 'Successfully Published Post!')`, etc.) by routing them through JSON keys so they can be translated.

## Execution order
1. Complete the Blade string audit (pair with the Tailwind rewrite so structural changes happen once).
2. Port remaining validation lines into JSON and delete the custom section from `lang/en/validation.php`.
3. Add any additional locales (fr, de, etc.) using the same keys to verify the multi-language pipeline.

Tracking progress here ensures we can mark Priority 4 items in `tasks.md` as we migrate each logical area.

