# Elementor Design System for WooCommerce (color-changer-for-elementor) — Technical Specification

> **Handoff specification.** This document is the engineering companion to the product README. It describes the plugin's implementation — purpose, architecture, every feature, every data structure, every hook, and the rewrite roadmap. Read the product README first for the user-facing direction; read this document before touching any code.

---

## 1. What this plugin is

**Plugin name (display):** Elementor Design System for WooCommerce  
**Plugin folder / slug:** `color-changer-for-elementor`  
**Version:** 2.0.0  
**Namespace:** `ElementorColorChanger`  
**Text domain:** `color-changer-for-elementor`  
**License:** GPL-2.0+  
**Requires:** WP 5.8+, PHP 7.4+, WooCommerce, Elementor (free is enough; Pro optional)

**One-line purpose:** *Takes the colors and fonts an Elementor user already defined in their kit (Site Settings → Global Colors / Global Typography) and applies them automatically to every WooCommerce element — buttons, prices, sale badges, star ratings, tabs, cart table, checkout, notices, quantity inputs, My Account navigation, links, and shop-loop buttons — via a single cached CSS stylesheet served site-wide.*

**What it is NOT:** It is not a page builder. It does not touch WooCommerce layout, spacing, or sizing by default. It layers color (and optionally font-family/weight) over WooCommerce's own stylesheet using a generated stylesheet whose declarations can carry `!important`. It deliberately never replaces WooCommerce's structural CSS.

### Core design decision (v2.0)

From v1.x to v2.0 the plugin changed from *per-page-type generated CSS inlined into the page* to **one global stylesheet, written to a hashed file, enqueued on every page.**

Why:

- WooCommerce only recognises pages it routes itself. An Elementor-built shop page is a plain page to `is_shop()`, so page-type detection styled nothing on the most common modern store layout.
- Mini-carts in headers and product strips on the home page got no styling because slots were gated to a page type.
- Inlining ~6 KB into every response and forcing six cache variants cost more than the ~2 KB saved per page.

A single file, content-hashed into its filename, can be cached forever by the browser and costs one `<link>`.

---

## 2. Environment and current live state

Verified on the site this README lives on (2026-08-15):

| Item | Value |
|---|---|
| WordPress | 7.0 |
| PHP | 8.2.32 |
| Locale | en_AU |
| Active theme | Hello Elementor Child (child of Hello Elementor) |
| Elementor | 4.2.2 (Pro 4.2.1) |
| WooCommerce | 11.0.0 |
| Plugin status | **Active** |
| DB schema version (`eccw_db_version`) | 4 |
| Elementor kit ID (`elementor_active_kit`) | 6 |
| Generated stylesheet | `wp-content/uploads/eccw/colors-5fa80f5a246b.css` (present) |

Current option values (live):

- `eccw_settings` → `{ enabled: true, force_important: true, auto_contrast: true, dequeue_blocks: false }` (note: `derive_states` and `apply_typography` absent from stored value → defaults apply, see §5).
- `eccw_colors_mappings` → `version: 1`, `widgets:` all 12 registry widget types present, `dismissed_new`, `last_scan`.
- `eccw_css_generation` → a random token (`iEtJkTAN3pJM`), the cache-invalidation generation.

---

## 3. Architecture map

All classes live in namespace `ElementorColorChanger` under `includes/`, autoloaded by `includes/class-autoloader.php` which maps `ElementorColorChanger\X_Y` → `includes/class-x-y.php`.

### Bootstrap flow

