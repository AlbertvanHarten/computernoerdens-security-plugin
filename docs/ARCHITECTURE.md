# Architecture notes

You asked to skip a formal up-front design doc and go straight to code this
round — fair enough, the foundation already existed. This is the lighter-weight
version: what's actually here and why, written *after* the fact so the next
person (or the next Claude session) isn't reverse-engineering it from diffs.

## Module system

Every feature is a class implementing `ModuleInterface` (`src/Contracts/`),
registered in `ModuleManager`. A module:

- only adds its WordPress hooks in `boot()`, and `boot()` is only called when
  the module is enabled — disabled modules cost nothing at runtime (the spec's
  "lazy loading where practical")
- reports its own `status()` and `score()` for the Security Center dashboard
- can opt out of the overall score via `countsTowardScore()` (Diagnostics and
  Reports do — they don't harden anything themselves)
- can expose a settings panel via `hasSettings()` + `sanitizeSettings()`; if it
  does, `templates/admin/partials/module-{id}.php` is the form for it

Adding a new module means: one class implementing `ModuleInterface` (extend
`AbstractModule` for the common defaults), one `register()` call in
`ModuleManager`, and — if it has settings — one partial template and one entry
in `SettingsRepository::defaults()`.

## Why some interfaces were removed

`ApplicationInterface` and `ContainerInterface` existed in the original
foundation but had exactly one implementation each, with nothing instantiating
an alternative or mocking them in isolation. Per your instruction ("use
interfaces only when multiple implementations exist or testing benefits"),
they're gone — `Application` and `Container` are now concrete classes.
`ModuleInterface` stays, because seven concrete modules genuinely implement it
interchangeably through `ModuleManager`.

## Settings

One option (`cno_security_settings`), one `SettingsRepository`, dot-path
get/set (`$settings->get('csp.mode')`). No custom database tables anywhere —
even the CSP violation log and scanner results are capped arrays in the same
option tree. `SettingsRepository::upgrade()` is a minimal schema-version
migration hook (bump `SettingsRepository::SCHEMA_VERSION` and add a branch),
used once already for the v1->v2 move from a single raw CSP policy string to
per-directive settings.

## AJAX, not pages-with-Save-buttons

`AjaxController` is the only place `wp_ajax_*` actions are registered. Every
write goes through it: module toggles, per-module settings saves, scanner
runs, CSP learning-mode actions, report exports. `Admin.php` only registers
menus and renders templates — it doesn't process POST data.

Module settings panels save automatically: checkboxes/selects save on change,
text/number fields save on a 600ms debounce after typing stops. There is no
Save button anywhere in the admin UI, by design (matches the original Modules
page behaviour, extended to per-module settings).

## CSP module

The flagship module, deliberately the most built-out:

- `csp.directives` is an associative array (directive -> source list string),
  not a single raw policy string, so the UI can offer a real builder
- nonces are generated once per request (`CspModule::nonce()`, request-scoped
  via a private static) and wired into `wp_inline_script_attributes`,
  `script_loader_tag`, and `style_loader_tag` — covering WordPress-enqueued
  and `wp_add_inline_script`-printed code, not yet every possible raw inline
  tag a theme might print directly (see ROADMAP)
- "learning mode" forces Report-Only and registers
  `POST /wp-json/cno-security/v1/csp-report`, a deliberately unauthenticated
  REST route (browsers send violation reports with no auth) that writes into
  `CspViolationStore` — a small capped-option store, not a database table
- the violation log is summarised (grouped by directive + origin, counted,
  sorted) rather than shown as a raw event stream, so "Add to policy" is a
  one-click action against real traffic instead of a log a human has to parse

## Free/Pro

See `docs/FREE-PRO.md` for the proposed split and `src/Settings/EditionManager.php`
for the gate. One filter (`cno_security/edition`), default `'free'`.

## PHP 7.0 floor — things that are easy to slip on

Local dev runs PHP 8.5; the minimum is 7.0. The features that look harmless
but aren't available until later versions, all avoided on purpose in this
codebase:

- typed properties (`private string $x;`) — PHP 7.4
- `void`/nullable return types (`: void`, `: ?string`) — PHP 7.1
- visibility modifiers on class constants (`private const X = 1;`) — PHP 7.1
- arrow functions (`fn() => ...`) — PHP 7.4
- constructor property promotion — PHP 8.0
- the `match` expression, nullsafe `?->`, named arguments — PHP 8.0+

Plain scalar param/return type declarations (`string`, `int`, `bool`, `array`)
and the null coalescing operator (`??`) are fine — both are PHP 7.0.
`phpcs.xml.dist` enforces PSR-12 but doesn't catch version drift yet (it would
need the separate PHPCompatibility ruleset, which needs its own Composer
install) — worth adding once you can run `composer install` somewhere with
registry access.

## Known gaps (tracked in `docs/ROADMAP.md`)

- No automatic CSP generation or third-party compatibility profiles
  (Elementor/WooCommerce/Cookiebot/Google Fonts/Maps/YouTube/Vimeo)
- No online internet.nl / sikkerpånettet.dk scoring (Scanner is local-only by
  design — see the module's docblock)
- No automated test suite yet (`tests/` is still empty)
- Hash generation is manual entry only, no "compute this hash for me" tool
