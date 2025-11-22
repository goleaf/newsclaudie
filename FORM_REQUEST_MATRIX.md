# Controller FormRequest Matrix (2025‑11‑22)

| Controller & Method | Request class in use | Notes / next steps |
| --- | --- | --- |
| Volt `posts.index` (Livewire) | Validated inside component ✅ | Querystring filters + per-page are validated in `livewire/posts/index` before pagination runs. |
| Volt `categories.index` (Livewire) | Sanitised inside component ✅ | Search + per-page run through `ManagesPerPage`; admin-only deletes gated via policies. |
| Volt `categories.show` (Livewire) | Read-only ✅ | Loads counts + hands pagination to `livewire/category-posts`. |
| `PostController@create` | `Illuminate\Http\Request` ⚠️ | Needs dedicated request (to validate `draft_id` + ensure author authorization). |
| `PostController@store` | `StorePostRequest` ✅ | Already unique per method. |
| `PostController@edit` | `Illuminate\Http\Request` ⚠️ | Requires new request mirroring `create` behavior. |
| `PostController@update` | `UpdatePostRequest` ✅ | Existing coverage. |
| `PostController@publish` / `unpublish` / `destroy` | Route-model only ⚠️ | Introduce intent-specific FormRequests to centralize authorization + messaging. |
| `CategoryController@store` | `StoreCategoryRequest` ✅ | New per-method request with translated errors. |
| `CategoryController@update` | `UpdateCategoryRequest` ✅ | New per-method request with translated errors. |
| `CategoryController@destroy` | Route-model only ⚠️ | Could wrap deletion in a FormRequest for centralized policy/error handling. |
| `LocaleController@update` | `SetLocaleRequest` ✅ | Already localized via JSON. |
| `CommentController@store` | `StoreCommentRequest` ✅ | Handles authorization + validation now that Livewire form is gone. |
| `CommentController@edit` | Route-model only ⚠️ | Could add lightweight request to centralize policy checks. |
| `CommentController@update` | `UpdateCommentRequest` ✅ | Ensures edits stay within 1k characters. |
| `CommentController@destroy` | Route-model only ⚠️ | Still relies on controller policy call; consider dedicated request. |
| `ReadmeController::__invoke` | `Illuminate\Http\Request` ⚠️ | Should enforce `config('blog.readme')` via FormRequest authorize. |
| `Auth\RegisteredUserController@store` | `Auth\RegisterUserRequest` ✅ | Moves registration validation + config gate into JSON-backed messages. |
| `Auth\PasswordResetLinkController@store` | `Auth\ForgotPasswordRequest` ✅ | Validates email with translated errors. |
| `Auth\NewPasswordController@create` | `Auth\ShowResetPasswordRequest` ✅ | Ensures reset tokens are well-formed before showing the form. |
| `Auth\NewPasswordController@store` | `Auth\ResetPasswordRequest` ✅ | Full validation + localized errors. |
| `Auth\ConfirmablePasswordController@store` | `Auth\ConfirmPasswordRequest` ✅ | Validates password confirmation inputs. |
| `Auth\AuthenticatedSessionController@store` | `Auth\LoginRequest` ✅ | Already in place. |
| `Auth\AuthenticatedSessionController@destroy` | `Illuminate\Http\Request` ⚠️ | Consider `LogoutRequest` to validate CSRF/session state, even if no payload. |

✅ = compliant with “unique FormRequest + translated errors”. ⚠️ = still pending conversion.
