# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.0] - 2026-08-17

### Fixed

- **A WooCommerce element in an Elementor header or footer went unstyled on
  every page that was not itself a store page.** This is the bug 2.0 was
  written to kill. 2.0's own notes give "mini-carts in headers and product
  strips on the home page got no styling" as the reason for moving to one
  site-wide stylesheet — and that move did remove the page-type gate from CSS
  *generation*, but an identical gate survived one layer further out, in
  `enqueue_stylesheet()`. `is_woocommerce_context()` read the queried post's
  `_elementor_data` and nothing else, so it could not see a Menu Cart living in
  a theme-builder template.

  Verified on a live store: home, and two ordinary pages, each rendering an
  `elementor-menu-cart` in the header, were served WooCommerce's own
  stylesheets and none of this plugin's. Now answered once for the whole site
  (`CSS_Generator::has_global_wc_chrome()`), cached in the autoloaded
  `eccw_global_chrome` option, and flushed when an `elementor_library` post is
  saved or deleted, on plugin update, and on both rescans.

  Detection stays narrow: on the same site the footer and popup templates
  answer false, and so do the pages with no store content, so a store whose
  elements live only on store pages is still untouched everywhere else.

- **Elementor pages built with add-on WooCommerce widgets were not recognised.**
  The check was a literal `"widgetType":"woocommerce-*"` regex, and Elementor
  free ships no WooCommerce widgets at all — so on the Essential Addons /
  Premium Addons stack, which is how most such stores are built, it matched
  nothing. It now asks `Element_Registry::is_woocommerce_widget_type()`, which
  already knew the answer, addon map included.

- `woocommerce-menu-cart` passed the family test but `normalize_key()` had
  nothing to map it to, so it normalised to itself and resolved to no registry
  entry. Mapped to `wc-cart-table`, whose slots are the totals, prices and
  buttons inside the drawer.

- Shortcodes embedded in Elementor shortcode widgets were cut off at the first
  escaped quote, so `[products ids="4,5"]` was read as `[products ids=`.

- The settings screen's "Check for new elements" button threw a `TypeError` on
  every click: `request()` returned nothing and the caller chained `.always()`
  onto it. The scan ran, but the button stayed disabled reading "Scanning…"
  until the page was reloaded.

- The save button was `name="submit"`, which shadows the form's own `submit()`
  method.

### Changed

- **The settings screen was rebuilt for the person it is for.** It opened on a
  four-column `wp-list-table`: twelve rows, up to four `<select>` elements
  each — thirty-seven controls of equal weight — a Status column printing the
  words "default" and "configured", and colour options labelled with
  Elementor's internal ids, so a shop owner chose between "Primary" and
  "2666acb8" for a colour they had themselves named "Light Grey".

  Now: one card per store element, each with a plain sentence saying where it
  is seen ("The main button on a product page"), its main colour, and its
  remaining states behind a "More options" disclosure. Colours are listed under
  the titles from the kit. Interaction states read "When the mouse is over it"
  rather than "Button hover". Advanced is collapsed.

  The screen also now shows what the store actually looks like: a preview
  painted from the same saved mappings the front-end stylesheet is generated
  from, which repaints as colours are changed and follows the same WCAG
  contrast rule the generator uses. Verified against the generated CSS — a
  button changed to a dark colour predicted white label text in the preview,
  and the regenerated stylesheet emitted white label text.

  Field names, option keys and the save handler are unchanged, so stored
  mappings carry over untouched.

- Menu label is now **Store Design**, under WooCommerce and mirrored under
  Appearance. "Elementor Colors" named the source rather than the job, and
  stopped being true when fonts were added.

- Six admin stylesheets and five admin scripts became one of each; the JS no
  longer needs jQuery.

## [1.5.1] - 2026-08-13

Findings from a full security and standards audit.

### Security

- **Elementor kit colours reached the stylesheet unvalidated.** Stored colours
  were strictly checked, but values coming from the active kit's post meta and
  from the `eccw_kit_colors` filter were returned verbatim by `resolve_color()`
  and interpolated straight into `<style id="eccw-dynamic-css">` — served on
  every front-end WooCommerce page, and into `<style id="eccw-share-css">` on a
  publicly reachable share URL. A value carrying `</style><script>` escaped the
  block.

  Editing a kit takes an administrator, who can already inject scripts by other
  means, so this was not privilege escalation — but unvalidated input reaching
  a stylesheet is wrong regardless of who can write it, and the output is
  public. Colours now pass an allowlist: hex in all four lengths, plus the
  rgb/hsl function forms, since kits legitimately hold `rgba()` for
  transparency. Anything else is rejected rather than escaped, because there is
  no correct escaping for arbitrary text inside a CSS value.