```
Plugin file loads (wp-admin / front end)
  → define ECCW_VERSION / ECCW_PATH / ECCW_URL
  → require class-autoloader.php
  → register_activation_hook   → Activator::activate()
  → register_deactivation_hook → Plugin::deactivate()
  → Plugin::init()

Plugin::init()
  → before_woocommerce_init : declare HPOS + cart/checkout-blocks compatibility
  → plugins_loaded          : check_dependencies()
        if ! WooCommerce OR ! ELEMENTOR_VERSION → admin notice, stop
        else → load()
            → define ECCW_DISABLE if ?eccw_disable=1 & manage_options (safety kill-switch)
            → register_services()  (Container::set factories)
            → do_action('eccw_register_services')   (add-ons replace services here)
            → boot_services()
                  css      → wp_enqueue_scripts → CSS_Generator::enqueue_stylesheet()
                  kit save → elementor/kit/after_save → Cache_Manager::clear_css()
                  update   → upgrader_process_complete → clear css if this plugin updated
                  admin    → admin_init → maybe_upgrade() (schema migrations)
                  + init() each of: dequeue, admin, ajax, cron, adminbar
            → do_action('eccw_loaded')
  → init                  : load_textdomain()
```

### Class index (responsibilities)

| File | Responsibility | Key public API |
|---|---|---|
| `color-changer-for-elementor.php` | Entry point; constants; hook registration | — |
| `includes/class-autoloader.php` | PSR-4-ish autoloader | `spl_autoload_register` |
| `includes/class-plugin.php` | Orchestrator: dependency gate, service wiring, upgrade path, WC compatibility, text domain | `init()`, `deactivate()`, `maybe_upgrade()`, `on_plugin_update()` |
| `includes/class-container.php` | Minimal lazy DI container; add-ons may `replace()` | `set()`, `get()`, `replace()`, `has()`, `ids()`, `reset()` |
| `includes/class-features.php` | Feature gate for (future) paid capabilities | `has()`, `all()`, `is_pro()`, `flush()` |
| `includes/class-settings.php` | Typed plugin settings + CSS cache signature | `get()`, `all()`, `update()`, `sanitize()`, `css_signature()`, `defaults()`, `flush()` |
| `includes/class-element-registry.php` | **Selector map**: 12 WC widgets × 36 color slots + add-on widget map | `get_registry()`, `lookup()`, `normalize_key()`, `get_addon_map()`, `is_woocommerce_widget_type()` |
| `includes/class-heuristic-engine.php` | Sensible default color token per slot | `apply_defaults()`, `determine_color()` |
| `includes/class-mapping-service.php` | Shared mapping helpers (version bump, key migration, defaults seeding, elementor traversal, sanitize) | `bump_version()`, `normalize_all()`, `ensure_defaults()`, `merge_new_widgets()`, `walk_elements()`, `sanitize_mappings()` |
| `includes/class-css-generator.php` | **Core engine**: kit colors, WCAG math, state derivation, CSS build/minify, stylesheet file write, enqueue | `enqueue_stylesheet()`, `current_css()`, `write_stylesheet()`, `get_kit_colors()`, `resolve_color()`, `contrast_text()`, `contrast_ratio()`, `ensure_readable()`, `derive_state_color()`, `get_css_for_mappings()`, `build_single_css()` |
| `includes/class-typography.php` | Applies kit font-family/weight to WC selector groups | `css()`, `groups()`, `kit_typography()`, `sanitize_family()`, `sanitize_weight()` |
| `includes/class-cache-manager.php` | Transient CSS cache; generation-token invalidation (object-cache safe) | `get()`, `set()`, `generation()`, `clear_css()`, `clear_all()`, `flush()` |
| `includes/class-dequeue-manager.php` | Optionally removes WooCommerce Blocks stylesheets | `should_dequeue()`, `dequeue_styles()` |
| `includes/class-discovery-engine.php` | Scans `_elementor_data` for WC widget types | `scan_all_pages()` |
| `includes/class-cron-handler.php` | Daily background rescan | `rescan()` |
| `includes/class-admin-interface.php` | Settings screen (WooCommerce → Elementor Colors) + form save | `add_submenu()`, `render_settings_page()`, `handle_form_save()`, `enqueue_assets()` |
| `includes/class-ajax-handlers.php` | 3 AJAX endpoints | `dismiss_new()`, `rescan()`, `reset_defaults()` |
| `includes/class-admin-bar.php` | "Store Colors" master switch in admin bar | `add_node()`, `handle_toggle()` |
| `includes/class-activator.php` | Activation: seed settings + mappings, small scan, cron schedule, WC compat, notice | `activate()` |
| `uninstall.php` | Full cleanup, multisite-aware | `eccw_uninstall()` |

