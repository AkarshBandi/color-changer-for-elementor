=== Color Changer for Elementor and WooCommerce ===
Contributors: AkarshBandi
Tags: woocommerce, elementor, css, design, colors
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Requires Plugins: woocommerce, elementor
WC requires at least: 7.0
WC tested up to: 10.9
Stable tag: 1.0.0
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.txt

Replaces WooCommerce default CSS with dynamic CSS that maps Elementor global colors to WooCommerce elements.

== Description ==

Color Changer for Elementor and WooCommerce makes your store match your brand — automatically. It takes the colors you already set in Elementor (Primary, Secondary, Text, Accent) and applies them to every WooCommerce element, so your shop looks like the rest of your site.

* Add to Cart buttons (normal, hover, focus, disabled)
* Product prices (regular and sale)
* Sale badges
* Star ratings (filled and empty)
* Product tabs (normal and active)
* Cart table headers, cells, proceed button, coupon input, update cart
* Checkout place order button and input fields
* Notices (success, info, error)
* Quantity inputs
* My Account navigation links
* General WooCommerce links
* Shop loop buttons

No coding, no design skills, and no extra setup. Your WooCommerce pages simply inherit your Elementor design system.

== Features ==

* **Onboarding Wizard** — A friendly 4-step setup that scans your site, shows a before/after comparison, and launches you into the editor.
* **Live Visual Editor** — Click any WooCommerce element on your site to change its color instantly. No page reload needed.
* **Color State Controls** — Customize Normal, Hover, Focus, and Disabled states for each element.
* **Audit Mode** — See how many WooCommerce elements are being styled.
* **WCAG Contrast Scoring** — Real-time contrast checks against accessibility standards.
* **Share Preview Links** — Send a temporary preview link to clients before committing changes.
* **Undo Support** — Ctrl+Z or click Undo to revert changes.
* **Elementor Colors** — For fine-tuning, under WooCommerce > Elementor Colors.

== Installation ==

Getting started takes about a minute:

1. **Set your brand colors in Elementor** (optional but recommended). Go to Elementor → Site Settings → Global Colors and pick your Primary, Secondary, Text, and Accent colors. If you skip this, the plugin uses temporary defaults you can change later.
2. **Install and activate the plugin** through the Plugins screen.
3. **Follow the short setup wizard** that opens automatically. It scans your store, shows you a before/after preview, and turns off WooCommerce's default styling so your colors show through.

That's it — your store is now styled with your brand colors. You can fine-tune anything later from WooCommerce > Elementor Colors, or click any element on your store to edit it live.

== Frequently Asked Questions ==

= Do I need to be technical to use this? =

No. After activation, everything runs automatically. The only thing you may want to do is set your brand colors in Elementor (Elementor → Site Settings → Global Colors). If you don't, the plugin uses temporary defaults you can change anytime.

= Does this require Elementor Pro? =

No. Elementor (free) is sufficient.

= Will this break my WooCommerce layout? =

No. The plugin only removes CSS, not markup. Your theme layout and spacing remain intact.

= How are colors assigned? =

On activation, the plugin scans your Elementor pages for WooCommerce widgets and auto-assigns sensible defaults. You can override any assignment from the settings page or the Live Editor.

= How do I use the Live Editor? =

Click "Open Live Editor" from the Elementor Colors page or add `?eccw_editor=1` to any WooCommerce page URL while logged in as admin. Click any element to customize.

= Can I share a preview with my client? =

Yes. Use the "Share Preview" button in the Live Editor toolbar. It generates a temporary, nonce-protected URL that anyone can view.

== Privacy Notices ==

This plugin may collect an email address during the optional step of the Onboarding Wizard. The address is stored locally in your site's database (WordPress option `eccw_pro_optin_email`) and is only used to notify you about the plugin's Pro add-on. It is not sent to any third party and can be removed at any time by deleting the option or deactivating the plugin.

== Changelog ==

= 1.0.0 =
* Initial release: dynamic CSS mapping Elementor global colors to WooCommerce elements.
* Added Onboarding Wizard with 4-step setup (scan, A/B test, dequeue, launch)
* Added Live Visual Editor with click-to-customize on the frontend
* Added color state controls (Normal, Hover, Focus, Disabled)
* Added Audit Mode with coverage counter
* Added WCAG contrast scoring for accessibility
* Added share preview links for client feedback
* Added undo support (10-action history stack)
* Added performance badge showing CSS savings
* Renamed admin menu to "Elementor Colors"
* New options: eccw_onboarding_completed, eccw_live_draft, eccw_history_*, eccw_share_*
* Simplified the setup wizard: the technical "CSS Dequeue" step is now a single, plain-language toggle that turns off WooCommerce's default styling.
* Added a friendly notice in the wizard when Elementor global colors aren't set yet, with a link to set them.
* Rewrote the readme to lead with a plain-language, 3-step setup for non-technical users.
* Fixed button text contrast so labels stay readable on any background color.
* Fixed dequeue so WooCommerce default CSS is only removed when replacement styles exist.
* Live editor: clicking an element opens its color card instead of triggering the element's action, with an instant kit-color palette for non-technical users.
