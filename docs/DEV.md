# Store Design for Elementor and WooCommerce

## Product & UI Direction — Non-Technical User Experience

**Plugin slug:** `commerce-colors-for-elementor`
**Namespace:** `ElementorColorChanger`
**Text domain:** `commerce-colors-for-elementor`
**Current version:** 1.0.0

---

# 1. Why this README exists

The existing technical implementation of this plugin has become significantly more complicated than the original product idea.

The plugin currently contains:

* 12 WooCommerce widget categories
* 36 color slots
* Normal/hover/focus/active/disabled states
* Automatic state derivation
* WCAG contrast calculations
* Elementor Global Color integration
* Elementor Global Typography integration
* WooCommerce widget discovery
* Third-party Elementor widget recognition
* AJAX rescan/reset/dismiss functionality
* WooCommerce Blocks CSS dequeueing
* Admin-bar controls
* CSS caching
* Hashed stylesheet generation
* Cron-based discovery
* Feature gates
* Service containers
* Mapping versions
* Multiple advanced settings

Most of these can remain useful internally.

However, **they should not define the user experience.**

The current settings page shown in the provided screenshot is too dense, too technical, and too configuration-heavy for the intended audience.

This README establishes the new product direction.

---

# 1a. Implemented & verified capabilities (August 2026)

Everything below is implemented in the current codebase and was verified against a live
WooCommerce + Elementor 4.x site (classic templates and Elementor-built pages).

## Automatic mapping — Elementor Site Settings → WooCommerce

The plugin reads the active Elementor Kit (`_elementor_page_settings`) and maps the
global settings onto WooCommerce automatically:

* **Button tab** — background, hover background, text, hover text → every WC button
  (add-to-cart, place order, proceed to checkout, update cart, shop loop buttons,
  apply coupon, login/register submit)
* **Links tab** — normal + hover colors and typography → WC links
* **Headings tab** — h1–h6 colors and typography → WC headings (page title, etc.)
* **Body tab** — body color and typography → WC body text
* **Global colors** — Primary / Secondary / Text / Accent → prices, sale badges,
  ratings, table rows, notices, account navigation, inputs

Changing any global setting in Elementor regenerates the stylesheet automatically —
no mapping edits, no cache clearing, no user action.

## Legacy Elementor fallback

Sites on Elementor < 3.0 have no Kit. The plugin falls back to the legacy scheme
options (`elementor_scheme_color`, `elementor_scheme_typography`) so unmigrated
sites still get styled instead of silently doing nothing.

## WooCommerce-only scoping

The stylesheet loads **only** where WooCommerce elements are actually rendered:

* native WC routes (shop, product, cart, checkout, account, endpoints)
* pages embedding WC shortcodes
* Elementor pages built with WooCommerce widgets, native or add-on
  (Essential Addons, Premium Addons, Happy Addons — recognised through the
  same addon map discovery uses)
* every page, when the site's Elementor **header, footer or other
  theme-builder template** contains a WooCommerce widget — a Menu Cart in the
  header is the common case, and it puts a cart total and a button on pages
  WooCommerce does not route

A site whose store elements only appear on store pages is untouched everywhere
else. A site with a mini cart in its header is styled site-wide, because that
is where its store elements are.

## Colors and fonts only — never layout

The generated CSS contains color and typography rules only. No padding, radius,
margin, width, or other structural properties are emitted, so the plugin can never
restructure a page.

## Self-healing shop page guard

WooCommerce generates a product-archive rewrite rule for the shop page's slug (or
the hardcoded `shop` fallback). When that slug is an Elementor-built page, the rule
hijacks the URL and the page's own content (hero banner, Products widget) never
renders. The plugin strips the hijack rule at generation time and at read time, so
Elementor-built shop pages always render — verified by reproducing the bug with the
plugin off and confirming the plugin alone fixes it with no manual intervention.

## WooCommerce Blocks support

Block-based cart/checkout elements are covered: block buttons, cart items, cart
header, coupon input, quantity selector, notices, product price/badge/rating.

## Free + Pro addon architecture