### Admin assets

- `admin/js/admin-settings.js` — bootstrap + shared helpers (`WooceSettings` global): kit color map, WCAG luminance/contrast-text, dirty/unsaved-changes guard.
- `admin/js/components/{element-row,gallery,dequeue-card,actions}.js` — per-component scripts.
- `admin/css/base.css` + `admin/css/components/{dequeue,elements,gallery,controls}.css` — admin styling.
- `admin/templates/settings-page.php` + `admin/templates/components/{header,notices,status-bar,elements-card,controls-card,dequeue-card,gallery-card,save-bar}.php` — admin markup.
- `languages/color-changer-for-elementor.pot`, `readme.txt` (WP.org), `CHANGELOG.md`, `uninstall.php`.

---

## 4. Features (current), detailed

### 4.1 Element registry — the selector map

The heart of the plugin is `Element_Registry`. It declares **12 WooCommerce widget types**, each with named **slots**. A slot is one styleable thing: a selector (or set of selectors), the interaction states it applies to, the CSS properties it paints, and optionally a `derives_from` relationship to a base slot (for auto-derived states).

**Widget types and slot counts (36 slots total):**

| Widget key | Label | Slots |
|---|---|---|
| `wc-add-to-cart` | Add to Cart Button | button_normal, button_hover, button_focus, button_disabled |
| `wc-product-price` | Product Price | price_regular, price_sale |
| `wc-sale-badge` | Sale Badge | badge_normal |
| `wc-star-rating` | Star Rating | stars_filled, stars_empty |
| `wc-product-tabs` | Product Tabs | tab_normal, tab_active |
| `wc-cart-table` | Cart Table | table_header, table_cell, proceed_button, proceed_hover, coupon_input, update_cart, update_cart_hover |
| `wc-checkout` | Checkout | place_order, place_order_hover, input_text, input_focus |
| `wc-notices` | Notices | success_border, success_icon, info_border, info_icon, error_border, error_icon |
| `wc-quantity-input` | Quantity Input | qty_input, qty_focus |
| `wc-account` | My Account | nav_link, nav_active |
| `wc-general-links` | General Links | link_normal, link_hover |
| `wc-loop-buttons` | Shop Page Buttons | loop_button, loop_hover |

**Slot shape:**
```php
array(
    'slot_id'      => 'button_normal',
    'label'        => __( 'Normal', 'color-changer-for-elementor' ),
    'selectors'    => array( '.single_add_to_cart_button', ... ),
    'states'       => array( 'normal' ),          // normal|hover|focus|active|disabled
    'properties'   => array( 'background-color', 'color' ),  // color|background-color|border-color|border-top-color|fill
    'derives_from' => 'button_normal',            // OPTIONAL: auto-derive from this base slot
)
```

**Add-on widget map** (`get_addon_map()`): routes third-party Elementor widget types to registry keys so discovery recognizes them — Essential Addons (`eael-woo-add-to-cart` → `wc-add-to-cart`, etc.) and Premium Addons (`premium-woo-products`, `premium-woo-cta`, `premium-mini-cart`, `premium-woo-categories`). This map is deliberately free/un-gated: Elementor free ships no WooCommerce widgets, so without it most sites would discover nothing.

**Widget-type recognition** (`is_woocommerce_widget_type()`): regex `woocommerce|^wc-|^eael-woo-|^premium-woo-|^eicon-woocommerce`.

**Key normalization** (`normalize_key()` / `lookup()`): resolves a raw widget type to a registry key via (1) direct hit, (2) add-on map, (3) `woocommerce-X` ↔ `wc-X` alias swapping.

