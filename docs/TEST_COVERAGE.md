# Test Coverage Inventory (2025-11-22)

This document maps every PHP class under `app/` to its current automated coverage and highlights the next test that should exist (including the preferred style and any fixtures/configuration needed).

## Controllers

| Class | Current Coverage | Gap / Required Test (type + prerequisites) |
| --- | --- | --- |
| `Auth\AuthenticatedSessionController` | `tests/Feature/Auth/AuthenticationTest` exercises login/logout happy-path + failures. | Add regression feature test for throttling/lockout once rate-limiter is configured. Requires fake cache + multiple failed attempts. |
| `Auth\ConfirmablePasswordController` | `tests/Feature/Auth/PasswordConfirmationTest`. | Covered. |
| `Auth\EmailVerificationNotificationController`, `Auth\EmailVerificationPromptController`, `Auth\VerifyEmailController` | `tests/Feature/Auth/EmailVerificationTest`. | Covered. |
| `Auth\NewPasswordController`, `Auth\PasswordResetLinkController` | `tests/Feature/Auth/PasswordResetTest`. | Covered. |
| `Auth\RegisteredUserController` | `tests/Feature/Auth/RegistrationTest`. | Covered. |
| `CategoryController` | `tests/Feature/CategoryControllerTest`. | Covered for CRUD. Need feature test ensuring validation errors surface translated messages (requires `app.supported_locales`). |
| `CommentController` | `tests/Feature/CommentControllerTest` exercises store/update permissions. | Add feature tests that cover the destroy action + policy failures. |
| `LocaleController` | `tests/Feature/LocaleControllerTest`. | Add feature test verifying locales constrained to `config('app.supported_locales')`. |
| `MarkdownConverter` | `tests/Unit/MarkdownConverterTest` (2 scenarios). | Add unit test ensuring Torchlight comment without config enabled does not inject attribution. |
| `MarkdownFileParser` | `tests/Feature/MarkdownFileParserTest` (success paths). | Add tests for invalid author/date failures using temporary markdown fixture files. |
| `PostController` | `tests/Feature/PostTagInputTest` and `tests/Unit/CreatesNewPostTest` cover tag input and slugging flows. | Add coverage for publish/unpublish endpoints plus ensuring drafts keep `published_at` null via feature tests. |
| `ReadmeController` | No coverage. | Feature test to ensure `/readme` returns markdown output when `blog.readme` true and 404 otherwise (requires toggling config + fixture README). |

## Middleware

| Class | Current Coverage | Gap / Required Test |
| --- | --- | --- |
| `AnalyticsMiddleware` | None. | Feature/Integration test that forces env to `production`, enables analytics, and asserts `PageView` rows are recorded/not recorded for excluded paths. Requires manual invocation of terminating callbacks + seeded hashing salt. |
| `Authenticate`, `EncryptCookies`, `PreventRequestsDuringMaintenance`, `RedirectIfAuthenticated`, `TrimStrings`, `TrustHosts`, `TrustProxies`, `VerifyCsrfToken`, `EnsureUserIsNotBanned`, `SetLocaleFromSession` | Covered implicitly by framework; no bespoke behaviour. | Optional: lightweight unit tests only if custom logic is added later. |

## Requests

| Request | Current Coverage | Gap / Required Test |
| --- | --- | --- |
| `Auth\LoginRequest` | Exercised via auth feature tests. | Covered. |
| `Auth\RegisterUserRequest`, `Auth\ForgotPasswordRequest`, `Auth\ResetPasswordRequest`, `Auth\ConfirmPasswordRequest`, `Auth\ShowResetPasswordRequest` | Indirect coverage via auth feature tests. | Add unit tests for validation messages if translation regressions occur. |
| `SetLocaleRequest` | `tests/Feature/LocaleControllerTest`. | Covered. |
| `StorePostRequest`, `UpdatePostRequest` | Partially covered (`PostTagInputTest`). | Add unit tests for `prepareTagsInput` and `published_at` coercion; use request fakes. |
| `StoreCategoryRequest`, `UpdateCategoryRequest` | Covered indirectly by `CategoryControllerTest`. | Covered. |

## Models & Scopes