- Added `wp_unslash()` before `sanitize_key()` on the share token and the live
  editor's widget and slot keys.

- **Narrowed four `phpcs:disable WordPress.Security.NonceVerification` blocks**
  that wrapped whole method bodies. Every one was factually correct — the nonce
  is checked in `verify_request()` — but a suppression spanning a whole method
  also silences the sniff for anything added to it later, which is exactly how
  unverified input gets in without anyone noticing. Now per-line.

### Fixed

- **Preview mode dismantled output buffers belonging to other plugins.** It
  called `ob_start()` and registered a shutdown function running
  `while ( ob_get_level() > 0 ) { ob_get_clean(); }`, draining every buffer on
  the stack — including those owned by caching and minification plugins, and by
  themes buffering for late escaping. It also meant a fatal error anywhere else
  still emitted the banner, appending markup to an error page.

  None of it was needed. The banner is `position: fixed`, so where it sits in
  the document does not matter; it now prints at `wp_footer` and reserves its
  space with padding on `<body>`. No buffering at all.

- Removed the `eccw_preview_active` cookie. It was written on every preview
  request and read by nothing — a header write, and a headers-already-sent
  risk, in exchange for no behaviour.

- **Bundled translations never loaded.** The plugin declares
  `Domain Path: /languages` and ships a template, but `load_plugin_textdomain()`
  was never called; WordPress.org's automatic loading covers translations it
  hosts, not ones shipped in the zip. Now loaded on `init`, which is also where
  WordPress 6.7 wants it.

- The onboarding notice echoed a raw `<script>` tag built on jQuery, on admin
  screens with no declared jQuery dependency. Rewritten without jQuery and
  printed through `wp_print_inline_script_tag()`, which lets a site attach a
  Content-Security-Policy nonce.

- Hardened the autoloader against class names carrying a namespace separator
  after the prefix, rather than reasoning about what path they would produce.

- `isset( $_POST ) && ! empty( $_POST )` — `$_POST` is always set.

- The settings status bar read `$settings` from whatever the including scope
  happened to define. It now resolves its own, so a template reordering cannot
  silently show "off" on a site whose colours are on.

### Performance

- **`eccw_colors_mappings` was autoloaded.** `add_option()` defaults to
  autoloading, so the plugin's largest option — twelve widgets, each with up to
  four slots, plus labels and status — was pulled into `alloptions` on every
  request on the site: front end, REST, cron, whether or not the page had
  anything to do with WooCommerce. It is only read on WooCommerce pages and the
  settings screen. The same applied to `eccw_onboarding_completed` and
  `eccw_wizard_dismissed`, which only wp-admin reads.

  All three are now `autoload => 'no'`. `eccw_settings` stays autoloaded on
  purpose: `Settings::all()` runs on every front-end request that generates CSS,
  and it is four booleans.

- `scan_progress()` now passes `cache_results => false`, matching
  `Discovery_Engine`. These posts are read once for their Elementor blob and
  never rendered, so priming the post cache with them only evicts entries the
  request actually needs.

### Added

- A versioned upgrade routine (`Plugin::maybe_upgrade()`, schema version 2).
  Activation does not re-run on update, so the autoload change would never have
  reached an existing install without it. Guarded by a single autoloaded
  integer, so an upgraded site does no work. Uses `wp_set_option_autoload()`
  where available, with a delete-and-re-add fallback for WordPress below 6.4.
- `data-testid` attributes on the status bar, master switch, rescan and preview
  controls, and the preview banner, so end-to-end tests do not depend on CSS
  classes that exist for styling.
- Twenty-one tests across the security allowlist and the upgrade routine, both
  verified against the pre-fix code first.
