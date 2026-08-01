# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

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

- Consolidated the six duplicated widget-key migration loops, the two
  `walk_elements()` copies and the mapping sanitizer into a single
  `Mapping_Service` class.
- Plugin source now passes `WordPress-Extra` with zero errors and warnings.
- One-time onboarding redirect no longer hijacks every admin page; a
  dismissible admin notice takes over (see `wooce_wizard_dismissed`).

### Fixed

- `woocommerce-*` widget keys were normalized with an off-by-one offset,
  producing broken registry keys (e.g. `woocommerce-product-price` failed to
  map to `wc-product-price`).
- Onboarding wizard forced a redirect on every admin page load until
  completion; now fires once after activation, then surfaces as a notice.

## [1.1.0] - 2026-07-30

### Added

- Onboarding Wizard with 4-step setup (scan, A/B test, dequeue, launch).
- Live Visual Editor with click-to-customize on the frontend.
- Color state controls (Normal, Hover, Focus, Disabled).
- Audit Mode with coverage counter.
- WCAG contrast scoring for accessibility.
- Share preview links for client feedback.
- Undo support (10-action history stack).
- Performance badge showing CSS savings.

### Changed

- Renamed admin menu to "Advanced Settings".

## [1.0.0] - 2026-01-15

### Added

- Initial release: dynamic CSS mapping Elementor global colors to
  WooCommerce elements.
