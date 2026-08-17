# Elementor Design System for WooCommerce

**Technical & Functional Reference** — describes what this plugin currently does.

- **Plugin slug:** `color-changer-for-elementor`
- **PHP namespace:** `ElementorColorChanger`
- **Text domain:** `color-changer-for-elementor`
- **Version:** 2.0.0
- **Requires:** WordPress >= 5.8, PHP >= 7.4, WooCommerce, Elementor
- **License:** GPL-2.0+

---

## 1. What the plugin does

This plugin makes WooCommerce match the design system already defined in Elementor's **Global Colors** and **Global Typography** (the active Elementor kit). It reads those tokens and emits **one cached stylesheet** that restyles WooCommerce elements (buttons, prices, sale badges, ratings, tabs, cart, checkout, forms, notices, account navigation, links, shop buttons) so they look like they belong to the same site.

Key properties:
- **One generated stylesheet** (`colors-<hash>.css`) served to the front end — no per-page editing.
- **Automatic** — reads the kit, applies colors + fonts, no manual CSS.
- **Conservative** — only touches color and font-family/font-weight. It does **not** change layout, sizes, or line-height (those are considered design-system concerns that would break a checkout page).
- **Accessibility-aware** — enforces WCAG AA text contrast automatically.

---

## 2. Architecture / file map

All classes live in `includes/` under the `ElementorColorChanger` namespace and are autoloaded.

| File | Responsibility |
|------|----------------|
| `color-changer-for-elementor.php` | Plugin bootstrap; defines `ECCW_VERSION`, `ECCW_PATH`, `ECCW_URL`; loads autoloader; registers activation/deactivation hooks; calls `Plugin::init()`. |
| `includes/class-autoloader.php` | PSR-style autoloader for the namespace. |
| `includes/class-plugin.php` | Main plugin lifecycle; wires hooks; deactivation cleanup. |
| `includes/class-activator.php` | Activation routine; writes default options/mappings. |
| `includes/class-settings.php` | Settings model: defaults, get/update, sanitize, `css_signature()`, legacy migration. |
| `includes/class-element-registry.php` | The **registry**: defines every supported WooCommerce widget group, its color slots, states, and CSS properties. Also the third-party widget `addon_map`. |
| `includes/class-css-generator.php` | Reads kit colors, applies WCAG math + state derivation, builds the CSS, caches it, and writes the hashed stylesheet file. |
| `includes/class-typography.php` | Maps the kit's Global Typography tokens onto WooCommerce selector groups; emits font-family/font-weight rules. |
| `includes/class-cache-manager.php` | Cache generation token + transient storage for the generated CSS. |
| `includes/class-discovery-engine.php` | Discovers supported WooCommerce widgets/elements. |
| `includes/class-mapping-service.php` | Maps discovered widgets to registry groups. |
| `includes/class-heuristic-engine.php` | Heuristic fallbacks for recognizing widgets. |
| `includes/class-dequeue-manager.php` | Optional WooCommerce Blocks CSS dequeueing. |
| `includes/class-admin-interface.php` | Admin menu (submenu under WooCommerce, titled **Elementor Colors**), settings page render, JS/CSS enqueues. |
| `includes/class-ajax-handlers.php` | AJAX endpoints (rescan/reset/dismiss). |
| `includes/class-cron-handler.php` | Scheduled discovery. |
| `includes/class-features.php` | Feature gates. |
| `includes/class-container.php` | Service container. |
| `admin/templates/` | Settings page templates: `header`, `status-bar`, `controls-card`, `gallery-card`, `elements-card`, `advanced-card`, `save-bar`, `notices`, `element-row`. |

---

## 3. Data flow (end to end)

```
Elementor active kit (post meta _elementor_page_settings)
        |
        |  system_colors / custom_colors   (Global Colors)
        |  system_typography / custom_typography  (Global Typography)
        v
Element_Registry (widget groups -> slots -> states -> properties)
        |
        v
CSS_Generator::build_css()
        |  resolve_color()  -> WCAG contrast (MIN_TEXT_CONTRAST = 4.5)
        |  derive_state_color() -> hover/focus/disabled
        |  Typography::css() -> font-family / font-weight
        v
current_css()  (cached via Cache_Manager, keyed by cache_key())
        |
        v
write_stylesheet() -> wp-content/uploads/eccw/colors-<hash>.css
        |
        v
Front end enqueues the hashed stylesheet (handle: eccw-colors)
```