- An end-to-end suite under `e2e/` — Playwright against a real WordPress with
  WooCommerce and Elementor active, covering activation and uninstall against
  the database, the admin screens, the front-end stylesheet, the Live Editor and
  wizard, and the failure states. Every regression found by hand this cycle has
  a spec, because each one passed `phpunit` and `phpcs` while plainly broken in
  a browser. Not yet executed — see `e2e/README.md`.

## [1.5.0] - 2026-08-13

Two failures found by using the plugin rather than reading it: the cart table
lost its structure, and text could be painted invisible.

### Fixed

- **The cart table stopped looking like a table.** The "Table Rows" slot
  carried `border-color` alongside `color`, and it targeted the `<table>`
  element as well as its cells. A cart table's row and column dividers *are*
  those borders, so whatever colour was chosen for the text was also painted
  over every structural line in the grid — boxed-in and heavy with a dark
  colour, and completely erased with a pale one, which left the cart reading as
  an unstructured list. Nobody picking a colour for something labelled "Table
  Rows" is asking to redraw the table. It now styles text only and WooCommerce
  keeps its own dividers.

  Form inputs and notices still colour their borders, which is correct — those
  are decoration, not layout.

- **Text could be applied and invisible.** Auto-contrast only ran for slots
  that paint their own background: a button knows what is behind its label, so
  the label went black or white as needed. Slots that paint *only* text —
  prices, links, tabs, table headings, account navigation, around twenty of
  them — had no such protection. The chosen colour was emitted with
  `!important` and no relationship to anything behind it, so a pale brand
  colour on a white store produced text that was technically applied and
  completely unreadable.

  Text colours are now checked against the page background and nudged only if
  they fall below WCAG's 3:1 floor, moving in 2% steps until they clear 4.5:1.
  Hue and saturation are preserved, so the result still reads as the brand
  colour rather than being replaced by black. **A colour that is already
  legible is passed through untouched, character for character.**

### Changed

- "Pick readable button text automatically" is now "Keep text readable
  automatically", because it no longer only concerns buttons. Turning it off
  still applies every colour exactly as chosen.

### Added

- `eccw_page_background`, filtering the background text is checked against.
  Generated CSS cannot measure a rendered page, so the check assumes white; a
  dark-themed store corrects that in one line. The assumption is stated rather
  than hidden.
- Fifteen tests covering contrast maths, hue preservation, dark-background
  behaviour, and the cart table keeping its borders to itself.

  Worth recording how these were checked: the first draft tested
  `ensure_readable()` in isolation and passed with the fix reverted, because
  nothing asserted that the generator actually *called* it. The test that
  matters builds real CSS for a near-white price and fails on the old code.

## [1.4.2] - 2026-08-13

Clean against the WordPress coding standards, and the settings screen no longer
opens with three numbers only a developer could read.

### Changed

- **The status bar said "Detected Colors: 4 | Widgets: 12 | Version: 7".** Two
  of those were internal counters. "Widgets" is Elementor's word rather than a
  shop owner's, and "Version" was the mappings cache counter — meaningless to
  anybody, and it reads alarmingly like the plugin's own version number. It now
  answers the three questions someone actually arrives with: whether their
  colours are on, how much of the store is styled, and where the colours come
  from. When no Elementor globals are set it says so and links to where to set
  them, rather than quietly reporting a count of stand-ins.
- Header buttons say what they do. "Rescan" → "Check for new elements",
  "Dismiss All New" → "Mark all as reviewed", "Preview on Site" → "See it on my
  store".
- "Map each WooCommerce element to an Elementor global color" → "Choose which of
  your brand colors each part of your store uses". Mapping is a thing this
  plugin does internally, not a thing a user does.
- **Renamed the `ECCw_*` constants to `ECCW_*`.** The mixed-case prefix was the
  plugin's oldest standards violation, undocumented anywhere users could see, so
  the rename is invisible outside the codebase.

### Fixed

- The last six missing `translators:` comments, on the draft-age, share-expiry
  and three contrast strings. Without them a translator sees `%s` with no way to
  know it is a ratio, a duration or an elapsed time.
- Array alignment across two files, via `phpcbf`.

**`phpcs` now reports zero errors and zero warnings against WordPress-Extra**,
down from the 10 and 6 that had been carried since before 1.1.0.

### Removed

- `$saved_version`, which the settings screen stopped rendering with the status
  bar rewrite and nothing else read.

## [1.4.1] - 2026-08-13

