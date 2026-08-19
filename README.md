# Commerce Colors for Elementor

**Your Elementor colors and fonts, applied automatically to every WooCommerce button, price, badge and form. No page-by-page setup.**

[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0--or--later-blue.svg)](LICENSE)


---

## What it does

You already chose your brand colors and fonts in Elementor. WooCommerce ignores them: its buttons, prices, badges and checkout ship their own grey-and-purple palette, and matching them by hand means rebuilding templates or writing CSS against selectors that change with every WooCommerce release.

This plugin reads your Elementor kit and keeps WooCommerce in step with it — colors and fonts, everywhere they appear, updating whenever you change the kit. Nothing to configure: sensible choices are applied the moment it activates.

![Demo](demo.gif)

## Features

- **Connected to your Elementor kit** — your brand colours and fonts follow your Elementor Site Settings automatically, and update the moment you change them there.
- **Sensible defaults on activation** — every WooCommerce element gets a colour picked from your palette. Nothing to configure.
- **Per-element colour control** — change any element's colour from WooCommerce → Store Design, or enter any custom hex value.
- **Hover, focus and disabled states** — derived automatically from your colours, including accessible focus outlines.
- **Keep text readable for me** — button labels and text colours are checked against the page background and darkened just enough to stay legible.
- **Win against my theme** — optional setting that makes your brand colours take priority over theme styles.
- **Use my fonts as well as my colours** — your Elementor typography follows into the store too.
- **Reset to suggested colours** — put every element back to its kit-derived colour in one action.
- **Check for new elements** — rescan your store for WooCommerce widgets added since activation.
- **Works with add-on widgets** — recognises WooCommerce widgets from Essential Addons and Premium Addons, not just Elementor Pro.
- **WooCommerce Blocks support** — block-based Cart and Checkout styled too, with an optional setting to remove the blocks stylesheet.
- **One-click off switch** — untick "Apply my brand colours" on the Store Design page and your store returns to its normal appearance instantly; nothing is deleted.

## Requirements

- WordPress 5.8+
- PHP 7.4+
- Elementor (free) — Elementor Pro not required
- WooCommerce 7.0+

## Installation

1. **Set your brand colors in Elementor** (optional but recommended). Go to Elementor → Site Settings → Global Colors and pick your Primary, Secondary, Text, and Accent colors. If you skip this, the plugin uses temporary defaults you can change later.
2. **Install and activate the plugin** through the Plugins screen (upload the zip from the [latest release](https://github.com/AkarshBandi/color-changer-for-elementor/releases/latest)).
3. **Check the colours** — open WooCommerce → Store Design. Every element already has a sensible colour picked from your palette; change any of them if you like, then save.

That's it — your store is now styled with your brand colors.

## Usage

Everything lives under **WooCommerce → Store Design**:

- Change any element's colour from your palette or enter a custom hex value
- Toggle "Win against my theme" if your theme overrides the colours
- Toggle "Keep text readable for me" to control button label colours yourself
- Use "Reset to suggested colours" to restore kit-derived defaults
- Use "Check for new elements" after adding new WooCommerce widgets
- Untick "Apply my brand colours" to switch everything off instantly — nothing is deleted

## Download

- **Latest release (zip):** [v1.0.0](https://github.com/AkarshBandi/color-changer-for-elementor/releases/latest) — upload via Plugins → Add New → Upload Plugin
- **Source:** clone this repository

## Development

- `includes/` — plugin classes (container, settings, CSS generation, element registry, typography, discovery)
- `admin/` — settings page (Store Design) templates, CSS and JS
- `languages/` — translation template (`.pot`)
- `docs/DEV.md` — internal design notes

## License

GPL-2.0-or-later — see [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).
