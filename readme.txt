=== WooCommerce Elementor Colors ===
Contributors: anomalyco
Tags: woocommerce, elementor, css, design, colors, global colors, live editor, visual editor
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
WC requires at least: 7.0
WC tested up to: 10.9
Stable tag: 1.1.0
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.txt

Replaces WooCommerce default CSS with dynamic CSS that maps Elementor global colors to WooCommerce elements.

== Description ==

WooCommerce Elementor Colors removes WooCommerce default stylesheets and dynamically applies your Elementor global colors -- Primary, Secondary, Text, Accent -- to every WooCommerce element:

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

Your WooCommerce pages fully inherit your Elementor design system with zero bloat.

== Features ==

* **Onboarding Wizard** — 4-step setup that scans your site, shows a live A/B comparison, configures dequeue settings, and launches you into the editor.
* **Live Visual Editor** — Click any WooCommerce element on your site to customize its color instantly. No page reload needed.
* **Color State Controls** — Customize Normal, Hover, Focus, and Disabled states for each element.
* **Audit Mode** — Visual coverage counter showing how many WooCommerce elements are being styled.
* **WCAG Contrast Scoring** — Real-time contrast ratio calculations against WCAG AA standards.
* **Share Preview Links** — Generate temporary share links to show draft changes to clients before committing.
* **Performance Badge** — See how much CSS weight you're saving by replacing WooCommerce defaults.
* **Undo Support** — Ctrl+Z or click Undo to revert changes, with a 10-action history stack.
* **Advanced Settings** — Full table-based settings page under WooCommerce > Advanced Settings for bulk edits and fine-tuning.

== Installation ==

1. Upload the `woocommerce-elementor-colors` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins screen
3. The **Onboarding Wizard** launches automatically — follow the 4-step setup
4. After setup, use the **Live Editor** by clicking "Open Live Editor" from any admin notice, or visit your shop page with `?wooce_editor=1`
5. For bulk edits, go to **WooCommerce > Advanced Settings**

== Frequently Asked Questions ==

= Does this require Elementor Pro? =

No. Elementor (free) is sufficient.

= Will this break my WooCommerce layout? =

No. The plugin only removes CSS, not markup. Your theme layout and spacing remain intact.

= How are colors assigned? =

On activation, the plugin scans your Elementor pages for WooCommerce widgets and auto-assigns heuristic defaults. You can override any assignment from the settings page or the Live Editor.

= How do I use the Live Editor? =

Click "Open Live Editor" from the Advanced Settings page or add `?wooce_editor=1` to any WooCommerce page URL while logged in as admin. Click any element to customize.

= Can I share a preview with my client? =

Yes. Use the "Share Preview" button in the Live Editor toolbar. It generates a temporary, nonce-protected URL that anyone can view.

== Changelog ==

= 1.1.0 =
* Added Onboarding Wizard with 4-step setup (scan, A/B test, dequeue, launch)
* Added Live Visual Editor with click-to-customize on the frontend
* Added color state controls (Normal, Hover, Focus, Disabled)
* Added Audit Mode with coverage counter
* Added WCAG contrast scoring for accessibility
* Added share preview links for client feedback
* Added undo support (10-action history stack)
* Added performance badge showing CSS savings
* Renamed admin menu to "Advanced Settings"
* New options: wooce_onboarding_completed, wooce_live_draft, wooce_history_*, wooce_share_*

= 1.0.0 =
* Initial release