Deleting the plugin from a network now actually deletes it.

### Fixed

- **Uninstall only ever cleaned one site.** It runs once, in the context of
  whichever site the request happened to be on, and options, transients and the
  daily scan are all per-site. Deleting the plugin from a network therefore left
  a complete set of rows behind on every other subsite — permanently, because
  once the plugin files are gone there is no code left to remove them. Uninstall
  is the one path that cannot be re-run to fix a mistake.

  It now iterates every site with `get_sites()`, paging in hundreds, and cleans
  each one. User meta is deliberately swept once rather than per site:
  `wp_usermeta` is a single shared table across a network, so repeating it would
  be the same delete N times.

- The scheduled `eccw_daily_scan` is now cleared on every site rather than one.
  A leftover cron entry pointing at a callback that no longer exists is
  harmless, but it is still litter we left behind.

### Changed

- On a network large enough for `wp_is_large_network()` to say so, the per-site
  sweep is skipped rather than attempted. Deleting across thousands of sites
  cannot finish inside one request, and a half-finished delete is worse than a
  clean skip: it looks done, and the remainder has nothing left to clean it.
  User meta is still removed, since that is one query whatever the size. The
  file documents the WP-CLI equivalent for finishing the job deliberately.

### Added

- Thirteen tests for uninstall, covering the network sweep, paging past the
  first hundred sites, terminating on an exact page boundary, restoring the
  original site afterwards, and not touching other plugins' options. Verified
  against the old implementation first: six fail on it.

## [1.4.0] - 2026-08-13

The free plugin is now the complete product.

### Added

- **A master switch in the admin toolbar.** "Store Colors" appears on every
  page, front end and admin, showing whether your colours are being applied and
  turning them off in one click. The switch already existed on the settings
  screen, which is a page load away from wherever the problem is actually
  visible — and someone on the phone saying "the site looks broken" does not
  wait for a page load. The menu says nothing is deleted either way, because a
  control that might be destroying your work is not one anybody reaches for in
  a hurry.

### Changed

- **The free tier is now complete for one person on their own site, and the
  feature gate says so.** Free is no longer a sampler: the Live Editor is
  unrestricted, every interaction state is available, colours are not limited
  to the four Elementor globals, and undo keeps 50 steps rather than 10.

  Two rules decided the line and are recorded in `Features`. Nothing that
  protects the user is ever paid — undo, reset, the master switch, contrast
  warnings; charging for the safety net of a plugin that rewrites a live store
  is charging someone not to lose their work. And nothing that makes the tool
  feel broken is paid; a colour picker that cannot pick a colour is not a
  limited plugin, it is a bad one.

- **Third-party add-on widget recognition is free.** It was gated, and gating
  it was a mistake. The map is not a bonus set of widgets — it is how discovery
  identifies a WooCommerce element at all, and Elementor free ships no
  WooCommerce widgets, so a large share of sites build them with Essential
  Addons or Premium Addons. Withheld, those sites would have discovered nothing
  and experienced the plugin as broken rather than as limited.

- Undo depth is 50 steps, filterable via `eccw_history_limit`, and described as
  50 rather than as "unlimited". Each step is a full snapshot in one user-meta
  row, so an unbounded stack would grow without any bound the user can see —
  and advertising unlimited while quietly trimming is the sort of thing this
  plugin removed in 1.3.3.

### Removed

- Seven feature-gate constants that reserved the right to charge for things
  that are now free for good: `LIVE_EDITOR`, `EXTRA_STATES`, `CUSTOM_COLORS`,
  `FULL_HISTORY`, `CONTRAST_REPORT`, `IMPORT_EXPORT` and `ADDON_WIDGETS`. Five
  of them were never read by any code. An unused gate is worse than no gate: it
  implies a decision nobody made and invites someone to flip it later.

  `SHARE_LINKS` and `PAGE_OVERRIDES` remain, and both still resolve to
  available. Share links already ship free and are advertised in `readme.txt`;
  withdrawing a shipped feature earns one-star reviews that cannot be removed,
  so that stays as it is until we settle whether 1.0.0 was ever published.

### Fixed