The plugin is a single free plugin, WordPress.org-compliant (no paid code inside).
A separate Pro addon can extend it through existing filter seams:

| Filter | Purpose |
| ------ | ------- |
| `eccw_element_registry` | Register additional widget mappings (e.g. Pro widget chrome) |
| `eccw_addon_widget_map` | Map third-party addon widgets to core slots |
| `eccw_is_pro` | Declare the addon active and licensed |
| `eccw_feature_*` | Gate Pro features behind the license |
| `eccw_typography_groups` / `eccw_kit_typography` | Pro typography tokens and groups |
| `eccw_kit_colors` / `eccw_generated_css` | Pro color tokens and final CSS |
| `eccw_is_woocommerce_context` | Declare WooCommerce context for an integration the built-in detectors cannot see |
| `eccw_woocommerce_widget_categories` | Which Elementor widget categories denote WooCommerce (default: any containing `woocommerce`) |
| `eccw_wc_block_namespaces` | Which block namespaces denote WooCommerce (default: `woocommerce`) |
| `eccw_wc_shortcodes` | Which shortcodes bring a page into scope |
| `eccw_chrome_template_types` | Which Elementor template types count as site-wide chrome |
| `eccw_slot_states` / `eccw_settings` | State and settings extension |

The free plugin covers native WooCommerce elements everywhere (Elementor Free or
Pro) and the inner elements of Elementor Pro widgets automatically; the Pro addon
adds the Pro widget chrome (Menu Cart drawer, widget containers, Data Tabs headers).

---

# 2. Core product identity

## The plugin's job

The plugin should have one simple promise:

> **Automatically make WooCommerce match your Elementor website.**

More specifically:

> **Use the colors and typography already defined in Elementor's Global Site Settings and apply them automatically to WooCommerce.**

The user should not need to understand:

* CSS
* selectors
* WooCommerce widget types
* color states
* hover calculations
* WCAG formulas
* CSS specificity
* generated stylesheets
* Elementor widget internals
* discovery scans
* mappings
* cache invalidation

The plugin handles those things automatically.

---

# 3. Target user

The primary user is **not a developer**.

The target user may be:

* a small business owner
* a freelancer
* a store owner
* a designer
* an Elementor website builder
* a WordPress administrator
* someone who knows Elementor but does not know CSS
* someone who wants their WooCommerce store to look consistent with their website

They may understand:

* Elementor
* Global Colors
* Global Fonts
* WooCommerce
* buttons
* prices
* sale badges
* checkout
* their brand colors

They should NOT be expected to understand:

* CSS selectors
* pseudo-classes
* `!important`
* HSL
* WCAG contrast ratios
* CSS specificity
* widget registries
* state derivation
* transient caches
* stylesheet hashing

---

# 4. The most important UX principle

## Simple on the surface, sophisticated underneath.

The internal implementation can remain sophisticated.

For example, internally the plugin may still have:

```text
wc-add-to-cart
button_normal
button_hover
button_focus
button_disabled
```

The user should simply see:

> **Buttons → Primary**

The plugin internally decides:

* what selector to use
* what hover color to generate
* what focus treatment to use
* what disabled treatment to use
* whether text should be black or white
* whether contrast needs adjustment
* whether `!important` is required

This is the central product philosophy.

---

# 5. What the current page gets wrong

The current settings page presents too much information at once.

The screenshot shows a long, dense configuration interface containing many rows and controls.

The user is effectively being presented with the internal implementation model rather than the product's value.

The page makes the plugin feel like:

> "A WooCommerce CSS configuration system."

It should feel like:

> "A switch that makes WooCommerce follow my Elementor design."

This distinction is critical.

---

# 6. The product should NOT be a 36-color-slot editor

The plugin should not primarily sell itself as a tool where users manually configure dozens of WooCommerce elements.

The internal 36 slots can remain.

The public interface should group them into simple design concepts.

For example:

| Internal concept    | User-facing concept |
| ------------------- | ------------------- |
| `button_normal`     | Buttons             |
| `button_hover`      | Buttons             |
| `button_focus`      | Buttons             |
| `button_disabled`   | Buttons             |
| `price_regular`     | Prices              |
| `price_sale`        | Prices              |
| `badge_normal`      | Sale & highlights   |
| `stars_filled`      | Ratings             |
| `tab_normal`        | Product navigation  |
| `tab_active`        | Product navigation  |
| `place_order`       | Checkout buttons    |
| `place_order_hover` | Checkout buttons    |
| `input_text`        | Forms               |
| `input_focus`       | Forms               |
| `nav_link`          | Account navigation  |
| `link_normal`       | Links               |
| `link_hover`        | Links               |

The user sees concepts.

The engine handles the individual selectors and states.

---

# 7. Recommended product structure

The settings page should have approximately four areas.

## Area 1 — Status

The first thing the user sees should answer:

> Is the plugin working?

Example:

### Elementor Design System

**Your WooCommerce store is using your Elementor design system.**

✓ Elementor kit connected
✓ Global Colors detected
✓ Global Typography detected
✓ WooCommerce styling active

**Apply my Elementor design system**

`ON`

This should immediately communicate success.

---

# 8. Area 2 — Your design

Show the colors that Elementor already provides.

Example:

### Your Elementor Colors

**Primary**
● `#...`

**Secondary**
● `#...`

**Text**
● `#...`

**Accent**
● `#...`

The plugin should not ask the user to recreate these colors.

The whole point is that the plugin uses the existing Elementor design system.

A small explanatory message can say:

> These colors come from Elementor → Site Settings → Global Colors.

---

# 9. Area 3 — WooCommerce appearance

This is where users can understand what is being applied.

Example:

### WooCommerce Appearance

| What             | Uses    |
| ---------------- | ------- |
| Buttons          | Primary |
| Prices           | Primary |
| Sale badges      | Accent  |
| Ratings          | Accent  |
| Text & links     | Text    |
| Forms            | Text    |
| Success messages | Accent  |
| Error messages   | Text    |

The user can optionally change these mappings.

For example:

```text
Buttons        [ Primary ▼ ]
Prices         [ Primary ▼ ]
Sale badges    [ Accent ▼ ]
Text & links   [ Text ▼ ]
```

Available choices should normally be:

* Primary
* Secondary
* Text
* Accent
* Custom

Do not expose the 36 internal slots here.

---

# 10. Custom colors

Custom colors should be available, but they should not dominate the interface.

If the user selects:

> Custom

then reveal a color picker / hex field.

Example:

```text
Sale badges

[ Accent ▼ ]

or

[ Custom ]
[ #FF4D67 ]
```

This gives advanced users control without making beginners deal with custom CSS-style configuration.

---

# 11. Typography

Typography should be equally simple.

### Typography

**Headings**

Use Elementor's:

`Secondary`

**Buttons**

Use Elementor's:

`Accent`

**Body text**

Use Elementor's:

`Text`

The plugin should only control:

* font family
* font weight

It should not try to become a typography editor.

Do not expose:

* font size
* line height
* letter spacing
* responsive typography

Those are layout/design-system concerns and are outside the plugin's core purpose.

---

# 12. Automatic states

Hover, focus, active, and disabled states should normally be automatic.

The user should not need to select:

```text
Normal
Hover
Focus
Active
Disabled
```

for every component.

Instead:

> **Automatic interaction states ✓**

The plugin internally derives sensible states.

For example:

```text
Primary button
    ↓
Normal → Elementor Primary
Hover → automatically derived
Focus → accessible focus treatment
Active → automatically derived
Disabled → automatically softened
```

The technical state-derivation system can remain.

The user should not have to operate it.

---

# 13. Accessibility

Automatic contrast protection should remain enabled by default.

The user should not have to understand WCAG math.

Instead, the interface can simply communicate:

> ✓ **Automatic contrast protection**
>
> Text automatically adjusts when necessary to remain readable against its background.

The internal functions:

* `relative_luminance()`
* `contrast_ratio()`
* `contrast_text()`
* `ensure_readable()`

should remain implementation details.

---

# 14. Advanced settings