---

## 4. The Element Registry

`Element_Registry::get_registry()` returns the full set of supported WooCommerce widget groups. Each group has a `label` and a list of **slots**. Each slot has `properties` (which CSS properties it paints) and `states` (normal/hover/focus/disabled).

### Widget groups and their slots

| Widget type | Label | Slots (properties / states) |
|-------------|-------|------------------------------|
| `wc-add-to-cart` | Add to Cart Button | 0: bg/color/fill (normal); 1: bg/color (hover); 2: bg/color/border (focus); 3: bg/color (disabled) |
| `wc-product-price` | Product Price | 0: color (normal); 1: color (normal) |
| `wc-sale-badge` | Sale Badge | 0: bg/color (normal) |
| `wc-star-rating` | Star Rating | 0: color (normal); 1: color (normal) |
| `wc-product-tabs` | Product Tabs | 0: color (normal); 1: color (normal) |
| `wc-cart-table` | Cart Table | 0: color; 1: color; 2: bg/color; 3: bg/color (hover); 4: color/border; 5: bg/color; 6: bg/color (hover) |
| `wc-checkout` | Checkout | 0: bg/color (normal); 1: bg/color (hover); 2: color/border; 3: border (focus) |
| `wc-notices` | Notices | 0: border-top; 1: color; 2: border-top; 3: color; 4: border-top; 5: color |
| `wc-quantity-input` | Quantity Input | 0: color/border (normal); 1: border (focus) |
| `wc-account` | My Account | 0: color; 1: color |
| `wc-general-links` | General Links | 0: color (normal); 1: color (hover) |
| `wc-loop-buttons` | Shop Page Buttons | 0: bg/color (normal); 1: bg/color (hover) |

### Third-party widget mapping (`get_addon_map()`)

Maps third-party Elementor add-on widget slugs onto registry groups so they are recognized and styled:

| Add-on widget slug | Maps to |
|--------------------|---------|
| `eael-woo-add-to-cart` | wc-add-to-cart |
| `eael-woo-product-price` | wc-product-price |
| `eael-woo-product-rating` | wc-star-rating |
| `eael-woo-product-tabs` | wc-product-tabs |
| `eael-woo-cart` | wc-cart-table |
| `eael-woo-checkout` | wc-checkout |
| `eicon-woocommerce` | wc-loop-buttons |
| `premium-woo-products` | wc-loop-buttons |
| `premium-woo-cta` | wc-add-to-cart |
| `premium-mini-cart` | wc-cart-table |
| `premium-woo-categories` | wc-general-links |
| `product-grid-new` | wc-loop-buttons |
| `product-carousel-new` | wc-loop-buttons |
| `product-category-grid-new` | wc-general-links |
| `product-category-carousel-new` | wc-general-links |
| `single-product-new` | wc-add-to-cart |
| `mini-cart` | wc-cart-table |
| `wc-cart` | wc-cart-table |

`is_woocommerce_widget_type()` returns true for a widget if it is a known WC widget **or** appears in the addon map. `normalize_key()` converts any recognized slug to its canonical registry group.

---

## 5. Color resolution & accessibility

- **Source:** the active Elementor kit's `system_colors` and `custom_colors` (read from `_elementor_page_settings`). Falls back to a neutral palette if the kit defines none. Extensible via the `eccw_kit_colors` filter.
- **WCAG contrast:** `MIN_TEXT_CONTRAST = 4.5` (WCAG AA for normal text). The generator computes relative luminance and contrast ratio, and adjusts text colors so they remain readable against their background. `auto_contrast` setting controls this.
- **State derivation:** hover/focus/disabled colors are derived from the base color (`derive_state_color()`), controlled by the `derive_states` setting.
- **Focus treatment (K2):**
  - Border-only focus slots (inputs, quantity boxes) use the kit **primary** color for their focus border so focus is visible.
  - Background-painting focus slots (buttons) additionally get an `outline: 2px solid <contrast color>` + `outline-offset: 2px` halo, where the ring color contrasts with the assumed page background.