- **The mappings version counter had no owner.** It feeds the generated CSS
  cache key, so it has to move whenever mappings change and must never revisit
  a value. Four call sites incremented it by hand with two different starting
  values; Reset to defaults and rescan changed mappings without touching it at
  all; and undo restored an old state wholesale, reinstating a version number
  already used and making one cache key reachable twice with different contents
  behind it. All six paths now go through `Mapping_Service::bump_version()`,
  which only moves forward. None of this was visible while it was wrong,
  because 1.3.4's cache flush covered for it.

## [1.3.4] - 2026-08-13

Clearing the colour cache now actually clears it.

### Fixed

- **On a site with a persistent object cache, nothing ever invalidated the
  generated CSS.** Cached stylesheets are stored with `set_transient()`, and
  when Redis or Memcached is active — normal on managed WordPress hosting —
  transients are written to that cache and never reach the options table.
  Invalidation was `DELETE FROM wp_options ... LIKE '_transient_eccw_css_%'`,
  which on those hosts matched nothing and reported nothing, while nine call
  sites relied on it.

  Most paths survived because the cache key already carried the kit colours and
  the settings signature. Two did not: `reset_defaults()` and `rescan()` both
  replace mappings *without* incrementing the stored mappings version, so their
  key was unchanged and the dead sweep was their only invalidation. The visible
  result was that **"Reset all elements to defaults" reported success and the
  store kept serving the old colours**, for up to the 30-day cache lifetime.

  Invalidation is now a generation token — a random string stored in
  `eccw_css_generation` and folded into every cache key, so replacing it orphans
  every entry at once. It behaves identically on both storage backends and
  cannot half-succeed. A token rather than an incrementing counter because an
  increment is a read-modify-write, and two concurrent invalidations racing
  would lose one bump and reintroduce the same stale serve.

  The row sweep is kept as housekeeping for sites with no object cache, and is
  now skipped entirely when one is present instead of running a query that can
  only match nothing.

### Changed

- Cache lifetime drops from 30 days to 7. Orphaned entries are the normal
  outcome of token-based invalidation, so on sites without an object cache the
  TTL is what bounds how long they sit in the options table. Rebuilding is
  string concatenation over an in-memory registry and costs very little.
- `CSS_Generator::cache_key()` is now a named method rather than five lines
  inline, so the invalidation contract can be asserted directly.

### Added

- Thirteen tests for cache invalidation, including two that run with a
  persistent object cache simulated. Verified against the old implementation
  first: three of them fail on it, which is the point. The test bootstrap grew
  a `$wpdb` fake that records statements instead of running them, so a test can
  assert that a path issued no query at all.

## [1.3.3] - 2026-08-13

The wizard no longer asks for an email address in exchange for something that
does not exist.

### Removed

- **The "Get Pro Tips" email signup on step 4.** It offered a "WooCommerce
  Color Psychology" guide that was never written, and nothing in the plugin
  could have sent it — the address went into the `eccw_pro_optin_email` option
  and no code ever read it back. The screen also said nothing about the address
  being stored at all, which is an undisclosed collection of personal data
  under the WordPress.org guidelines, and a broken promise on the one screen
  whose job is to earn trust. Step 4 keeps its two real calls to action, "Open
  Live Editor" and "Go to Elementor Colors". The `eccw_save_email` AJAX action
  and its handler are gone with it.

### Fixed

- **Addresses already collected are now deleted, not just left behind.**
  Upgrading removes any value stored in `eccw_pro_optin_email` on the first
  admin request, rather than holding it until someone uninstalls the plugin.
  `uninstall.php` still deletes the option too, since the upgrade path only
  runs when WooCommerce and Elementor are both active.
- The Privacy Notices section of `readme.txt` described the old signup as an
  ongoing feature. It now states plainly that the plugin collects no personal
  data, and records what the removed signup did and that upgrading clears it.

### Changed

- Regenerated `languages/color-changer-for-elementor.pot`. It was stamped
  1.0.0 and predated the 1.3.2 wizard rewrite, so it still shipped the removed
  signup copy — including the guide that does not exist — to translators.

## [1.3.2] - 2026-08-13

The onboarding wizard now shows a real before/after, and stops claiming a
setting it never saved.

### Fixed

