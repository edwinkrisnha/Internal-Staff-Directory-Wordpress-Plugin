# Changelog

All notable changes to Employee Directory will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [1.20.0] — 2026-03-02

### Added
- **Birthday field** — a new `birth_date` profile field (month + day only, no year stored) is now available on every employee's WP user edit screen and HR Staff tab. Stored as `employee_dir_birth_date` in `wp_usermeta` with format `MM-DD`. Feb 29 is accepted for leap-year birthdays.
- **`[employee_birthdays]` shortcode** — renders a card grid of employees whose birthday falls within a configurable window relative to today. Cards are sorted by upcoming birthdays first (today → future), then past birthdays. Supports optional attrs: `days_before`, `days_after`, `role`.
- **Birthday window settings** — two new fields under Settings → Internal Staff Directory: "Birthday window — days before" (0–30, default 7) and "Birthday window — days after" (0–30, default 7). Shortcode attrs override the global defaults per instance.
- **Birthday badge on cards** — when a card is rendered via `[employee_birthdays]`, a badge appears next to the employee's name showing "Today!", "In X days", or "X days ago".
- **Birthday card field (optional)** — `birth_date` is now available in the "Visible card fields" setting for both the main directory and new-hires widget. When enabled, the formatted birthday (e.g. "March 15") is displayed on regular directory cards. Default: off.
- `employee_dir_sanitize_birth_date( $value )` — validates and sanitizes a `MM-DD` birthday string using `checkdate()` against a leap year.
- `employee_dir_format_birthday_label( $offset )` — returns "Today!", "In X days", or "X days ago" from an integer day offset.
- `employee_dir_get_birthday_employees( $days_before, $days_after, $extra_args )` — fetches all users with a `birth_date` set, PHP-filters to the window (cross-year boundary safe), and returns sorted `[user, offset, profile]` entries.

## [Unreleased]

### Fixed
- **Directory card photo wrong size** — `flex-shrink: 0` was placed on `.ed-card__photo` (the `<img>`), which is not a flex item of `.ed-card`. The actual flex child is the `<a>` wrapper, which retained the default `flex-shrink: 1` and could be narrowed by the flex algorithm. Combined with a common theme reset (`img { max-width: 100% }`), this caused photos to render smaller than the configured size (e.g. 67 px instead of 96 px for the Large setting). Fixed by adding a new `.ed-card__photo-link` class to the `<a>` wrapper with `flex-shrink: 0; display: block; line-height: 0;`, and adding `max-width: none` to `.ed-card__photo` to prevent any theme reset from capping the image width.

### Added
- **Resigned employee flag** — admins can mark any employee as resigned from the WP user edit screen or the HR Staff tab edit form. Stores `employee_dir_resigned` (boolean) and `employee_dir_resigned_date` (YYYY-MM-DD) in `wp_usermeta`. Resigned employees remain visible in the directory (not filtered out) but receive a red "Former" badge next to their name on directory cards. On their profile page, a "Former Employee" badge appears inline with their name and an optional "Resigned" date row is shown in the details section.
- `employee_dir_is_resigned( $user_id )` — helper that returns `true` when a user is marked as resigned.
- **Employment Status section** on the WP user edit/profile screen — "Resigned" checkbox + "Resignation Date" date picker; gated to users with `edit_users` capability (employees cannot self-resign).
- **Employment Status section** on the HR Staff tab edit form, using the shared `employee_dir_admin_render_employment_status()` renderer.
- **HR Staff list view** now shows a red "Resigned" badge in the Status column for resigned employees (replacing "Active"; a user cannot be simultaneously active and resigned in the status display).
- `employee_dir_admin_render_employment_status( $profile )` — shared renderer for the Employment Status form section; used by both the WP admin screen and the HR Staff tab.

### Fixed
- `EMPLOYEE_DIR_VERSION` constant (`1.0.0`) was out of sync with the plugin header version (`1.17.0`), causing CSS/JS assets to be served with a stale cache-busting query string after updates. Constant is now set to `1.17.0`.
- Avatar fallback mismatch: the directory card used DiceBear while the profile page used `get_avatar_url()`, so the same user could show two different placeholder images. Both templates now call a shared `employee_dir_get_avatar_url()` helper.