### 4.2 Kit color resolution

`CSS_Generator::get_kit_colors()` reads the active kit's `_elementor_page_settings` post meta (`system_colors` + `custom_colors`, each `{ _id, color }`), producing `color_id → hex`. If the kit defines none, a fallback palette is used:

```
primary=#6EC1E4  secondary=#54595F  text=#7A7A7A  accent=#61CE70
```

Stored mapping values may be either a **kit token** (`primary`) or a **raw hex** (`#FF5733`). `resolve_color()` converts tokens to the kit's current hex and validates/sanitizes before the value ever reaches a stylesheet.

### 4.3 State derivation (hover/focus/active/disabled)

Setting `derive_states` (default **on**) means the user never picks interaction-state colors. Each state slot that declares `derives_from` gets its color computed from the base slot's color:

- **hover**: lightness ±10% (darken light colors, lighten dark ones)
- **active**: lightness ±16%
- **focus**: base color unchanged (the slot's own outline is the focus signal — accessibility)
- **disabled**: saturation ×0.35, lightness pushed toward 62% (washed out = inert)

All math preserves hue and saturation where possible (`hex_to_hsl` / `hsl_to_hex`). Turning `derive_states` off restores each stored manual color — nothing is discarded.

### 4.4 Readability / WCAG guard

Two protections, both on by default (`auto_contrast`):

1. **Background-painting slots** (a slot whose properties include `background-color`) force their own text/fill to black or white via `contrast_text()` (relative luminance threshold 0.179).
2. **Text-only slots** (prices, links, tabs, table headings, account nav) run `ensure_readable()`: if the chosen color's contrast against the assumed page background (`#ffffff`, overridable via `eccw_page_background` filter) is below **3.0:1**, lightness is nudged in 2% steps toward 4.5:1, preserving hue/saturation. Colors already legible are emitted verbatim.

WCAG helpers exposed: `relative_luminance()`, `contrast_ratio()`, `contrast_text()`, `ensure_readable()`.

### 4.5 Typography bridge (v2.0)

`Typography` reads the kit's `system_typography`/`custom_typography` (same `{ _id, ... }` shape as colors) and emits **font-family and font-weight only** — sizes and line-heights carry layout and are deliberately untouched. Token → selector group mapping (design decision):

- `secondary` → product loop titles, product page title (headings)
- `accent` → alt buttons, add-to-cart, checkout button (buttons)
- `text` → prices, table headings, account nav (body)

Gated by `apply_typography` (default **on**). Families are quoted and allowlisted; weights allowlist `normal|bold|lighter|bolder|[1-9]00`.

### 4.6 `!important` toggle (`force_important`)

Default **on** — WooCommerce and most themes ship very specific selectors, so generated declarations append `!important`. Off lets site CSS win where selectors overlap.

### 4.7 Master switch

Two places (v2.0):

1. **Admin bar** node "Store Colors" (`class-admin-bar.php`) — a dot + On/Off state, a "Turn my colors off/on" action (nonced `admin-post.php` round-trip that returns the user to the page they were on), a "nothing is deleted" note, and a settings link. Shown when `is_admin_bar_showing()` and `manage_options`.
2. **Settings screen** checkbox `enabled`.

> **Note (rewrite):** Per the product README §16 and approved decisions, the admin-bar master switch is being **removed entirely** in the rewrite; the settings toggle remains the single control.

### 4.8 Reset / Rescan / Dismiss-new (AJAX)

Three `wp_ajax_` endpoints, each nonce-checked (`eccw_admin_nonce`) + `manage_options`:

- `eccw_dismiss_new` — mark discovered widgets as seen (status `new` → `default`), track in `dismissed_new`.
- `eccw_rescan` — run `Discovery_Engine::scan_all_pages()`, normalize keys, merge new widgets (status `new`), ensure defaults, bump version, clear CSS.
- `eccw_reset_defaults` — rebuild all widget mappings from heuristic defaults, bump version, clear CSS.

> **Note (rewrite):** Per approved decisions, these move to the Advanced/developer area (not primary UI). Discovery stays automatic via activation + daily cron.

### 4.9 Discovery + daily cron

`Discovery_Engine::scan_all_pages()` queries published `page` and `product` posts that have `_elementor_data` meta (default limit 250, newest first; `eccw_scan_limit` / `eccw_scan_post_types` filters), JSON-decodes each layout, and walks the element tree collecting WooCommerce widget types. Used at activation (limit 50, to stay fast) and on a daily `eccw_daily_scan` cron hook (`Cron_Handler::rescan()`), with the same merge pipeline as the AJAX rescan.

### 4.10 Caching and the stylesheet file

`Cache_Manager` stores generated CSS in transients keyed `eccw_css_<md5>` with a 7-day TTL. **Invalidation is by generation token, not deletion**: every cache key embeds the current token (`eccw_css_generation` option), so bumping the token orphans all entries at once — this works identically whether or not a persistent object cache (Redis/Memcached) is in use. A row sweep of `_transient_eccw_css_%` is kept as housekeeping for non-object-cache hosts.

The stylesheet itself (`CSS_Generator::write_stylesheet()`) is written to `wp-content/uploads/eccw/colors-<md5(css+version)[:12]>.css` and enqueued with no `?ver` (the hash in the filename is the cache-buster). Old `colors-*.css` files are deleted when superseded. If the filesystem is read-only, the CSS falls back to `wp_add_inline_style()`.

Cache-key inputs: mappings version + serialized kit colors + `Settings::css_signature()` (i/c/d/t flags for force_important/auto_contrast/derive_states/apply_typography) + generation token.

### 4.11 Dequeue of WooCommerce Blocks CSS (`dequeue_blocks`)

Default **off**. When on, removes `wc-blocks-style` and `wc-blocks-vendors-style` — but only when mappings exist to replace them (see `Dequeue_Manager::should_dequeue()`), and never for admins. Off by default because the blocks sheet carries layout as well as color, so enabling it can shift block Cart/Checkout layout.

> **Note (rewrite):** Per approved decisions, remains a capability but only exposed under Advanced with a layout-change warning.

### 4.12 Uninstall

`uninstall.php` removes per-site data (`eccw_settings`, `eccw_dequeue_settings`, `eccw_colors_mappings`, onboarding flags, `eccw_css_generation`, legacy `eccw_pro_optin_email`, `_transient_eccw_%` rows, generated stylesheets, cron) and network-wide user meta (`eccw_history_*`, `eccw_draft`, `eccw_draft_time`). Multisite-aware: skips large networks (operator finishes deliberately) and otherwise pages through all sites with `switch_to_blog`.

### 4.13 Features previously removed (v2.0) — do not resurrect casually

- Onboarding wizard (4 steps) and its flags
- Live visual editor (click-to-customize) and its 8 AJAX endpoints + user drafts/history
- Share preview links
- Per-page-type CSS variants (replaced by the single global stylesheet)

---

## 5. Data schema

### Options

| Option | Autoload | Shape | Purpose |
|---|---|---|---|
| `eccw_settings` | yes | `{ enabled:bool, force_important:bool, auto_contrast:bool, dequeue_blocks:bool, derive_states:bool, apply_typography:bool }` | Typed behavior flags; missing keys fall back to defaults; legacy `eccw_dequeue_settings` migrated once |
| `eccw_colors_mappings` | **no** | `{ version:int, widgets:{ <widget_key>: { label:string, status:string('default'|'new'|'configured'), slots:{ <slot_id>: { color:string(token|hex) } } } }, dismissed_new:string[], last_scan:string }` | The saved color mappings (12 widgets) |
| `eccw_css_generation` | yes | random token string | Cache invalidation generation |
| `eccw_db_version` | yes | int (currently 4) | Schema migration guard |
| `elementor_active_kit` | — | int | Read by the plugin (not owned by it) |

**Schema version history (`eccw_db_version`):**
1. pre-1.5.1, implied
2. opt-in email purged; admin-only options de-autoloaded
3. onboarding flags removed with the wizard (2.0)
4. page-type CSS variants dropped; one stylesheet for the whole site

### Default color tokens (heuristic)

`Heuristic_Engine::$defaults` maps each of the 36 slot ids to a kit token. Summary of rules (`determine_color()`): buttons/proceed/place_order/update_cart/loop → `primary` (states hover/focus → `secondary`); prices → `primary`; sale → `accent`; badge/stars → `accent`; success → `accent`; info → `primary`; error → `text`; inputs/qty/coupon → `text` (focus → `primary`); nav/links → `text` (active/hover → `primary`); tabs → `text` (active → `primary`); table header/cell → `primary`. Explicit `$defaults` array overrides the pattern rules per-slot.

### Transients

- `eccw_css_*` — cached generated CSS (7-day TTL).
- `eccw_activation_notice` — one-shot activation notice (60 s).
- `eccw_share_*` — (legacy share previews; only cleared by `clear_all()`).

### User meta (legacy, cleaned on uninstall)

`eccw_history_*` (undo stacks), `eccw_draft`, `eccw_draft_time` — written by the removed Live Editor; no longer created.

---

## 6. Hooks reference (all `eccw_*`)

### Filters

| Filter | Signature | Default | Purpose |
|---|---|---|---|
| `eccw_kit_colors` | `(array $colors)` | read from kit | Extend/override the palette (`color_id => hex`) |
| `eccw_page_background` | `(string $hex)` | `#ffffff` | Background assumed for text-only readability checks |
| `eccw_auto_contrast_text` | `(bool, string $hex, array $properties)` | `Settings::get('auto_contrast')` | Disable auto label contrast per call |
| `eccw_force_important` | `(bool, array $properties)` | `Settings::get('force_important')` | Disable `!important` per call |
| `eccw_generated_css` | `(string $css, array $mappings)` | — | Append/alter final CSS before minify |
| `eccw_element_registry` | `(array $registry)` | registry | Add/replace widget definitions |
| `eccw_addon_widget_map` | `(array $map)` | add-on map | Widen third-party widget recognition |
| `eccw_slot_states` | `(string[] $states)` | all 5 | Restrict which states may be styled site-wide |
| `eccw_kit_typography` | `(array $tokens)` | read from kit | Extend/override typography tokens |
| `eccw_typography_groups` | `(array $groups)` | default groups | Remap token → selector groups |
| `eccw_sanitized_mappings` | `(array $clean, array $raw)` | — | Filter mappings after sanitization |
| `eccw_settings` | `(array $resolved)` | defaults merged | Override resolved settings |
| `eccw_feature_<key>` | `(bool $default, string $feature)` | defaults | Feature gate override |
| `eccw_is_pro` | `(bool)` | `false` | Declare paid add-on active/licensed |
| `eccw_scan_post_types` | `(string[] $types)` | `['page','product']` | Discovery post types |
| `eccw_scan_limit` | `(int $limit)` | 250 | Discovery scan cap |
| `eccw_show_admin_bar_switch` | `(bool)` | `true` | Hide admin-bar switch |
| `eccw_admin_data` | `(array $data)` | — | Alter settings-screen JS data |
| `eccw_settings_panels` | `(array $panels)` | `[]` | Register add-on settings panels |
| `eccw_service` | `(object $instance, string $id)` | — | Filter resolved service |

### Actions

| Action | Args | When |
|---|---|---|
| `eccw_register_services` | — | after default factories registered, before boot (add-ons `Container::replace()`) |
| `eccw_loaded` | — | after every service booted |
| `eccw_admin_assets_enqueued` | — | after settings-screen assets enqueued (add-ons enqueue here) |
| `eccw_settings_page_before` / `eccw_settings_page_after` | `($panels)` | top/bottom of settings page |
| `eccw_daily_scan` | — | daily cron rescan |

---

## 7. CSS generation pipeline (end to end)

1. `CSS_Generator::enqueue_stylesheet()` on `wp_enqueue_scripts` — bails if `ECCW_DISABLE` or `enabled=false`; gets `current_css()`; if non-empty, `write_stylesheet()` (hashed file, fallback inline).
2. `current_css()` — reads `eccw_colors_mappings` (widgets only); empty → return `''`. Otherwise compute `cache_key(version)` (mappings version + kit colors + `css_signature` + generation token) and read the transient; miss → build.
3. `prepare($mappings)` — attach each stored slot to its registry definition (`Element_Registry::lookup`), dropping slots/definitions that no longer match. Nothing is page-type filtered anymore.
4. `build_css($mappings)` — per widget: resolve base colors first (slots without `derives_from`), then per slot: resolve color → derive state color if enabled → for each selector × state, compute state selectors (hover→`:hover`, focus→`:focus`, active→`:active,.active`, disabled→`:disabled,.disabled`) → `build_rules()` → append. Disabled also appends `opacity:0.5 !important`. Appends `Typography::css()`. Applies `eccw_generated_css` filter. Minifies.
5. `build_rules($properties, $hex)` — decides text color (`contrast_text` if slot paints a background + auto_contrast; else `ensure_readable` for text-only; else verbatim), appends `!important` per `force_important`, emits one declaration per property (color/fill take the text color).
6. Cache the built CSS under the transient key.

### Selector/state matrix

| State | CSS selector forms |
|---|---|
| normal | `selector` |
| hover | `selector:hover` |
| focus | `selector:focus` |
| active | `selector:active`, `selector.active` |
| disabled | `selector:disabled`, `selector.disabled` |

### Color math (WCAG)

- `relative_luminance(hex)`: sRGB linearization, then `0.2126R + 0.7152G + 0.0722B`.
- `contrast_ratio(a,b)`: `(max(L)+0.05)/(min(L)+0.05)`.
- `contrast_text(bg)`: white on dark, `#111111` on light (luminance threshold 0.179).
- `ensure_readable(text, bg)`: below 3.0 → move lightness toward target 4.5, 2% steps, ≤50 iterations, hue/sat preserved; last resort `contrast_text`.
- `derive_state_color`: HSL lightness shifts (see §4.3).

### Minifier

Whitespace collapse, `; ` → `;`, `{ ` → `{`, ` }` → `}`, newline removal. Pure string work — no CSS parser.

---

## 8. Admin surface

### Settings screen (WooCommerce → Elementor Colors)

Single form posting to `admin-post.php?action=eccw_save_settings` (nonce `eccw_save_settings`). Renders cards:

- **Elements card** — one row per widget, each slot a `<select>` (or text) of kit colors + "custom hex" support; `WooceSettings` JS maps tokens ↔ hex, tracks dirty state, warns on unload.
- **Controls card** — checkboxes: `enabled`, `force_important`, `auto_contrast`, `derive_states`, `apply_typography`, `dequeue_blocks`.
- **Dequeue card** — blocks-dequeue toggle.
- **Gallery card** — kit color gallery / audit info.
- **Save bar** — Save, Reset to defaults (AJAX), Rescan (AJAX), dismiss-new count badge.

Form save: verifies nonce + `manage_options`; `Settings::update()` for checkboxes (every checkbox written explicitly, since unticked ones are absent from POST); normalizes keys; validates posted colors against kit IDs or hex; marks widget status `configured`; bumps mappings version; updates option; clears CSS; redirects with `?saved=1`.

JS bootstrap data (`eccwData` via `wp_localize_script`): `kitColors[]`, `defaults`, `newCount`, `ajaxUrl`, `nonce`, `siteUrl`, `features`.

> **Note (rewrite):** Per the product README, the settings screen is being redesigned to a 4-area layout (Status / Your Elementor Colors / WooCommerce Appearance / Advanced). The 36 slots become 12 user-facing groups. See Phase A deliverables.

### AJAX endpoints

`wp_ajax_eccw_dismiss_new` | `wp_ajax_eccw_rescan` | `wp_ajax_eccw_reset_defaults` (all nonce `eccw_admin_nonce` + `manage_options`).

---

## 9. Security & compatibility

- **Capability checks**: every admin/AJAX/action path requires `manage_options`; nonces everywhere (`eccw_admin_nonce`, `eccw_save_settings`, `eccw_toggle_enabled`).
- **Output sanitization**: colors allowlisted (`#rgb/#rrggbb/#rrggbbaa` hex and numeric `rgb()/rgba()/hsl()/hsla()`), font families allowlisted + quoted, weights allowlisted. Nothing unvalidated ever reaches the generated stylesheet (it is served on every front-end page).
- **WooCommerce compatibility declarations**: HPOS (`custom_order_tables`) and `cart_checkout_blocks` both declared compatible via `FeaturesUtil::declare_compatibility` at activation and on `before_woocommerce_init`.
- **`eccw_disable` kill-switch**: `?eccw_disable=1` + `manage_options` defines `ECCW_DISABLE`, skipping front-end output — a safe remote shutdown.
- **Object-cache safety**: cache invalidation is generation-token based, so Redis/Memcached hosts never serve stale CSS.
- **Autoload discipline**: heavy admin-only options (`eccw_colors_mappings`) are stored with autoload **off**; the settings flags are autoloaded because they are read on every CSS-generating front-end request.

### MUST-NEVER rules

- Never modify, deactivate, uninstall, or break the **Novamira** plugin — it is the connection to this agent. Also never revoke user ID 8's OAuth/Application Passwords.
- Never write PHP files outside `wp-content/novamira-sandbox/` via the `write-file`/`edit-file` abilities (they are blocked); write plugin PHP through `execute-php` (`file_put_contents`) or the upload link.
- Never remove plugin options the active install relies on (`eccw_settings`, `eccw_colors_mappings`, `eccw_css_generation`, `eccw_db_version`).

---

## 10. Rewrite roadmap

**Goal**: simplify the product UI (product README) and clean-rebuild the same plugin (same folder/namespace/DB keys), feature by feature, keeping the site functional after each step. Saved colors must survive via the existing option keys.

**Phases (per product README §33 + approved decisions):**

- **Phase A — Product simplification**: settings-page IA (4 areas), user-facing settings, internal-only vs removed features, default behavior, DB schema compatibility. (Admin-bar switch **removed**; blocks dequeue **advanced-only**; rescan/dismiss **advanced area**; 36 slots → 12 user-facing groups.)
- **Phase B — UI implementation**: rewrite `admin/templates/*` + `admin/css/*` + `admin/js/*` + `class-admin-interface.php` around the simplified product.
- **Phase C — Engine implementation**: continue the technical rewrite preserving options, mappings, CSS behavior, public APIs, compatibility.
- **Phase D — Validation**: saved colors survive; Elementor colors/typography detected; styling, typography, CSS generation, caching, disabling all work; no layout CSS unintentionally removed.

**Execution rules for every step:**
1. Preserve the exact public API (class names, method signatures, static calls, hook names) so un-rewritten classes keep working.
2. Keep all DB option keys and their shapes.
3. Back up the folder before changing it (`color-changer-for-elementor.bak`).
4. After writing, run `novamira/execute-php` to confirm: plugin class loads (`class_exists('ElementorColorChanger\Plugin')`), no fatal errors, and `eccw_colors_mappings` still reads.
5. Re-enable/verify front-end styling still emits the stylesheet.

**Status (2026-08-15):** Phase A in progress (design deliverables). Backup created at `color-changer-for-elementor.bak`. Product README now at plugin root; this file lives at `docs/technical-spec.md`.

---

_End of technical specification._