- **"See the Difference" showed two identical panels.** The generated stylesheet
  was injected into the wizard's own document, and a `<style>` tag applies to
  its whole document regardless of where it sits — so the "after" rules, which
  carry `!important` on front-end selectors like `.woocommerce a.button.alt`,
  repainted the "before" panel too. The entire point of the step was a no-op.
  Each panel is now a self-contained iframe document, which also stops the
  WooCommerce stylesheets and those `!important` colour rules leaking into the
  surrounding admin page, and stops the product markup's element IDs appearing
  twice on one page.
- **Step 3's checkbox silently discarded the user's choice.** It wrote
  `eccw_dequeue_settings` — the option `Settings` absorbed in 1.2 and now reads
  only once, during migration, which has already run by the time onboarding
  finishes. The write landed nowhere. Worse, the box was checked and labelled
  "Recommended" while `Settings::defaults()` deliberately keeps the blocks
  dequeue off, so the wizard promised the opposite of what the plugin did. Step
  3 is now an explainer that links to the settings screen, where that toggle
  lives with the warning it needs. Its label copy was wrong too — it described
  the plugin's whole purpose rather than a blocks-stylesheet dequeue.
- **The colour toggle did nothing.** It toggled a class no stylesheet defined.
  It now switches the "after" panel between your colours and the defaults, with
  the panel heading following along.
- **Finishing the scan yanked the user out of whichever step they were on.**
  The scan advanced the wizard on completion regardless of position, so clicking
  ahead to step 3 while it was still batching pushed you to step 4. It now only
  advances if you are still on step 1.

### Added

- An honest note on step 2 when there is nothing to apply. Two identical panels
  now say why — no Elementor global colours are mapped yet — and link to where
  to set them, rather than implying the plugin does nothing.
- The wizard strings this release touches moved into the localised `i18n`
  bundle. The rest of `eccw-wizard.js` is still hardcoded English.

## [1.3.1] - 2026-08-12

The editor now tells the user what state their work is in.

### Fixed

- **"Copy link" in the share dialog was broken.** The click handler referenced
  an undefined `self`, throwing a ReferenceError, so the confirmation never
  appeared and the copy silently failed on browsers where `execCommand` is
  disabled. Now uses the async Clipboard API with an `execCommand` fallback and
  reports honestly when it cannot copy.
- The share dialog claimed the link "expires after one hour" — hardcoded, and
  wrong since the lifetime changed. It now reports the real expiry sent by the
  server.
- Save and connection failures said "Save failed" / "Connection error", which
  reads as *your work is gone*. They now say the changes are still there, which
  is true — drafts persist server-side.

### Added

- **Restored-draft bar.** Returning to the editor with unsaved changes shows how
  long ago they were made and a Discard button. Previously a user saw colours
  they did not remember choosing with no way to tell saved from unsaved, and no
  way to back out.
- **Leave-page warning** when unsaved changes exist. Suppressed for navigation
  the user asked for, such as reset and undo reloads.
- **Confirmation that a change reached the server** — the unsaved badge flashes
  green as each colour is stored.
- **Escape** closes the color panel and the share dialog.
- Every editor string moved to an `i18n` bundle passed from PHP. They were all
  hardcoded English, so the editor was untranslatable on a non-English site.

## [1.3.0] - 2026-08-12

Do not lose the user's work, and do not show their client the wrong thing.

### Fixed

- **Unsaved changes could disappear.** Live-editor drafts lived in a transient
  with a one-day expiry, so a long session, a cache flush or a hosting-level
  object-cache purge silently discarded them. Drafts now persist in user meta
  with no expiry.
- **Two administrators overwrote each other.** The draft key was site-wide, not
  per-user, so a second admin editing at the same time replaced the first
  admin's work and saw their colours instead. Drafts are now scoped per user.
- **Share Preview sent clients the wrong colours.** The unsaved draft was only
  consulted when *nothing at all* was saved, so a designer mid-edit generated a
  link showing the previously saved state. Draft changes are now layered over
  the saved state, which is the only sensible behaviour for a preview link.
- Share links expired after one hour with no indication anywhere. Now seven
  days, and the response states how long the link lives.

### Added

- `Draft_Store` — per-user draft persistence, with one-time adoption of any
  draft left in the old transient so an upgrade mid-session loses nothing.
- Discard endpoint (`eccw_discard_draft`), so a user can back out of an
  experiment. Previously the only way to resolve a draft was to save it.