---

## 6. Typography mapping

`Typography::css()` maps the kit's Global Typography tokens onto WooCommerce selector groups:

| Kit token | Applied to |
|-----------|------------|
| `secondary` (H2 style) | product titles (loop + single) |
| `accent` | buttons (`.button.alt`, add-to-cart, checkout button) |
| `text` (body) | prices, table headers, account navigation |

Only **font-family** and **font-weight** are emitted (sizes/line-height are read but not emitted). Controlled by the `apply_typography` setting. The mapping is extensible via the `eccw_typography_groups` and `eccw_kit_typography` filters.

---

## 7. CSS generation & caching

- `CSS_Generator::current_css()` builds the CSS and caches it via `Cache_Manager` (transient), keyed by `cache_key()`.
- `cache_key()` folds in: mappings version, **kit colors**, **kit typography**, `Settings::css_signature()`, and the cache **generation token**. Changing any of these invalidates the cache.
- `write_stylesheet()` writes the CSS to `wp-content/uploads/eccw/colors-<hash>.css`, where the hash is `md5(css . ECCW_VERSION)`. A changed palette produces a new filename (cache-buster); old files are deleted as they are superseded.
- The front end enqueues the hashed file under the handle `eccw-colors`.
- `Cache_Manager::clear_css()` bumps the generation token, forcing regeneration.

---

## 8. Settings

Stored in the `eccw_settings` option. Defaults:

| Setting | Default | Meaning |
|---------|---------|---------|
| `enabled` | `true` | Master on/off for applying the design system. |
| `force_important` | `true` | Emit `!important` so the plugin wins over theme/plugin CSS. |
| `auto_contrast` | `true` | Enforce WCAG AA text contrast. |
| `dequeue_blocks` | `false` | Dequeue WooCommerce Blocks CSS (can affect layout; advanced). |
| `derive_states` | `true` | Auto-derive hover/focus/disabled colors. |
| `apply_typography` | `true` | Apply kit Global Typography to WooCommerce. |

Other options: `eccw_colors_mappings` (the widget->color mapping, versioned), `eccw_css_generation` (cache token), `eccw_db_version`.

---

## 9. Admin interface

- A submenu page under **WooCommerce**, titled **Elementor Colors**.
- Renders a settings page composed of template cards: status bar, controls, gallery, elements (widget groups), advanced, save bar.
- Enqueues admin CSS/JS (including the dequeue toggle component).
- Save button label: **Save Settings**.

---

## 10. Recent fixes (2026-08)

- **K1/K3:** `MIN_TEXT_CONTRAST` raised `3.0 -> 4.5` (WCAG AA for normal text).
- **K2:** visible focus indicators (primary border for inputs; outline halo for buttons).
- **K4:** added Happy Addons widget slugs to the addon map.
- **K5:** removed dead `dequeue-card.php` template (kept `dequeue.css` + `dequeue-card.js`).
- **Naming:** admin title **Elementor Colors**; plugin name **Elementor Design System for WooCommerce**.
- **Cache key fix:** `cache_key()` now includes kit typography so font changes invalidate the cache.

---

## 11. Known limitations / notes

- The plugin colors WooCommerce elements; it does **not** lay out or design Elementor widgets (that is Elementor's own CSS).
- `dequeue_blocks` can alter layout because WooCommerce Blocks CSS contains structural styles.
- Font rendering depends on the actual font files being loaded (by Elementor/theme); the plugin only declares `font-family`.
- The kit may define two font layers (Global Fonts tokens vs. page body typography); the plugin follows the **Global Fonts** tokens.

---

## 12. Development notes

- Namespaced classes are referenced by string name (e.g. `'ElementorColorChanger\CSS_Generator'`) for dynamic calls.
- The plugin is deliberately conservative about what it overrides to avoid breaking checkout/layout.
- Backups of pre-fix files live under `wp-content/uploads/eccw-backup/fixes-2026-08-15/`.