Advanced settings should be collapsed by default.

Possible contents:

### Advanced

#### CSS priority

> Use stronger CSS rules when WooCommerce or your theme overrides the design system.

`Use stronger styling rules` ✓

This is the user-friendly equivalent of `force_important`.

Do NOT label it:

> `force_important`

---

#### Automatic interaction states

> Automatically create hover, active and disabled colors.

`Enabled` ✓

This is the user-friendly equivalent of `derive_states`.

---

#### Typography

> Apply your Elementor Global Typography to WooCommerce.

`Enabled` ✓

---

#### WooCommerce Blocks compatibility

This should be handled cautiously.

The current `dequeue_blocks` functionality can affect layout because WooCommerce Blocks styles contain structural CSS.

Therefore:

**Do not present this as a normal feature.**

If retained, it belongs under an advanced compatibility section with a warning.

---

# 15. Features that should disappear from the normal UI

The following should not be part of the main settings experience.

## Remove from primary UI

### Rescan

The plugin should discover supported WooCommerce elements automatically.

If a manual scan is technically required, place it under advanced/developer tools.

---

### Dismiss New

Do not make users manage discovered widget states.

The plugin should automatically support newly recognized elements.

---

### Widget discovery status

Do not show:

```text
new
default
configured
```

These are internal states.

---

### Mapping version

Never expose it.

---

### Cache generation

Never expose it.

---

### CSS filename/hash

Never expose it.

---

### Registry

Never expose it.

---

### Slot IDs

Never expose them.

---

### Selector information

Never expose it.

---

### HSL/state derivation details

Never expose them.

---

### CSS `!important`

Do not use the technical term in the normal UI.

---

# 16. Features that should probably be removed entirely from the product

## WooCommerce Blocks CSS dequeue

This should not be part of the normal plugin experience.

The plugin's purpose is to **apply a design system**, not remove WooCommerce's structural CSS.

Removing WooCommerce Blocks styles can cause layout changes.

If this capability is required for a specific compatibility case, keep it internally or behind an advanced compatibility option.

---

## Admin-bar master switch

The admin-bar switch is not necessary for the initial product.

It adds another place where users have to understand the plugin.

A single enable/disable control on the settings page is enough.

The underlying functionality can remain if desired, but it should not be a central feature.

---

# 17. Discovery should become automatic

The user should not have to think:

> "Which WooCommerce widgets are currently detected?"

Instead:

1. Plugin activates.
2. Plugin detects Elementor.
3. Plugin detects the active Elementor kit.
4. Plugin reads Global Colors.
5. Plugin reads Global Typography.
6. Plugin applies styling to supported WooCommerce elements.
7. New supported elements are automatically handled.

The user sees:

> **WooCommerce styling is active.**

That's it.

---

# 18. The plugin's ideal first-use experience

The ideal flow should be:

## Step 1

Install and activate the plugin.

## Step 2

Plugin checks:

* WooCommerce exists
* Elementor exists
* Elementor kit exists

## Step 3

Plugin finds the kit colors.

Example:

```text
Primary
Secondary
Text
Accent
```

## Step 4

Plugin automatically applies them.

## Step 5

User opens the settings page.

They see:

> **Your WooCommerce store is connected to your Elementor design system.**

No complicated setup wizard is required.

---

# 19. The settings page should answer five questions

A non-technical user should be able to understand the entire page by answering these questions:

### 1. Is it working?

Yes / No

### 2. Which Elementor kit is being used?

Show the active kit.

### 3. Which colors are being used?

Show Primary / Secondary / Text / Accent.

### 4. What does the plugin style?

Show simple categories.

### 5. Can I turn it off?

Yes.

Everything else is secondary.

---

# 20. Recommended visual hierarchy

The page should prioritize information in this order:

```text
1. What does this plugin do?
        ↓
2. Is it currently active?
        ↓
3. Which Elementor kit is being used?
        ↓
4. What colors are being applied?
        ↓
5. What WooCommerce areas are affected?
        ↓
6. Optional customization
        ↓
7. Advanced settings
```

The current page effectively does the opposite:

```text
Many individual controls
Many individual controls
Many individual controls
Many individual controls
...
```

That is why it feels overwhelming.

---

# 21. Recommended visual style

The interface should feel like a modern Elementor/WordPress settings product.

It should be:

* clean
* spacious
* friendly
* visually obvious
* low-density
* card-based
* easy to scan
* focused on outcomes

Avoid:

* huge tables
* dozens of tiny rows
* excessive borders
* technical terminology
* long explanations
* nested controls everywhere
* developer terminology
* configuration overload

---

# 22. The page should not look like a developer tool

Avoid presenting information such as:

```text
Widget Type
Slot
Selector
Properties
State
Derives From
Mapping Status
```

Those are useful to the developer but not to the customer.

Instead:

```text
Buttons
Prices
Sale badges
Ratings
Product navigation
Cart
Checkout
Forms
Messages
Account
Links
```

Even these categories should only be shown when useful.

---

# 23. Recommended main-page copy

The product should communicate something close to:

> ## Make WooCommerce match Elementor
>
> Your Elementor Global Colors and Typography are already your site's design system.
>
> This plugin automatically applies them to WooCommerce so your buttons, prices, forms, checkout, sale badges and other store elements look like they belong to the same website.

Then:

> **Design system**
>
> ✓ Connected to your Elementor Kit

Then:

> **WooCommerce styling**
>
> `ON`
>
> WooCommerce is using your Elementor design system.

This should be understandable without reading documentation.

---

# 24. Product philosophy

The plugin should follow this rule:

> **Every setting should exist because a normal website owner needs to make a decision.**

If the user does not need to make the decision, the plugin should make it automatically.

Examples:

### Bad

> Choose hover color.

### Better

> Automatically create a hover color.

### Bad

> Choose whether to use `!important`.

### Better

> Make sure WooCommerce uses your chosen design.

### Bad

> Configure 36 WooCommerce slots.

### Better

> Choose which Elementor color each type of store element should use.

### Bad

> Rescan Elementor data.

### Better

> Automatically detect supported WooCommerce elements.

---

# 25. What remains in the engineering layer

The existing technical work is not necessarily wasted.

The following can remain internal:

* `Element_Registry`
* `Heuristic_Engine`
* `CSS_Generator`
* `Typography`
* `Cache_Manager`
* `Discovery_Engine`
* `Mapping_Service`
* WCAG calculations
* selector definitions
* third-party widget mappings
* state derivation
* hashed stylesheet generation
* cache invalidation
* cron discovery
* service container
* feature gates

The important change is:

> **These are implementation details, not the product interface.**

---

# 26. Internal versus external architecture

The plugin should have two distinct mental models.

## Internal model

```text
Element
    ↓
Widget type
    ↓
Slot
    ↓
Selector
    ↓
State
    ↓
Color token
    ↓
Resolved color
    ↓
Contrast adjustment
    ↓
CSS
    ↓
Cached stylesheet
```

## User model

```text
My Elementor Design
        ↓
WooCommerce
        ↓
Looks consistent
```

The second model is what the customer should experience.

---

# 27. Recommended default behavior

The plugin should work well without customization.

Recommended defaults:

```text
Enabled                 ON
Use Elementor colors    ON
Use Elementor fonts     ON
Automatic states        ON
Contrast protection     ON
Strong CSS rules        ON
Blocks CSS removal      OFF
```

---

# 28. The plugin should be opinionated

A good non-technical plugin should make sensible decisions.

It should not ask the user:

> "What should we do?"

for every small detail.

It should say:

> "We've done the sensible thing for you."

For example:

* Primary buttons use Primary.
* Sale elements use Accent.
* Body text uses Text.
* Headings use the appropriate Elementor typography.
* Hover colors are derived automatically.
* Disabled controls are softened automatically.
* Text contrast is protected automatically.

The user can override these if they want.

---

# 29. Customization should be an override, not the starting point

The hierarchy should be:

```text
Elementor design system
        ↓
Plugin defaults
        ↓
Optional user overrides
        ↓
Generated CSS
```