| Model / Scope | Current Coverage | Gap / Required Test |
| --- | --- | --- |
| `Category`, `Comment`, `Tag`, `User` | Not directly tested. | Add unit tests for relationships/scopes once domain logic is added. |
| `PageView` | `tests/Unit/PageViewTest`. | Add coverage for `normalizeDomain` edge cases (e.g. missing protocol) and `anonymizeRequest` salted hashing. |
| `Post` | `tests/Unit/PostModelTest`. | Covered for helper methods; add test for `scopePublished` once `PublishedScope` integrates. |
| `PublishedScope` | `tests/Unit/PublishedScopeTest`. | Covered for guest/author/admin visibility logic. |

## View Components

| Component | Current Coverage | Gap / Required Test |
| --- | --- | --- |
| Blade components under `resources/views/components/**` | None. | Once Tailwind refactor stabilizes, add Laravel view component tests to ensure props render expected markup. |

## Volt Components

| Component | Current Coverage | Gap / Required Test |
| --- | --- | --- |
| `livewire/posts/index` | `tests/Feature/PostIndexFilterTest` validates category filters + paginator rendering. | Add Livewire/browser test for tag + author filters and per-page validation errors. |
| `livewire/admin/posts/index` | `tests/Feature/AdminPostsPageTest` exercises access control + table rendering. | Add interaction tests for publish/unpublish actions (Livewire browser test). |
| `livewire/admin/categories/index` | `tests/Feature/AdminCategoriesPageTest` covers listing + access control. | Add test ensuring delete action requires confirmation/policies. |
| `livewire/admin/comments/index` | `tests/Feature/AdminCommentsPageTest` covers access control + table data. | Add tests for the Livewire delete action once browser suite is configured. |
| `livewire/admin/users/index` | `tests/Feature/AdminUsersPageTest` covers access control + listing. | Add interaction coverage for role/ban toggles via browser tests. |
| `livewire/admin/dashboard` | None. | Feature test should assert stats render expected counts + localization. |

## Browser Tests

| Test | Current Coverage | Gap / Required Test |
| --- | --- | --- |
| `tests/Browser/HomepageTest` | Playwright spec seeds a published post, visits `/`, and asserts the hero + latest posts heading render. | Extend to cover theme toggle/locale interactions if they receive regressions. |
| `tests/Browser/AdminNavigationTest` | Logs in via UI and visits each Flux admin route to confirm dashboards/posts/categories/comments/users render as expected. | Future enhancement: interact with Flux sidebar directly once the component exposes stable selectors. |
| `tests/Browser/AdminPostsPublishTest` | Publishes and unpublishes a draft from the Volt posts table to assert Livewire actions succeed. | Extend to comment moderation/user toggles when those table actions stabilize. |

## Console Commands / Providers

| Class | Current Coverage | Gap / Required Test |
| --- | --- | --- |
| `Console\Commands\*` | None. | Add feature tests using `artisan` helper to assert side effects (e.g. `CreateAdminUser` seeds user). |
| `Providers\*` | Not tested. | Rely on integration tests unless provider adds custom boot logic (then add unit test hooking container). |

## Priority Coverage Targets

1. **Markdown parser/converter** – add:
   - Failure test when markdown front matter references a missing author.
   - Regression test ensuring Torchlight attribution is suppressed when disabled even if the comment is present.
2. **Post creation flow** – add feature tests confirming categories sync, drafts skip `published_at`, and unauthorized users receive `403`.
3. **Analytics tracking** – add feature test for `AnalyticsMiddleware` verifying `PageView` creation and exclusion list behaviour.
4. **Locale workflow** – extend feature coverage to assert that the validation message uses `validation.locale_invalid` and that supported locale list is honoured.

## Test Harness Hardening Checklist

- [ ] Keep `phpunit.xml` pointed at the in-memory SQLite defaults (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`) for normal runs.
- [x] Document env overrides required for browser/Playwright tests (see README testing section + `npm run playwright:install`).
- [ ] Ensure `ParallelTesting::setUpProcess` seeds the database if/when factories become mandatory.
- [ ] Add `tests/ParallelTesting` hooks if tenant-specific config or external services must be faked per process.
