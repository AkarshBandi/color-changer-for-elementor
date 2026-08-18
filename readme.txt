=== Commerce Colors for Elementor ===
Contributors: AkarshBandi
Tags: elementor, woocommerce, colors, fonts, branding
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
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

* **Connected to your Elementor kit** — your brand colours and fonts follow your Elementor Site Settings automatically, and update the moment you change them there.
* **Sensible defaults on activation** — every WooCommerce element gets a colour picked from your palette. Nothing to configure.
* **Per-element colour control** — change any element's colour from WooCommerce → Store Design, or enter any custom hex value.
* **Hover, focus and disabled states** — derived automatically from your colours, including accessible focus outlines.
* **Keep text readable for me** — button labels and text colours are checked against the page background and darkened just enough to stay legible.
* **Win against my theme** — optional setting that makes your brand colours take priority over theme styles.
* **Use my fonts as well as my colours** — your Elementor typography follows into the store too.
* **Reset to suggested colours** — put every element back to its kit-derived colour in one action.
* **Check for new elements** — rescan your store for WooCommerce widgets added since activation.
* **Works with add-on widgets** — recognises WooCommerce widgets from Essential Addons and Premium Addons, not just Elementor Pro.
* **WooCommerce Blocks support** — block-based Cart and Checkout styled too, with an optional setting to remove the blocks stylesheet.
* **One-click off switch** — untick "Apply my brand colours" on the Store Design page and your store returns to its normal appearance instantly; nothing is deleted.
* **Store Design** — the whole thing lives under WooCommerce → Store Design.

== Installation ==

Getting started takes about a minute:

1. **Set your brand colors in Elementor** (optional but recommended). Go to Elementor → Site Settings → Global Colors and pick your Primary, Secondary, Text, and Accent colors. If you skip this, the plugin uses temporary defaults you can change later.
2. **Install and activate the plugin** through the Plugins screen.
3. **Check the colours** — open WooCommerce → Store Design. Every element already has a sensible colour picked from your palette; change any of them if you like, then save.

That's it — your store is now styled with your brand colors. You can fine-tune anything later from WooCommerce → Store Design.

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

You can also untick "Apply my brand colors to WooCommerce" under WooCommerce → Store Design. Either way your store returns to its normal appearance immediately and none of your color choices are deleted — switch it back on to bring them straight back.

= My colors are not showing up =

Leave "Win against my theme" on. Some themes use very specific CSS rules, and this setting makes your brand colors take priority over them.

= The plugin is overriding my own CSS =

Turn off "Win against my theme" on the Store Design page. Your stylesheet will then win where the two overlap.

= Can I choose my own button label colors? =

Yes. Turn off "Keep text readable for me" on the Store Design page. By default, button labels are set to black or white so they stay readable on any background color.

= I picked a light color and the text vanished =

That should no longer happen. "Keep text readable for me" checks every text color against your page background and darkens it just enough to be legible, keeping the same hue so it still looks like your brand color. Colors that are already readable are used exactly as you chose them.

The check assumes your pages have a light background. If your store is dark-themed, tell the plugin so:

`add_filter( 'eccw_page_background', function () { return '#111111'; } );`

= My cart table looks like a plain list with no dividing lines =

Fixed in 1.5.0. "Table Rows" used to recolor the cart table's borders as well as its text, and those borders are the only thing separating the columns — so a pale color erased them. It now styles text only and WooCommerce keeps its own dividers.

= How are colors assigned? =

On activation, the plugin scans your Elementor pages for WooCommerce widgets and auto-assigns sensible defaults. You can override any assignment from the Store Design page.

= How do I change a color? =

Open WooCommerce → Store Design, find the element you want to change, pick a color from your palette or enter any hex value, and save. The store updates immediately.

== Privacy Notices ==

This plugin collects no personal data. It sends nothing to any external service, and it has no analytics, telemetry or tracking of any kind. Everything it stores — your color mappings, settings and editor drafts — stays in your own site's database.

== Changelog ==

= 1.0.0 =
* First release.
* Reads the colours and fonts from your Elementor site settings and applies them across WooCommerce.
* Covers add-to-cart and shop buttons, prices, sale badges, star ratings, product tabs, cart, checkout, notices, quantity boxes, My Account and store links.
* Styles only colours and fonts, never spacing or layout, so nothing on your pages moves.
* Loads only on store pages; the rest of your site is left alone.
* Optional readable-text adjustment picks black or white button labels automatically and lifts text that is too faint to read.
* Optional hover, focus and unavailable shades worked out from each element's main colour.
* Works with WooCommerce Blocks cart and checkout.
* Keeps Elementor-built shop pages from being replaced by the default WooCommerce archive.
* Falls back to pre-3.0 Elementor colour settings on sites that never moved to a kit.