### Added
- **Social & Contact profile fields** — eight new fields per employee: WhatsApp, Telegram, Discord, Instagram, Facebook, Twitter/X, YouTube, TikTok. Stored as `employee_dir_{key}` usermeta. Rendered as a compact icon row on directory cards and as labeled links on profile pages. Username-based fields (WhatsApp, Telegram, Instagram, Twitter, TikTok) auto-generate links; URL-based fields (Facebook, YouTube) store the full URL; Discord (no universal link) shows username as text only.
- **Per-user social field privacy** — a "Show in directory" checkbox per social field on the user's own WP Profile page (and admin/HR edit screens). Unchecking a field hides it from the directory card and profile page, stored as `employee_dir_hidden_social_fields` usermeta. Global admin settings control which field *types* can appear; per-user toggles control opt-out for individual values.
- `employee_dir_social_fields()` — canonical list of the 8 social field keys; single source of truth for all consumers.
- `employee_dir_get_hidden_social_fields( $user_id )` — returns the array of field keys a user has opted to hide.
- `employee_dir_social_link( $key, $value )` — returns `[ url, label ]` for a given key/value pair (URL is `null` for Discord).
- `employee_dir_social_icon_svg( $key )` — returns a hardcoded inline SVG icon for each platform; safe to echo directly.
- `employee_dir_admin_render_social_fields( $profile, $hidden_social )` — shared renderer used by the WP profile screen and HR Staff tab to output the Social & Contact table with show/hide checkboxes.
- **New hire card fields setting** — a dedicated **"New hire card fields"** checkbox group in Settings → Internal Staff Directory controls which fields appear on `[employee_new_hires]` cards independently from the main directory's **"Visible card fields"** setting. Default: Department, Job Title, Start Date. Name, email, and photo are always visible.
- **`[employee_new_hires]` shortcode** — renders a card-grid spotlight of employees whose start date falls within the **"New" badge window** configured in Settings → Internal Staff Directory. Sorted newest-first by default. Accepts `per_page` and `role` attributes. No search or filter controls — intended as a sidebar or homepage widget.
- `new_hires_only` arg in `employee_dir_get_employee_query()` — injects a `meta_query` clause filtering `employee_dir_start_date >= cutoff` (cutoff = today minus `new_hire_days`). Composable with existing `department`, `role__in`, and blocked-users filters.
- `employee_dir_get_avatar_url( WP_User $user, int $size )` — canonical avatar resolver used by both directory templates. Thin wrapper around `get_avatar_url()` now that the filter handles all logic.
- `pre_get_avatar_data` filter (`employee_dir_avatar_data`) — the plugin photo (or DiceBear fallback) now overrides the WordPress avatar system everywhere `get_avatar()` / `get_avatar_url()` is called (directory templates, comments, author pages, admin screens, etc.). Priority: plugin photo → DiceBear (seeded from display name, style from settings) → WP default for non-WP users (e.g. unregistered commenters).

### Changed
- HR Staff list view now paginates at 50 users per page (prev/next navigation with total count) instead of loading all WordPress users in a single unbounded query.

### Refactored
- Extracted `employee_dir_hr_profile_data_from_post()` helper in `hr-admin.php` to eliminate the duplicate `$profile_data` array construction that existed in both the save-user and create-user handlers.

### Added
- **HR Staff Management tab** — new **Staff** tab under Settings → Internal Staff Directory. HR can create new WP user accounts (with all employee directory fields in one form), edit any user's core WP fields (name, email, display name, role) and all `employee_dir_` meta fields, remove users from the directory (reversible — adds to the blocked list), and restore removed users. All actions guarded by `edit_users` capability with per-action nonces.
- **Blocked users** — admins can enter usernames or email addresses (one per line) under **Settings → Internal Staff Directory → Blocked users** to permanently exclude specific accounts from the directory. Stored as user IDs; applied as `exclude` in every `WP_User_Query` including AJAX searches.
- **"New" hire badge** — employees whose start date is within a configurable window (default: 90 days) get a small green "New" chip next to their name on the card. Window is set in **Settings → Internal Staff Directory → "New" badge window** (0–365 days; 0 disables the badge).
- **Shortcode attributes** — `[employee_directory]` now accepts `department`, `per_page`, and `role` attributes. Example: `[employee_directory department="Engineering" per_page="10" role="editor"]`. When `department` is locked via the shortcode, the department dropdown is hidden from visitors and the AJAX search always respects the locked value.
- **Sort dropdown** — filter bar now includes an A → Z / Z → A / Newest join date / Department sort selector. Selected sort is persisted to `localStorage` so the preference survives page reloads.
- **A–Z jump navigation** — an alphabet row above the results grid lets visitors jump to employees by the first letter of their name. Letter filter is AJAX-powered so pagination stays correct across the full dataset. Clicking the active letter (or "All") returns to the full list. Typing in the search box automatically clears any active letter filter (and vice-versa).

### Changed
- `employee_dir_get_employee_query()` now accepts `sort`, `letter`, and `role__in` args. Sort maps to safe, whitelisted `WP_User_Query` `orderby`/`order`/`meta_key` combinations. Letter filter overrides text search when set.
- AJAX handler now reads and forwards `sort`, `letter`, `per_page`, and `role` POST params.