- Draft state passed to the editor: whether one exists, when it was written,
  and the strings for a returning-session notice and a leave-page warning.
- `DraftStoreTest`, including coverage for per-user isolation and legacy
  adoption.
- User-meta shims in the test harness.

## [1.2.0] - 2026-08-12

Control and reversibility. The plugin rewrites the appearance of a live store,
so the user needs an obvious way to stop it, and an obvious way to decide how
forcefully it acts.

### Added

- **Master switch.** "Apply my brand colors to WooCommerce" returns the store
  to stock WooCommerce immediately without deleting a single saved color.
  Previously the only escape was an undocumented `?eccw_disable=1` URL that
  lasted one request.
- **Override theme styles** toggle. Generated declarations carried
  `!important` unconditionally, so a site owner could not win against the
  plugin with their own CSS. Now their choice.
- **Pick readable button text automatically** toggle. Label color was forced
  to black or white whenever a slot painted a background, silently discarding
  a deliberately chosen label color with no way to prevent it.
- `Settings` class: one set of typed defaults, one sanitizer, one migration
  path. Behaviour flags were previously read with `get_option()` and an inline
  default at each call site.
- Controls card on the settings screen, plus `controls.css`.
- `SettingsTest` covering defaults, merging, checkbox coercion, legacy
  migration and cache-key behaviour.

### Changed

- **The WooCommerce Blocks dequeue now defaults to off.** It carries layout
  and spacing for block-based Cart and Checkout, not only color, so enabling
  it can rearrange those pages. Existing sites keep whatever they had
  selected; only new installs get the safer default.
- The former "CSS Dequeue Settings" card is now "Advanced", with the layout
  consequence stated plainly instead of implied.
- CSS cache key includes a settings signature, so toggling a style option
  invalidates cached output instead of serving stale CSS.
- Settings screen copy rewritten around what the user is deciding rather than
  what the code does.

### Fixed

- The readme claimed "The plugin only removes CSS, not markup. Your theme
  layout and spacing remain intact." That was not true with the blocks
  dequeue enabled. Corrected, with troubleshooting entries for the three
  questions this plugin will actually generate.

## [1.1.1] - 2026-08-12

### Fixed

- Activation could time out on large stores. The discovery scan read every
  published page and product and JSON-decoded each Elementor layout in one
  request; it now reads the 50 most recently modified on activation and is
  capped at 250 elsewhere, filterable via `eccw_scan_limit`.
- "Unsaved changes" in the live editor toolbar was hardcoded English and never
  translatable.
- Deactivating the plugin deleted the stored opt-in email. Deactivation no
  longer destroys data; uninstall still does.
- The mappings `version` counter was written once at activation and never
  incremented, so any cache key derived from it never changed. It now bumps
  on every save.

### Added

- `eccw_auto_contrast_text` — opt out of forcing button label colour to black
  or white, which previously overrode an explicitly chosen label colour with
  no way to prevent it.
- `eccw_force_important` — opt out of `!important` on generated declarations
  so site CSS can win.

## [1.1.0] - 2026-08-12

Groundwork for a paid add-on. No user-visible change: every new gate and
filter defaults to current behaviour.

### Added

- `Features` gate — single place to ask whether a capability is available.
  Free answers from its defaults; an add-on answers by hooking
  `eccw_feature_{name}` or `eccw_is_pro`.
- `Container` — lazy service registry so an add-on can substitute an
  implementation before it is built.
- Lifecycle actions: `eccw_register_services`, `eccw_loaded`,
  `eccw_editor_started`, `eccw_admin_assets_enqueued`,
  `eccw_settings_page_before`, `eccw_settings_page_after`.
- Filters: `eccw_slot_states`, `eccw_kit_colors`, `eccw_generated_css`,
  `eccw_sanitized_mappings`, `eccw_editor_allowed`, `eccw_editor_data`,
  `eccw_admin_data`, `eccw_settings_panels`, `eccw_current_page_type`,
  `eccw_is_woocommerce_page`, `eccw_service`.
- Unit tests for `Features` and `Container`.
- Test harness shims for `do_action`, `add_filter` and `has_filter`.

### Changed

- `Plugin::load()` resolves services through the container instead of
  instantiating them directly.
