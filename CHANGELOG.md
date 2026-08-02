# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-08-02

### Added

- `Mapping_Service::ensure_defaults()` seeds every registry widget with
  heuristic defaults when mappings are empty or slots are missing. Wired
  into activation, cron rescan and the manual rescan AJAX handler so the
  option can never be empty and the frontend always receives replacement CSS.
- `Dequeue_Manager::should_dequeue()` gates core/block stylesheet removal on
  the presence of element mappings, preventing unstyled storefronts.
- Advanced Settings page refactored into components: PHP partials under
  `admin/templates/components/`, vanilla JS modules under
  `admin/js/components/` (no build step) and per-component stylesheets under
  `admin/css/components/`.

### Changed

- Button text and SVG fill now use an auto-derived WCAG contrast color
  (white on dark backgrounds, near-black on light backgrounds) whenever a
  slot paints a background color, so labels stay readable.
- Settings page shell (`settings-page.php`) now orchestrates component
  partials and exposes `defaults` / `newCount` to the frontend via
  `wooceData`.
- Dismiss All New button is disabled when there are no "new" widgets.
- Dequeue toggles are disabled (with an explanatory notice) when no element
  mappings exist, mirroring the frontend gating.
- Onboarding wizard Step 3 simplified for non-technical users: the technical
  "CSS Dequeue Settings" checkboxes are replaced with a single plain-language
  toggle ("Turn off WooCommerce's default styling") that sets both dequeue
  options, both on by default.
- Onboarding wizard now detects whether Elementor global colors are set and,
  when they are not, shows a friendly notice with a link to set them.
- `CSS_Generator::has_kit_colors()` added to distinguish a kit with real
  brand colors from the plugin's fallback palette.
- readme.txt rewritten to lead with a plain-language 3-step setup.

### Fixed

- Buttons rendered with identical background and text color when the mapped
  Elementor global color was applied to both (`color` now uses the contrast
  helper instead of the raw background color).
- Dequeueing WooCommerce core CSS while mappings were empty left the shop
  page structurally unstyled; dequeue is now inert without mappings.
- Reset-to-default now uses the heuristic default token instead of a fixed
  value, and includes dequeue checkboxes in the unsaved-changes guard.

## [1.1.0] - 2026-08-01

### Added

- Onboarding Wizard with 4-step setup (scan, A/B test, dequeue, launch).
- Live Visual Editor with click-to-customize on the frontend.
- Color state controls (Normal, Hover, Focus, Disabled).
- Audit Mode with coverage counter.
- WCAG contrast scoring for accessibility.
- Share preview links for client feedback.
- Undo support (10-action history stack).
- Performance badge showing CSS savings.
- Addon widget discovery: Essential Addons (`eael-woo-*`, `eicon-woocommerce`)
  and Premium Addons (`premium-woo-*`) widget types now auto-map to their
  matching WooCommerce registry elements on scan.
- Filterable widget registry and addon map via `wooce_element_registry` and
  `wooce_addon_widget_map` filters.
- Logic-core PHPUnit test suite (45 tests) covering the registry, heuristic
  engine, page context, CSS generation and mapping service.
- GitHub Actions CI with WordPress Coding Standards (PHPCS), PHPUnit and
  WordPress Plugin Check.
- `CHANGELOG.md`.

### Changed

- Renamed admin menu to "Advanced Settings".
- Consolidated the six duplicated widget-key migration loops, the two
  `walk_elements()` copies and the mapping sanitizer into a single
  `Mapping_Service` class.
- Plugin source now passes `WordPress-Extra` with zero errors and warnings.
- One-time onboarding redirect no longer hijacks every admin page; a
  dismissible admin notice takes over (see `wooce_wizard_dismissed`).
- Plugin header now declares `Requires Plugins: woocommerce, elementor`,
  `Tested up to: 7.0`, and aligns version with the readme stable tag (1.1.0).
- Readme now includes a privacy notice for the optional Pro opt-in email.

### Fixed

- `woocommerce-*` widget keys were normalized with an off-by-one offset,
  producing broken registry keys (e.g. `woocommerce-product-price` failed to
  map to `wc-product-price`).
- Onboarding wizard forced a redirect on every admin page load until
  completion; now fires once after activation, then surfaces as a notice.

## [1.0.0] - 2026-01-15

### Added

- Initial release: dynamic CSS mapping Elementor global colors to
  WooCommerce elements.