### Added
- **Individual profile page** — each employee name on a card links to `/staff/{username}` via a custom WP rewrite rule. The profile page renders inside the active theme's header/footer and shows all fields unconditionally (ignores visible_fields setting).
- **AJAX pagination** — numbered page navigation below the results grid; clicking a page number fetches results without a page reload. Driven by the existing `paged` arg in `WP_User_Query`. Search or filter resets to page 1 automatically.
- **List/grid/vertical view toggle** — two buttons in the filter bar let visitors switch between card grid, compact list, and vertical portrait layout; preference persists in `localStorage`.
- **Department color stripe** — each card gets a 3 px left border color auto-assigned from an 8-color palette based on the department name (deterministic, no admin config needed). Toggleable via settings.
- **Adjustable photo size** — admin setting controls card photo diameter: Small (40 px), Medium (64 px, default), or Large (96 px).
- **LinkedIn URL profile field** — `employee_dir_linkedin_url` meta key; appears as a link on cards and profile pages.
- **Start Date profile field** — `employee_dir_start_date` meta key (YYYY-MM-DD); cards show computed tenure (e.g. "3 yrs"), profile pages show the full formatted date plus tenure.
- **Send message quick action** — admin setting: None (hidden), Email (mailto: link), or Microsoft Teams (`teams.microsoft.com/l/chat` URL using the employee's email). Rendered as a small action button on each card.
- **Photo click → profile page** — clicking the card photo navigates to the employee's full profile page.
- **Copy email icon** — inline copy icon (SVG) next to each employee's email address; clicking it copies the address to the clipboard via the Clipboard API; icon turns green briefly to confirm.
- `employee_dir_get_employee_query()` — new public function that returns the full `WP_User_Query` object (with `count_total => true`) for callers that need both results and total count.
- `employee_dir_get_profile_url( WP_User $user )` — returns the canonical `/staff/{user_nicename}/` URL for a given user.
- `employee_dir_pagination_html( $total_pages, $current_page )` — generates accessible pagination nav HTML with ellipsis compression; used by both the shortcode and the AJAX handler.
- `employee_dir_dept_color( $dept )` — returns a deterministic hex color for a department name.
- `employee_dir_years_at_company( $start_date )` — computes a human-readable tenure string from a YYYY-MM-DD date.
- `register_activation_hook` / `register_deactivation_hook` flush rewrite rules so the `/staff/` URL works immediately after activation.

### Changed
- **Start Date field** — replaced the free-text date input with two dropdowns (Month + Year) so users select rather than type. Saves as `YYYY-MM`. Existing `YYYY-MM-DD` values are automatically normalized on re-save.

### Added
- **Avatar fallback style setting** — admin setting under **Settings → Internal Staff Directory** to choose which of the 31 [DiceBear](https://www.dicebear.com/styles/) styles is used for employees without a profile photo. Styles are grouped into Minimalist and Characters. Default: Big Smile.

### Changed
- **Photo fallback** — replaced Gravatar with a [DiceBear](https://www.dicebear.com/) generated avatar (seeded from the employee's name) when no custom photo URL is set. Style is configurable in settings (31 styles available, default: Big Smile).
- `employee_dir_get_employees()` is now a thin wrapper around `employee_dir_get_employee_query()->get_results()` — no breaking change for existing callers.
- AJAX handler now returns `pagination` HTML alongside `html` in the JSON response.
- Settings page under **Settings → Internal Staff Directory** with four configurable options:
  - **Results per page** — replaces the hardcoded limit of 200 (range: 1–500)
  - **User roles to include** — filter which WordPress roles appear in the directory; empty = all roles
  - **Visible card fields** — toggle Department, Job Title, Phone, Office/Location, and Bio independently
  - **Require login to view** — when enabled, guests see a login prompt instead of the directory
- `employee_dir_get_settings()` helper centralises default-merging for all settings consumers
- Employee cards now display **First name + Last name** (from WordPress core profile fields), falling back to `display_name` when either is blank
- `employee_dir_query_args` filter — lets external code modify `WP_User_Query` arguments before the employee query runs
- `employee_dir_settings_defaults` filter — lets external code override plugin setting defaults
- `employee_dir_card_after` action — fires inside each card `<article>` after all built-in fields, enabling custom field injection

### Changed
- `employee_dir_get_employees()` default `per_page` is now driven by the settings value instead of a hardcoded `200`
- Role filter (`role__in`) applied to `WP_User_Query` when roles are configured in settings
- AJAX handler respects the "require login" setting and returns `wp_send_json_error` for unauthenticated requests when the restriction is active
- `$visible_fields` is now resolved once per render loop (in the parent template / AJAX handler) instead of once per card, removing repeated `get_option` calls inside the partial
- `employee_dir_get_departments()` result is cached in a 1-hour transient; cache is invalidated automatically when any user's department field is saved

## [1.0.0] — 2026-02-21

### Added
- `[employee_directory]` shortcode renders a searchable staff directory on any page
- Debounced AJAX search (300 ms) filtered by name and email via `WP_User_Query`
- Department dropdown filter with instant results on change
- Employee profile fields added to the WordPress user edit/profile screen: Department, Job Title, Phone, Office / Location, Bio, Profile Photo URL
- All profile data stored as user meta — no custom tables, no duplication
- Profile photo falls back to Gravatar when no custom URL is set
- Phone numbers rendered as `tel:` links with non-numeric characters stripped
- AJAX endpoint protected by nonce (`employee_dir_search`)
- Assets (CSS + JS) enqueued only on pages containing the shortcode
- Responsive card grid with `aria-live="polite"` for screen reader announcements
- Accessible focus styles and `screen-reader-text` utility class
