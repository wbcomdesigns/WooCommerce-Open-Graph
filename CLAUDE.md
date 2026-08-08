# Open Graph for WooCommerce (WooCommerce-Open-Graph)

## Plugin Identity
- **Plugin Name:** Open Graph for WooCommerce
- **Repo:** `WooCommerce-Open-Graph` (note: repo name is CamelCase, plugin dir is `open-graph-for-woocommerce`)
- **Main File:** `open-graph-for-woocommerce.php`
- **Text Domain:** `woo-open-graph`
- **Version:** 2.0.1
- **Author:** Wbcom Designs
- **License:** GPL v2 or later
- **Requires WordPress:** 5.0+
- **Requires PHP:** 7.4+
- **Requires WooCommerce:** yes (hard dependency, self-deactivates)
- **Tested up to:** WordPress 6.8.1
- **Pro Version:** none (single tier)
- **Store:** https://wbcomdesigns.com/downloads/woo-open-graph/

## Names & Identity

Every surface this product is known by. When these drift, a site owner reports a bug under one name and support searches for another.

| Surface | Value |
|---|---|
| Plugin Name (what the site owner sees) | `Open Graph for WooCommerce` |
| Install slug (`wp-content/plugins/`) | `open-graph-for-woocommerce` |
| Git repo | `WooCommerce-Open-Graph` |
| Text domain | `woo-open-graph` |
| readme.txt title | `Open Graph for WooCommerce` |
| Basecamp board | **none** |
| Basecamp URL | - |

**No Basecamp board exists for this product.** Bugs have no default home - raise this before filing one.

**Repo name does not match the install slug.** The repo is `WooCommerce-Open-Graph` (CamelCase) but the plugin installs as `open-graph-for-woocommerce`. Build and deploy scripts must use the install slug, never the repo name.

## Current Task List

Ordered by how many store owners are affected, not by how interesting the code is.
Derived from a code audit on 2026-08-08 that verified every open Basecamp card against this branch.
**Work happens on this branch (`2.0.2`).**

### 1. Social sharing is broken for most stores (board created 2026-08-08)
- [ ] **De-duplication has never worked.** `scan_existing_tags()` listens for `wp_head_early_og`, an action only this plugin fires, so all 13 guard sites are permanent no-ops. Any store with Yoast/RankMath/SEOPress gets duplicate `og:` tags. Buffer `wp_head` itself instead. (`includes/class-wog-meta-tags.php:50-79`)
- [ ] **Remove the "Compatible with: Yoast, RankMath, SEOPress" claim** at `admin/class-wog-admin.php:285` until the above actually works.
- [ ] **Image hints are hardcoded** 1200/630/`image/png` at `class-wog-meta-tags.php:334-337, 659-662, 682-685, 701-704`. Derive from `wp_get_attachment_metadata()`; omit rather than guess.
- [ ] **`og:image:alt` emitted twice** - delete the second emitter at `:510`.
- [ ] **`og:description` vanishes when the tagline is empty** (`:698`) - add a fallback chain: excerpt, then content, then store name.

### 2. Before the next release
- [ ] **Version-key the sitemap flush guard** (`includes/class-wog-sitemap.php:109-111`). It is keyed to a hand-written `_v2` literal that every install already has, so any future rewrite-rule change that forgets to bump it 404s on 100% of upgrades while passing every fresh-install test.

### Note
This product had no Basecamp board until 2026-08-08, which is why none of the above was ever reported. Repo name (`WooCommerce-Open-Graph`) is not the install slug (`open-graph-for-woocommerce`) - build scripts must use the slug.

### What this plugin should have and does not (10 of 16)

**Store owner expects:**

- [ ] **Gutenberg block** - Block themes often never fire the classic WooCommerce hooks this plugin renders through, so the owner sees nothing and has no way to place it by hand.
- [ ] **Theme-overridable templates** - The owner cannot restyle output without editing plugin files, which an update overwrites.
- [ ] **Admin screen for stored data** - Anything the plugin stores, the owner must be able to see, moderate and export from wp-admin. Otherwise support means phpMyAdmin.
- [ ] **First-run guidance** - After activation the owner should know what to do first, and the plugin should not silently change the storefront.
- [ ] **Clean uninstall** - Uninstalling should leave no tables, options or scheduled events behind.
- [ ] **CSS custom properties** - Without CSS custom properties the plugin cannot follow a theme or support dark mode without a rewrite.

**Developer extending it expects:**

