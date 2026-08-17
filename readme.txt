=== Color and Font Sync for Elementor and WooCommerce ===
Contributors: admin
Tags: elementor, woocommerce, colors, fonts, branding
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Requires Plugins: woocommerce, elementor
WC requires at least: 7.0
WC tested up to: 10.9
Stable tag: 2.1.6
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.txt

Your Elementor colors and fonts, applied automatically to every WooCommerce button, price, badge and form. No page-by-page setup.

== Description ==

You already chose your brand colors and fonts in Elementor. WooCommerce ignores them: its buttons, prices, badges and checkout ship their own grey-and-purple palette, and matching them by hand means rebuilding templates or writing CSS against selectors that change with every WooCommerce release.

This plugin reads your Elementor kit and keeps WooCommerce in step with it — colors and fonts, everywhere they appear, updating whenever you change the kit. Nothing to configure: sensible choices are applied the moment it activates.

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

Everything below is in the free plugin. There is no locked panel, no upgrade prompt, and no feature that stops working after a trial.

* **One-click off switch in the toolbar** — "Store Colors" sits in your admin bar on every page, front end and back. If a store ever looks wrong, you are one click from putting it back, without hunting for a settings screen. Nothing is deleted either way.
* **Onboarding Wizard** — A friendly 4-step setup that scans your site, shows a before/after comparison, and launches you into the editor.
* **Live Visual Editor** — Click any WooCommerce element on your site to change its color instantly. No page reload needed. Unrestricted — every element, every color.
* **Color State Controls** — Customize Normal, Hover, Focus, and Disabled states for each element. Focus states are part of keyboard accessibility, so they are never held back.
* **Any color you like** — Use your four Elementor globals, or pick any hex value.
* **Undo Support** — Ctrl+Z or click Undo. The last 50 saves are kept, per user.
* **Reset to defaults** — Put every element back to its suggested color in one action.
* **Audit Mode** — See how many WooCommerce elements are being styled.
* **WCAG Contrast Scoring** — Real-time contrast checks against accessibility standards.
* **Works with add-on widgets** — Recognises WooCommerce widgets from Essential Addons and Premium Addons, not just Elementor Pro.
* **Share Preview Links** — Send a temporary preview link to clients before committing changes.
* **Elementor Colors** — For fine-tuning, under WooCommerce > Elementor Colors.

== Installation ==

Getting started takes about a minute:

1. **Set your brand colors in Elementor** (optional but recommended). Go to Elementor → Site Settings → Global Colors and pick your Primary, Secondary, Text, and Accent colors. If you skip this, the plugin uses temporary defaults you can change later.
2. **Install and activate the plugin** through the Plugins screen.
3. **Follow the short setup wizard** that opens automatically. It scans your store, shows you a before/after preview, and layers your colors over WooCommerce's default styling so your brand shows through. Your product grid and button sizing stay intact.

That's it — your store is now styled with your brand colors. You can fine-tune anything later from WooCommerce > Elementor Colors, or click any element on your store to edit it live.

== Frequently Asked Questions ==

= Do I need to be technical to use this? =

No. After activation, everything runs automatically. The only thing you may want to do is set your brand colors in Elementor (Elementor → Site Settings → Global Colors). If you don't, the plugin uses temporary defaults you can change anytime.

= Does this require Elementor Pro? =

No. Elementor (free) is sufficient.

= Will this break my WooCommerce layout? =

By default, no. The plugin layers color over WooCommerce's own stylesheet rather than replacing it, so your product grid, button sizing and spacing are untouched.

There is one exception, and it is off by default. Under Advanced you can remove the WooCommerce Blocks stylesheet. That sheet carries spacing and layout as well as color for block-based Cart and Checkout pages, so turning it on can change how those two pages are arranged. If you enable it, check your Cart and Checkout afterwards.

= How do I turn it off if something looks wrong? =

Click "Store Colors" in your admin toolbar and choose "Turn my colors off". It is there on every page, including the front end, so you do not have to go looking for it while someone is on the phone telling you the site looks broken.

You can also untick "Apply my brand colors to WooCommerce" under WooCommerce > Elementor Colors. Either way your store returns to its normal appearance immediately and none of your color choices are deleted — switch it back on to bring them straight back.

