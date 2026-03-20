# Newsmast Curator - Project Context

## Overview
WordPress plugin for curating content from multiple sources and publishing to Mastodon.
**Repo:** `c:\xampp82\htdocs\wordpress\wp-content\plugins\newsmast-curator`
**Branch:** master
**Version:** 1.3.0 (DB schema 1.3.0)
**Language:** Portuguese (Brazilian) for UI strings

## Architecture
- PSR-4 autoloading with `NewsmastCurator\` namespace
- MVC-like: Models, Repositories (Base_Repository), Services, API Controllers (REST), Connectors
- Database abstraction via `Core\Database` class with prefixed table names
- WP-Cron scheduler for processing publications queue + external cron endpoint (`?nc_cron=1&token=SECRET`)

## Key Files
- **Connectors:** `includes/Connectors/` — Plone, WordPress, Tainacan (implements `Connector_Interface`)
- **Models:** `includes/Models/` — Item, Publication, Source, Collection, Mastodon_Account
- **Repositories:** `includes/Repositories/` — Base_Repository, Source_Repository, Mastodon_Account_Repository, etc.
- **Services:** `includes/Services/` — Scheduler_Service, Mastodon_Service, Logger_Service, Collection_Service
- **API:** `includes/API/` — REST controllers (Settings, Publications, Items, Sources, Collections, Mastodon_Accounts, Public_API)
- **Frontend:** `assets/js/admin.js` — Single-file JS with NC namespace, jQuery-based
- **Views:** `includes/Admin/views/` — PHP templates (sources.php, curation.php, queue.php, settings.php, collections.php, etc.)

## Publication Flow
1. Item collected from source → stored in items table
2. User curates (approves) item → status changes
3. User schedules publication (selects Mastodon accounts, adds hashtags, edits alt text) → creates one Publication per account with `scheduled` status
4. WP-Cron triggers `Scheduler_Service::process_scheduled_publications()`
5. Stuck recovery: pubs in 'processing' >5min reset to 'scheduled'
6. Lock mechanism (wp_options-based, 5min TTL) prevents concurrent runs
7. For each pending pub: resolve Mastodon account → upload image (with alt text) → post to Mastodon → mark published/failed
8. Failed pubs retry up to `nc_max_attempts` (default 3), reschedule +10min

## Multi-Mastodon Accounts
- `nc_mastodon_accounts` table: id, name, instance_url, access_token, username, is_default, status
- Model: `Mastodon_Account` with URL stripping, token masking, validation
- API: `Mastodon_Accounts_Controller` — CRUD, test connection, set default
- `Mastodon_Service::for_account($id, $db)` / `::get_default($db)` factory methods
- Legacy wp_options config (`nc_mastodon_instance`, `nc_mastodon_token`) supported as fallback
- Migration endpoint: `POST /settings/migrate-mastodon`
- Schedule modals: multi-account checkbox selector, one Publication row per account
- Queue view: "Conta" column showing target Mastodon account name

## Cron & Auto-Collection
- WP-Cron hook `nc_collect_sources` runs every 5 minutes → calls `collect_all_sources()` (collects ALL active sources)
- `ensure_cron_scheduled()` in Plugin::run() re-registers missing cron events on every page load
- External cron endpoint: `?nc_cron=1&token=SECRET` — `action=all` also collects all sources
- Per-source config: `auto_collect` (bool) + `collect_interval` (every_15_minutes to daily) — available but optional
- Items marked with `collection_type` = 'auto' or 'manual'
- Curation view shows "Auto" badge on auto-collected items

## Tainacan Connector (Subject-Based Curation)
- Search by keywords: `search_terms` config field → `?search=` API parameter
- `fetch_only` parameter includes `thumbnail` field (critical for images)
- `X-WP-Total` header captured for total count
- Test search via backend proxy: `POST /sources/test-tainacan` (avoids CORS)
- Image extraction: uses `thumbnail` field directly (no extra HTTP request), with size preference order
- Title fallback: `denominacao` metadata when title is empty
- Author: checks `autor`/`autoria` metadata before `author_name`
- Helper: `extract_metadata_value()` for flexible metadata lookup by slug or name
- Multiple sources from same collection with different search_terms for subject-based curation

## Curation View
- Grid and list views with toggle
- Source filter dropdown to filter items by source
- Items enriched with `source_name` and `search_terms` from Items_Controller
- Grid: source name + search terms in meta row
- List: dedicated "Fonte" and "Filtro" columns
- Bulk actions: approve, schedule, add to collection

## Schedule Modal (Hashtags & Alt Text)
- Custom hashtags input: tag chips with Enter/comma to add, appended to post content
- Editable alt text textarea (max 1500 chars) auto-generated from item metadata
- Alt text endpoint: `GET /items/{id}/alt-text` — generates structured alt text from Item::build_alt_text()
- Alt text stored in `publications.alt_text` column (DB migration 1.3.0)
- Scheduler uses publication's alt_text (user-edited) with auto-generate fallback
- Alt text structure: title, author, description, Tainacan metadata (material, dimensions, date, museum, collection), source URL

## Collections
- Items can be grouped into collections for batch management
- Collection detail modal: drag-and-drop reordering (HTML5 native), thumbnails, author/date metadata
- Reorder saved via `POST /collections/{id}/reorder` with `item_ids` array
- Individual items within a collection can be scheduled independently
- Bulk collection scheduling: staggered publication times with configurable interval
- Collection statuses: draft → scheduled → published/partial

## Public API (includes/API/Public_API_Controller.php)
- Authenticated via `X-NC-API-Key` header (or `api_key` query param fallback)
- Toggle: `nc_api_enabled` option, key: `nc_api_key` option
- Endpoints: GET /public/items, GET /public/items/{id}, POST /public/schedule, GET /public/publications, GET /public/publications/{id}
- POST /schedule: `item_id` (required), `scheduled_for` (required), `content` (optional), `mastodon_account_ids` (optional)

## Connectors & Collection Limits
- **Plone:** Scrapes HTML via DOMDocument+XPath. Configurable CSS selectors. Pagination via `b_start:int`. Configurable `max_pages` (1-10).
- **WordPress:** REST API with configurable `per_page` (default 20, max 100), categories filter
- **Tainacan:** REST API with `per_page`, `orderby`, `search_terms`, `fetch_only`. Uses `/wp-json/tainacan/v2/collection/{id}/items`
- `Collection_Service::collect_from_source()` inserts ALL items from connector with no limit/slice

## Conventions
- All strings use `__()` with `newsmast-curator` text domain
- Security: `sanitize_text_field`, `esc_url_raw`, `wp_kses_post`, nonce via `wp.apiFetch`
- API base: `ncData.apiUrl` in JS, `$this->namespace` in PHP controllers
- Token masking: saved tokens shown as `********`, empty/masked values never overwrite valid ones
- Timezone: always use `current_time('timestamp')` / `current_time('mysql')` for local time comparisons
- Commits pushed directly to master branch