- [ ] **REST API** - No mobile app, headless storefront or external integration can reach this data.
- [ ] **Documented hooks/filters** - Developers extending the plugin have to read the source to find the extension points.
- [ ] **Test suite** - Nothing catches a regression before a customer does.
- [ ] **WPCS config** - Coding-standard drift is invisible until a WordPress.org review rejects it.
### Frontend, UX & code health

**P0 first - nothing else on this plugin matters until it lands.** Saving any product or product category crashes the site: `schedule_sitemap_update()` is `private` at `includes/class-wog-sitemap.php:1189` yet registered as a hook callback at `:88-90`. Make it public, then grep every `add_action`/`add_filter` callback and confirm each target is public. Add a CI check for this class - one wrong visibility takes a store down.

- [ ] **No CSS custom properties at all** - 354 raw hex across 47kb of CSS, zero `var()` uses. Cannot follow a theme, cannot do dark mode. Prerequisite for any visual work.
- [ ] **176 functions in 12 files** - the highest density in the suite. Split by concern while fixing the P0.
- [ ] **Dead-code leads: 15.** Largest: `debug_sitemap_info()` (42 LOC), `get_section_settings()` (42), `migrate_old_settings()` (27), `import_settings()` (23), `get_system_info()` (20). Check `migrate_old_settings()` carefully - it may only be reachable on upgrade.
- [ ] **3 duplicate function bodies** inside the plugin.
- [ ] Rendered surface only partly reviewed - the plugin is deactivated on the audit store because its fatal blocks everything. Re-run the visual pass after the fix.

### The standard every plugin in this suite is measured against
We are not auditing against each plugin's own history - we are auditing against what a WooCommerce plugin **should** provide a store owner and a developer extending it. Scored across all 11 plugins on 2026-08-08.

| Expectation | Who needs it | Suite score |
|---|---|---|
| Gutenberg block | owner | **0 / 11** |
| Admin screen for stored data | owner | **0 / 11** |
| REST API | developer | **0 / 11** |
| Test suite | developer | **0 / 11** |
| WPCS config | developer | 2 / 11 |
| Documented hooks/filters | developer | 3 / 11 |
| Theme-overridable templates | owner | 4 / 11 |
| Shortcode fallback | owner | 5 / 11 |
| RTL stylesheet | owner | 8 / 11 |
| CSS custom properties | owner | 8 / 11 |
| Conditional asset loading | owner | 9 / 11 |
| Clean uninstall | owner | 9 / 11 |
| First-run guidance | owner | 9 / 11 |
| Translation file | owner | 10 / 11 |
| CI config | developer | 10 / 11 |
| Settings screen | owner | 11 / 11 |

**The four zeros are the real backlog.** Every plugin has a settings screen; not one has a block, an admin screen for the data it stores, a REST route, or a test. Those four gaps explain more customer complaints than the entire open bug list does.

### Portfolio floor - one mechanical pass per plugin
- [ ] **Focus rings** - `outline: none` with no `:focus-visible` replacement, **98 occurrences suite-wide**. Keyboard users cannot see where they are.
- [ ] **RTL** - raw `margin-left` / `margin-right`, **96 occurrences suite-wide**. Use `margin-inline-start/end`.
- [ ] **Icons** - **62** Dashicons references; migrate to Lucide with a map for stored values.
- [ ] **No native dialogs** - **12** `alert()`/`confirm()` calls put a raw browser dialog in front of a shopper mid-purchase.

### Ground rules
- **Dead-code lists are leads, not delete lists.** `init_form_fields()`, `get_content_html()` and `get_content_plain()` are `WC_Email` overrides invoked through the parent class - they look unreferenced to a static scan and **must not be removed**. The same applies to callbacks reached only by `add_action` string name and CSS classes built in JS.
- **Deduplicate at the seam.** Where free and Pro share an identical function body, the fix is one owner plus an extension point, never the same edit twice.
- **One concern per PR**, so a regression bisects fast.

### Ground rules for this list
- A card is a lead, not a spec. Several open cards were found to be already fixed or factually wrong about this tree - re-verify before building.
- Fix at the seam, not on the screen that reported it. Where a fix has a shared cause, the entry below says so.
- Most customers do not run our themes. Verify on a generic theme (Storefront or a block theme), not only on Reign/BuddyX.

## What It Does
Emits Schema.org markup, Open Graph and Twitter Card meta tags, and social share buttons for WooCommerce products. Fills the product-specific gaps that generic SEO plugins leave (price, availability, brand, ratings). Also generates a dedicated image sitemap.

## Architecture