= My colors are not showing up =

Leave "Override theme styles" on. Some themes use very specific CSS rules, and this setting makes your brand colors take priority over them.

= The plugin is overriding my own CSS =

Turn off "Override theme styles" under Controls. Your stylesheet will then win where the two overlap.

= Can I choose my own button label colors? =

Yes. Turn off "Keep text readable automatically" under Controls. By default, button labels are set to black or white so they stay readable on any background color.

= I picked a light color and the text vanished =

That should no longer happen. "Keep text readable automatically" checks every text color against your page background and darkens it just enough to be legible, keeping the same hue so it still looks like your brand color. Colors that are already readable are used exactly as you chose them.

The check assumes your pages have a light background. If your store is dark-themed, tell the plugin so:

`add_filter( 'eccw_page_background', function () { return '#111111'; } );`

= My cart table looks like a plain list with no dividing lines =

Fixed in 1.5.0. "Table Rows" used to recolor the cart table's borders as well as its text, and those borders are the only thing separating the columns — so a pale color erased them. It now styles text only and WooCommerce keeps its own dividers.

= How are colors assigned? =

On activation, the plugin scans your Elementor pages for WooCommerce widgets and auto-assigns sensible defaults. You can override any assignment from the settings page or the Live Editor.

= How do I use the Live Editor? =

Click "Open Live Editor" from the Elementor Colors page or add `?eccw_editor=1` to any WooCommerce page URL while logged in as admin. Click any element to customize.

= Can I share a preview with my client? =

Yes. Use the "Share Preview" button in the Live Editor toolbar. It generates a temporary, nonce-protected URL that anyone can view.

== Privacy Notices ==

This plugin collects no personal data. It sends nothing to any external service, and it has no analytics, telemetry or tracking of any kind. Everything it stores — your color mappings, settings and editor drafts — stays in your own site's database.

Versions before 1.3.3 offered an email signup on the last step of the Onboarding Wizard. Any address entered there was stored in your own database (WordPress option `eccw_pro_optin_email`) and never sent anywhere, but the wizard did not say so and the guide it offered was never written. That signup is gone, and updating to 1.3.3 deletes any address it stored.

== Changelog ==

= 2.1.6 =
* Shop-page guard hardened: also intercepts pre-generated rewrite rules at read time, which is what WordPress 6.4+ actually uses.
* Styled WooCommerce chrome rendered in Elementor headers and footers (mini-cart, account links) on every page, not just store pages.
* Login/register submit button and mini-cart buttons now part of the button family.
* Mini-cart totals, product reviews, review author and short description styled with the body text token.
* Block product tabs (.wc-tabs) styled alongside the classic tabs.
* New registry slots apply automatically after an update — no mappings reset required.

= 2.0.0 =
* Automatic mapping of Elementor Site Settings (Button, Links, Headings, Body) onto all WooCommerce elements.
* WooCommerce-only scoping: stylesheet loads only on WooCommerce contexts; other pages untouched.
* Colors and fonts only — never emits layout/structural CSS.
* Self-healing shop page guard: prevents the WooCommerce archive rewrite rule from hijacking Elementor-built shop pages.
* Legacy Elementor fallback: reads pre-3.0 scheme options when no Kit exists.
* WooCommerce Blocks support: block cart/checkout buttons, inputs, cart items, notices, quantity selector.
* Elementor-built WooCommerce page detection via _elementor_data.
* Coupon widget fix: correct cart coupon selectors and Apply Coupon button styling.
* Account dashboard colors restored to text/primary tokens.
* Free + Pro addon architecture via filter seams (eccw_element_registry, eccw_is_pro, eccw_addon_widget_map, eccw_typography_groups, eccw_generated_css).

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
* Simplified the setup wizard: the technical "CSS Dequeue" step is now a single, plain-language toggle that hands styling over to your brand colors.
* Added a friendly notice in the wizard when Elementor global colors aren't set yet, with a link to set them.
* Rewrote the readme to lead with a plain-language, 3-step setup for non-technical users.
* Fixed button text contrast so labels stay readable on any background color.
* Fixed dequeue so WooCommerce block CSS is only removed when replacement styles exist.
* Live editor: clicking an element opens its color card instead of triggering the element's action, with an instant kit-color palette for non-technical users.
