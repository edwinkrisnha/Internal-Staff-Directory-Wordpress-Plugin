# Internal Staff Directory

[GitHub](https://github.com/edwinkrisnha/Employee-Directory)

A lightweight WordPress plugin for internal staff directories. Provides a searchable, filterable employee directory with AJAX-powered search, department filtering, and extended user profile fields.

## Features

- **Searchable directory** – Search employees by name, email, or username
- **Department filtering** – Instant filtering by department with no page reload
- **AJAX-powered** – Debounced search, live filtering, and pagination without full page refreshes
- **Extended user profiles** – Adds Department, Job Title, Phone, Office/Location, Bio, Photo URL, LinkedIn URL, and Start Date fields to WordPress user profiles
- **Individual profile pages** – Each employee card links to `/staff/{username}` — a full profile page rendered inside the active theme
- **AJAX pagination** – Numbered page navigation; search or filter resets to page 1 automatically
- **Three-state view toggle** – Switch between grid, compact list, and vertical card layouts; preference saved to `localStorage`
- **Department color stripes** – Cards get an auto-assigned colored left border based on department name
- **Adjustable photo size** – Admin setting: Small (40 px) / Medium (64 px) / Large (96 px)
- **LinkedIn links** – Employee cards and profile pages link directly to LinkedIn profiles
- **Years at company** – Tenure displayed on cards; full start date shown on profile pages
- **Send message quick action** – Configurable button on each card: Email, Microsoft Teams, or hidden
- **Copy email** – Inline copy icon next to each email address; click to copy to clipboard, icon flashes green to confirm
- **Social & Contact fields** – Eight social/contact fields per employee (WhatsApp, Telegram, Discord, Instagram, Facebook, Twitter/X, YouTube, TikTok). Each user can show or hide individual fields from their WP Profile page. Social fields appear as a compact icon row on cards and as labeled links on profile pages
- **New hires spotlight** – `[employee_new_hires]` shortcode renders a card grid of employees who joined within the configurable **"New" badge window** (Settings → Internal Staff Directory). Sorted newest-first; no search or filter controls — use it as a homepage or sidebar widget. Card fields are controlled independently via **New hire card fields** in settings
- **My Profile header widget** – `[employee_my_profile]` shortcode renders a compact, inline profile chip for the currently logged-in user: circular avatar, display name, and job title. Designed for headers, nav bars, and widget areas. Photo size, name, title, and profile-page link are each independently toggleable via shortcode attributes
- **Birthday spotlight** – `[employee_birthdays]` shortcode renders a festive horizontal-scroll carousel of employees whose birthday falls within a configurable day window relative to today. Each portrait card shows a circular photo, name, job title, department, formatted birthday date, and a pill label ("🎂 Today!" / "🎈 In X days"). Cards cycle through five gradient color themes; "Today" cards glow gold with a pulsing photo ring. Cross-year boundaries handled correctly. Window defaults set in settings; overridable per shortcode
- **Unified avatar** – The plugin photo (or a generated [DiceBear](https://www.dicebear.com/) avatar when none is set) is used everywhere WordPress renders an avatar — directory cards, profile pages, comments, author pages, and admin screens. Style is configurable in settings; avatar is seeded from the employee's display name
- **Responsive card grid** – Clean card layout that adapts to all screen sizes
- **Accessibility-first** – ARIA labels, screen reader text, and live regions for dynamic updates
- **Conditional asset loading** – CSS and JS only load on pages that use the shortcode or the profile page
- **Admin settings page** – Configure results per page, visible card fields, photo size, color stripes, message platform, included roles, and login requirement from **Settings → Internal Staff Directory**

## Requirements

- WordPress 5.0+
- PHP 5.6+
- jQuery (bundled with WordPress)

## Installation

1. Upload the `internal-staff-directory` folder to `/wp-content/plugins/`
2. Activate the plugin through **Plugins** in the WordPress admin
3. Add the shortcode to any page or post

## Usage

### Shortcode

Place the directory on any page or post:

```
[employee_directory]
```

No attributes are required. The shortcode renders the full searchable directory with department filter.

Optional attributes:

| Attribute | Example | Description |
|---|---|---|
| `department` | `"Engineering"` | Lock the directory to one department; hides the department dropdown |
| `per_page` | `10` | Override the results-per-page setting |
| `role` | `"editor"` | Restrict results to a single WordPress role slug |

### New hires shortcode

Display only employees who joined within the configured **"New" badge window**:

```
[employee_new_hires]
```

| Attribute | Example | Description |
|---|---|---|
| `per_page` | `6` | Limit how many cards are shown (default: plugin setting) |
| `role` | `"subscriber"` | Restrict to a single WordPress role slug |

Results are sorted newest-first. The window is set via **Settings → Internal Staff Directory → "New" badge window**.

### My Profile shortcode

Display a compact profile chip for the currently logged-in user — ideal for headers and nav bars:

```
[employee_my_profile]
```

| Attribute | Default | Description |
|---|---|---|
| `photo_size` | `small` | Avatar size: `small` (40 px), `medium` (64 px), `large` (96 px), or `none` (no photo) |
| `show_name` | `1` | Set to `0` to hide the display name |
| `show_title` | `1` | Set to `0` to hide the job title |
| `link` | `1` | Set to `0` to disable the link to the employee's profile page |
| `fallback` | `hide` | What to render when the visitor is not logged in: `hide` (nothing) or `login` (a login link) |

**Examples:**

```
[employee_my_profile photo_size="medium" show_title="0"]
[employee_my_profile photo_size="none" link="0"]
[employee_my_profile fallback="login"]
```

> **Elementor:** Add a **Shortcode** widget to your global header section and paste the `[employee_my_profile]` shortcode. The plugin stylesheet is automatically enqueued so the widget is styled on every page.

### Birthdays shortcode

Display employees whose birthday (month and day) falls within a configurable window around today:

```
[employee_birthdays]
```

| Attribute | Example | Description |
|---|---|---|
| `days_before` | `3` | Include birthdays up to this many days ago (default: plugin setting) |
| `days_after` | `7` | Include birthdays up to this many days ahead (default: plugin setting) |
| `role` | `"subscriber"` | Restrict to a single WordPress role slug |

Results are displayed as a horizontal-scroll carousel. Each portrait card shows a circular photo, name (linked to the profile page), job title, department, formatted birthday date (e.g. "March 15" — only when Birthday is enabled in Visible card fields), and a pill label: "🎂 Today!", "🎈 In X days", or "X days ago". Cards are sorted today-first, then upcoming, then past. "Today" cards get a gold gradient, animated glow, and pulsing photo ring.

Window defaults are set via **Settings → Internal Staff Directory → Birthday window**. Per-shortcode attributes override those defaults.

### Settings

Go to **Settings → Internal Staff Directory** to configure:

| Setting | Default | Description |
|---|---|---|
| Results per page | 200 | Maximum employees shown (1–500) |
| User roles to include | All roles | Restrict the directory to specific WordPress roles |
| Visible card fields | All fields | Show or hide Department, Job Title, Phone, Office/Location, Bio, LinkedIn URL, Start Date, Birthday (month & day) |
| Require login to view | Off | When on, guests see a login prompt instead of the directory |
| Profile photo size | Medium (64 px) | Card photo diameter: Small (40 px), Medium (64 px), or Large (96 px) |
| Department color stripe | On | Color-code each card's left border by department (auto-assigned) |
| Send message platform | None | Show a quick-action button on cards: None, Email (mailto:), or Microsoft Teams |
| Avatar fallback style | Big Smile | DiceBear style used when an employee has no profile photo — [31 styles available](https://www.dicebear.com/styles/) |
| "New" badge window | 90 days | Employees who joined within this many days get a "New" badge. Set to 0 to disable |
| New hire card fields | Department, Job Title, Start Date | Fields shown on `[employee_new_hires]` cards (independent from Visible card fields) |
| Birthday window — days before | 7 | Include birthdays up to this many days in the past in `[employee_birthdays]` (0–30) |
| Birthday window — days after | 7 | Include birthdays up to this many days ahead in `[employee_birthdays]` (0–30) |
| Birthday columns | 3 | Number of columns shown in the `[employee_birthdays]` spotlight: 1, 2, or 3. Replaces the horizontal scroll carousel with a grid layout |
| Grid columns | 3 | Number of columns shown in grid view: 1, 2, or 3. List and vertical views are not affected |
| New hire photo size | Medium (64 px) | Card photo diameter on `[employee_new_hires]` cards: Small (40 px), Medium (64 px), or Large (96 px). Independent from the main directory photo size |
| New hire columns | 3 | Number of columns shown in the `[employee_new_hires]` spotlight: 1, 2, or 3 |
| Available views | Grid, List, Vertical | Which view modes visitors can switch between. The view switcher is hidden when only one is enabled. Grid is always the minimum fallback |

### Profile Fields

Each WordPress user gains additional fields on their profile page (under **Profile** in the admin, standard user edit screen, or the **Staff** tab in Settings → Internal Staff Directory):

| Field | Meta Key | Description |
|---|---|---|
| Department | `employee_dir_department` | Team or department name |
| Job Title | `employee_dir_job_title` | Role or position |
| Phone | `employee_dir_phone` | Contact phone number |
| Office / Location | `employee_dir_office` | Physical office or remote location |
| Bio | `employee_dir_bio` | Short biography |
| Profile Photo URL | `employee_dir_photo_url` | Direct URL to a profile photo |
| LinkedIn URL | `employee_dir_linkedin_url` | Full LinkedIn profile URL |
| Start Date | `employee_dir_start_date` | Month/year joined (YYYY-MM); shown as tenure on cards |
| Birthday | `employee_dir_birth_date` | Month and day only, no year (MM-DD); used by `[employee_birthdays]`. Feb 29 is accepted |
| WhatsApp | `employee_dir_whatsapp` | Phone number with country code; links to `wa.me` |
| Telegram | `employee_dir_telegram` | Username or phone; links to `t.me` |
| Discord | `employee_dir_discord` | Username (display only — no universal link) |
| Instagram | `employee_dir_instagram` | Username; links to `instagram.com` |
| Facebook | `employee_dir_facebook` | Full profile URL |
| Twitter / X | `employee_dir_twitter` | Username; links to `x.com` |
| YouTube | `employee_dir_youtube` | Full channel URL |
| TikTok | `employee_dir_tiktok` | Username; links to `tiktok.com/@` |

Each user controls per-field visibility from their WP Profile page via **"Show in directory"** checkboxes. Stored as `employee_dir_hidden_social_fields` usermeta.

Users with the `edit_user` capability can edit these fields. Employees can update their own fields from the **Profile** screen.

## Data Storage

No custom database tables are created. All employee data is stored in the native WordPress `wp_usermeta` table using the `employee_dir_` prefix.

## Hooks

### Actions

| Hook | Description |
|---|---|
| `wp_ajax_employee_dir_search` | AJAX search handler for logged-in users |
| `wp_ajax_nopriv_employee_dir_search` | AJAX search handler for public visitors |
| `show_user_profile` | Renders profile fields on the user's own profile page |
| `edit_user_profile` | Renders profile fields on the admin user edit page |
| `personal_options_update` | Saves fields when a user updates their own profile |
| `edit_user_profile_update` | Saves fields when an admin updates a user profile |
| `employee_dir_card_after` | Fires inside each card `<article>` after all built-in fields — use to inject custom content. Receives `WP_User $user, array $profile`. |

### Filters

| Hook | Description |
|---|---|
| `employee_dir_query_args` | Modify `WP_User_Query` arguments before the employee query runs. Receives `array $query_args, array $args`. |
| `employee_dir_settings_defaults` | Override plugin setting defaults. Receives `array $defaults`. |
| `pre_get_avatar_data` | Built-in WP filter — hooked by the plugin to substitute the plugin photo (or DiceBear fallback) for any WP avatar call site-wide. |

## File Structure

```
internal-staff-directory/
├── internal-staff-directory.php    # Main plugin file — registers hooks and loads includes
├── includes/
│   ├── profile.php           # User meta read/write (field definitions, getters, savers)
│   ├── settings.php          # Admin settings page, Settings API registration, sanitizers
│   ├── profile-page.php      # Rewrite rule, template_redirect handler, profile URL helper
│   ├── directory.php         # Shortcode, WP_User_Query logic, AJAX handler, pagination
│   ├── admin.php             # Admin profile field UI (render and save)
│   └── hr-admin.php          # HR Staff tab: create/edit/remove/restore users with pagination
├── templates/
│   ├── directory.php         # Shortcode output markup (search, filter, results, pagination)
│   ├── profile-card.php      # Individual employee card partial
│   ├── profile-page.php      # Full employee profile page template
│   ├── new-hires.php         # [employee_new_hires] spotlight template
│   └── birthdays.php         # [employee_birthdays] spotlight template
├── assets/
│   ├── directory.css         # Responsive card grid and component styles
│   └── directory.js          # Debounced search, department filter, AJAX, DOM update
├── CHANGELOG.md
├── LICENSE
└── README.md
```

## Security

- All AJAX requests are validated with WordPress nonces (`check_ajax_referer`)
- User input is sanitized before use in queries (`sanitize_text_field`)
- All output is escaped before rendering (`esc_html`, `esc_url`, `esc_attr`)
- Phone numbers are filtered to digits and `+` only before storage

## License

[GPL-2.0-or-later](LICENSE)