Not:

```text
User manually configures everything
        ↓
Plugin generates CSS
```

This distinction is central to the product.

---

# 30. What the plugin is NOT

The plugin is not:

* a page builder
* a WooCommerce theme
* a WooCommerce layout editor
* a CSS editor
* a visual page editor
* a complete WooCommerce styling framework
* a replacement for Elementor Site Settings

It is a **bridge between Elementor's design system and WooCommerce**.

---

# 31. Core value proposition

The simplest explanation of the plugin is:

> **Elementor defines the design.**
>
> **This plugin makes WooCommerce follow it.**

That should be the foundation for the UI, documentation, marketing, onboarding and future development.

---

# 32. Development rule going forward

Before adding a feature, ask:

### Does this help a non-technical user accomplish the core goal?

If yes:

> Consider adding it.

If no:

> Keep it internal or don't build it.

Do not add a feature simply because it is technically possible.

Do not expose an internal system merely because the system exists.

Do not create UI controls for every capability of the CSS engine.

---

# 33. Rewrite roadmap change

The previous rewrite roadmap should be paused before continuing implementation.

Do **not** immediately continue from:

```text
Step 1
Step 2
Step 3
...
```

Instead:

## Phase A — Product simplification

1. Redesign the settings-page information architecture.
2. Decide the exact user-facing settings.
3. Remove unnecessary concepts from the UI.
4. Define default behavior.
5. Define which existing features become internal-only.
6. Define which existing features are removed.
7. Keep the existing database schema compatible.

## Phase B — UI implementation

Build the new settings page around the simplified product.

## Phase C — Engine implementation

Continue the technical rewrite while preserving:

* existing options
* existing mappings
* existing CSS behavior
* existing public APIs
* existing compatibility

## Phase D — Validation

Verify:

* existing saved colors survive
* Elementor colors are detected
* WooCommerce styling works
* typography works
* CSS generation works
* caching works
* disabling works
* no layout CSS is unintentionally removed

---

# 34. Database compatibility remains mandatory

Simplifying the UI does NOT mean destroying existing data.

The following options must remain:

```text
eccw_settings
eccw_colors_mappings
eccw_css_generation
eccw_db_version
```

Existing saved color mappings must remain usable.

The internal 36-slot mapping system can remain even if the UI becomes much simpler.

The user interface should become simpler without requiring the database to become simpler immediately.

---

# 35. Migration philosophy

Existing users may already have configured detailed mappings.

Therefore:

> **Never discard their configuration simply because the new UI no longer exposes every individual slot.**

Instead:

* retain the existing mapping
* normalize it
* use it internally
* display a simplified representation
* preserve custom overrides where possible

If an old setting no longer has a meaningful UI equivalent, continue honoring it internally until a safe migration strategy exists.

---

# 36. What the finished plugin should feel like

A non-technical user should be able to open the plugin and think:

> "Oh, this just makes WooCommerce use my Elementor colors."

Then:

> "It's already connected."

Then:

> "I can change a few things if I want."

And finally:

> "I don't need to understand anything else."

That is the desired outcome.

---

# 37. Final product definition

## Store Design for Elementor and WooCommerce

**Automatically apply your Elementor design system to WooCommerce.**

### It uses:

* Elementor Global Colors
* Elementor Global Typography

### It styles:

* buttons
* prices
* sale badges
* ratings
* product navigation
* cart
* checkout
* forms
* notices
* account navigation
* links
* shop buttons

### It automatically handles:

* hover states
* focus states
* disabled states
* text contrast
* CSS priority
* supported WooCommerce elements
* stylesheet generation
* caching

### The user mainly controls:

* Enable / disable
* Color mapping
* Typography
* Optional advanced behavior

---

# 38. Final design principle

> ## Hide complexity. Do not remove capability.

The plugin can remain technically sophisticated.

The user should never have to know that it is sophisticated.

The best version of this plugin is not the one with the most controls.

It is the one where a non-technical user installs it, sees their WooCommerce store immediately match their Elementor site, and thinks:

> **"That's it? It just works."**

That is the product we should build.