- `CSS_Generator::get_kit_colors()` split from the new private
  `read_kit_colors()`, so every path applies `eccw_kit_colors`. Previously
  two early returns bypassed post-processing.
- `Element_Registry::get_registry()` runs slot states through a single
  policy pass rather than each widget definition standing alone.

## [1.0.0] - 2026-08-08

### Added

- Initial release: dynamic CSS mapping Elementor global colors to
  WooCommerce elements.
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
- Filterable widget registry and addon map via `eccw_element_registry` and
  `eccw_addon_widget_map` filters.
- `Mapping_Service::ensure_defaults()` seeds every registry widget with
  heuristic defaults when mappings are empty or slots are missing. Wired
  into activation, cron rescan and the manual rescan AJAX handler so the
  option can never be empty and the frontend always receives replacement CSS.
- `Dequeue_Manager::should_dequeue()` gates block stylesheet removal on the
  presence of element mappings, preventing unstyled storefronts. Only
  `wc-blocks-style` and `wc-blocks-vendors-style` are removed; the structural
  core sheets (`woocommerce-general`, `woocommerce-layout`,
  `woocommerce-smallscreen`) are always kept.
- Elementor Colors page refactored into components: PHP partials under
  `admin/templates/components/`, vanilla JS modules under
  `admin/js/components/` (no build step) and per-component stylesheets under
  `admin/css/components/`.
- Logic-core PHPUnit test suite covering the registry, heuristic engine,
  page context, CSS generation, mapping service and dequeue manager. Run
  locally with `composer test`; `composer phpcs` checks coding standards.
- `CHANGELOG.md`.

### Changed

- Button text and SVG fill now use an auto-derived WCAG contrast color
  (white on dark backgrounds, near-black on light backgrounds) whenever a
  slot paints a background color, so labels stay readable.
- Settings page shell (`settings-page.php`) now orchestrates component
  partials and exposes `defaults` / `newCount` to the frontend via
  `eccwData`.
- Dismiss All New button is disabled when there are no "new" widgets.
- Dequeue toggles are disabled (with an explanatory notice) when no element
  mappings exist, mirroring the frontend gating.
- Onboarding wizard Step 3 simplified for non-technical users: the technical
  "CSS Dequeue Settings" checkboxes are replaced with a single plain-language
  toggle ("Replace default styling with your brand colors") that sets the
  `dequeue_blocks` option, on by default.
- Onboarding wizard now detects whether Elementor global colors are set and,
  when they are not, shows a friendly notice with a link to set them.
- `CSS_Generator::has_kit_colors()` added to distinguish a kit with real
  brand colors from the plugin's fallback palette.
- readme.txt rewritten to lead with a plain-language 3-step setup.
- Renamed admin menu to "Elementor Colors".
- Consolidated the six duplicated widget-key migration loops, the two
  `walk_elements()` copies and the mapping sanitizer into a single
  `Mapping_Service` class.
- Plugin source checked against `WordPress-Extra` via `composer phpcs`. One
  known exception remains: the `ECCw_*` constant prefix trips the
  uppercase-constants sniff in `class-plugin.php`.
- One-time onboarding redirect no longer hijacks every admin page; a
  dismissible admin notice takes over (see `eccw_wizard_dismissed`).
- Plugin header now declares `Requires Plugins: woocommerce, elementor`,
  `Tested up to: 7.0`, and aligns version with the readme stable tag (1.0.0).
- Readme now includes a privacy notice for the optional Pro opt-in email.
- Live editor: clicking any element opens its color card instead of firing
  the element's own action; a visible kit-color palette lets non-technical
  users pick colors instantly without touching a code editor.

### Fixed

- Buttons rendered with identical background and text color when the mapped
  Elementor global color was applied to both (`color` now uses the contrast
  helper instead of the raw background color).
- Dequeueing WooCommerce block CSS while mappings were empty left the shop
  page unstyled; dequeue is now inert without mappings.
- Reset-to-default now uses the heuristic default token instead of a fixed
  value, and includes dequeue checkboxes in the unsaved-changes guard.
- `woocommerce-*` widget keys were normalized with an off-by-one offset,
  producing broken registry keys (e.g. `woocommerce-product-price` failed to
  map to `wc-product-price`).
- Onboarding wizard forced a redirect on every admin page load until
  completion; now fires once after activation, then surfaces as a notice.
