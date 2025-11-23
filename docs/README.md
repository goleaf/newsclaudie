# Documentation Overview

Markdown documentation now lives inside function-scoped folders under `docs/`:

- `docs/admin` — admin UI, configuration, and changelogs
- `docs/comments` — comment model architecture, performance, security, and changelogs
- `docs/news` — public news feature docs, security notes, and changelogs
- `docs/design-tokens` — token guides, performance, security, and component placeholders
- `docs/accessibility` — accessibility audits and testing guides
- `docs/interface` — interface architecture, audits, and migrations
- `docs/query-scopes` — query scope onboarding, references, and changelog
- `docs/optimistic-ui` — optimistic UI strategy and quick references
- `docs/planning` — planning placeholders (tasks/todo) relocated from the repo root
- `docs/testing`, `docs/validation`, `docs/security`, and other folders for supporting guides

Rules of thumb:
- Do not add Markdown to the repository root; place new docs in an existing function folder or create a new one under `docs/`.
- Keep cross-links relative to the current file (the repo includes updated relative paths).
- Run `npm run docs:verify` to ensure Markdown stays inside the `docs/` tree and out of disallowed locations.