### Pattern
Plain class-per-concern, no boilerplate loader. `Woo_Open_Graph` in the main file bootstraps and instantiates the `WOG_*` classes directly. Prefix for everything is `WOG_` / `wog_`.

### Key Files

| File | Purpose |
|------|---------|
| `open-graph-for-woocommerce.php` | Bootstrap, `Woo_Open_Graph` class, WooCommerce dependency guard, constants |
| `includes/class-wog-meta-tags.php` | Open Graph + Twitter Card `<meta>` output |
| `includes/class-wog-schema.php` | Schema.org / JSON-LD product markup |
| `includes/class-wog-social-share.php` | Front-end share buttons and share tracking |
| `includes/class-wog-sitemap.php` | Image sitemap generation and rewrite rules |
| `includes/class-wog-settings.php` | Settings storage, defaults, validation, import/export, migration |
| `includes/class-wog-meta-boxes.php` | Per-product meta box (OG title/description/disable) |
| `admin/class-wog-admin.php` | Admin screens and menus |

### Assets
- `assets/js/social-share.js` - share button behaviour
- `assets/css/social-share.css`, `assets/css/admin.css` (+ `rtl/` variants)
- `admin/css/woo-open-graph-admin.css` (+ `rtl/`)

Codebase: ~5,200 PHP LOC across 11 files.

## Constants

| Constant | Value |
|----------|-------|
| `WOG_VERSION` | `'2.0.1'` |
| `WOG_PLUGIN_FILE` | `__FILE__` |
| `WOG_PLUGIN_DIR` | `plugin_dir_path(__FILE__)` |
| `WOG_PLUGIN_URL` | `plugin_dir_url(__FILE__)` |

## Hooks & Filters (plugin-defined)

### Actions
| Hook | Fired when |
|------|-----------|
| `wog_init` | Plugin finished bootstrapping |
| `wog_product_meta_saved` | Per-product OG meta saved |
| `wog_setting_updated` | A single setting changed |
| `wog_settings_imported` | Settings imported from JSON |
| `wog_settings_migrated` | Settings migrated to a new schema |
| `wog_product_cache_cleared` | Product-level cache invalidated |
| `wog_category_cache_cleared` | Category-level cache invalidated |
| `wog_settings_cache_cleared` | Settings cache invalidated |

### Filters
| Hook | Purpose |
|------|---------|
| `wog_default_settings` | Default settings array |
| `wog_validated_settings` | Settings after validation, before save |
| `wog_product_meta_data` | Assembled product meta before output |
| `wog_max_images_per_product` | Cap on images emitted per product |
| `wog_sitemap_include_images` | Toggle images in the sitemap |
| `wog_config_summary` | Config summary shown in admin |
| `wog_system_info` | System info block |

## Settings & Data

### Options (`wp_options`)
| Option | Purpose |
|--------|---------|
| `wog_settings` | Main settings array (single option) |
| `wog_version` | Installed version, drives migrations |
| `wog_migration_completed` | Migration guard flag |
| `wog_sitemap_last_generated` | Sitemap generation timestamp |
| `wog_flush_rewrite_rules` / `wog_rewrite_rules_flushed_v2` | Rewrite-flush guards for the sitemap endpoint |

### Post Meta
| Key | Purpose |
|-----|---------|
| `_wog_og_title` | Per-product OG title override |
| `_wog_og_description` | Per-product OG description override |
| `_wog_disable_og` | Disable OG output for this product |

Also *reads* (does not own) `_brand`, `_wp_attachment_image_alt`, and `_yoast_wpseo_metadesc` to fill gaps when other plugins supply the data.

### AJAX actions
`wog_generate_sitemap`, `wog_test_sitemap`, `wog_track_share`

## Dependencies
- **WooCommerce** - hard dependency. The bootstrap deactivates the plugin and shows an admin notice when WooCommerce is absent.
- Interoperates with Yoast SEO meta when present, but does not require it.

## Development Notes
- **Settings live in ONE option** (`wog_settings`), not scattered keys. Add new settings to the defaults array and let `wog_validated_settings` sanitize them - do not add sibling options.
- **Sitemap uses rewrite rules.** Any change to the sitemap route must bump the flush guard option, otherwise the endpoint 404s on existing installs.
- **Meta output is cached.** When changing what a tag emits, clear the matching cache and fire the corresponding `wog_*_cache_cleared` action so downstream consumers stay in sync.
- Repo directory name (`WooCommerce-Open-Graph`) does not match the plugin slug (`open-graph-for-woocommerce`). Build and deploy scripts must use the plugin slug.
- No Basecamp board exists for this product as of August 2026; bugs have no default home column.
