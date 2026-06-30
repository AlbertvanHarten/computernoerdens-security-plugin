# Changelog

## 2.1.0-alpha1

### Added
- HTTPS module: HSTS with optional preload, force-SSL URL rewriting, reverse
  proxy and Cloudflare-aware HTTPS detection.
- Security Headers module: every header now individually configurable
  (Referrer-Policy, X-Frame-Options, Permissions-Policy, COOP, CORP, COEP).
- CSP module rewritten: per-directive policy builder, per-request nonce
  support for script-src/style-src, manual hash allowlisting, learning mode
  with a new REST endpoint (`POST /wp-json/cno-security/v1/csp-report`) and a
  one-click "add to policy" UI driven by a summarised violation log.
- security.txt module: full RFC 9116 field editor (contact, expires,
  encryption, acknowledgments, canonical, policy, hiring, preferred languages).
- New Scanner implementation: 12 real local-only checks replacing the
  placeholder (no outbound network calls).
- New Diagnostics module: read-only environment overview.
- New Reports module: on-screen module summary, CSV/JSON export. PDF export
  gated behind a new `EditionManager` (Pro), see `docs/FREE-PRO.md`.
- `cno_security_csp_nonce()` helper function for themes/plugins printing their
  own inline `<script>`/`<style>` tags.
- `AjaxController`: every module's settings now save immediately via AJAX
  (checkboxes/selects on change, text fields on a short debounce) — no Save
  button, anywhere.
- `docs/ARCHITECTURE.md`, `docs/ROADMAP.md`, `docs/FREE-PRO.md`,
  `docs/SPRINT-2.0.md`, `phpcs.xml.dist`.
- One-time settings migration (`SettingsRepository::upgrade()`) from the old
  single raw `csp.policy` string to the new `csp.directives` array.

### Changed
- Entire foundation rewritten to PSR-12 (was condensed single-line PHP).
- `Admin.php` now only registers menus and renders templates; all AJAX
  handling moved to the new `AjaxController`.
- `ModuleManager` now registers seven modules (was five) and excludes
  Diagnostics/Reports from the overall score via `countsTowardScore()`.

### Removed
- `ApplicationInterface` and `ContainerInterface` — each had exactly one
  implementation and no test double, so per the project's interface-discipline
  guidance they were replaced with concrete classes.
- `PlaceholderModule` (was incomplete — missing required interface methods).

## 2.0.0-alpha2

- Added persistent Settings Repository.
- Added module enable/disable settings.
- Added AJAX module toggles.
- Added Security Center UI.
- Added Modules UI.
- Added HTTPS module with HTTP to HTTPS redirect.
- Added Security Headers module.
- Added CSP module foundation.
- Added security.txt module foundation.
- Added Scanner placeholder.
- Added dashboard scoring foundation.
- Added .gitattributes and .editorconfig.

## 2.0.0-alpha1

- Initial application bootstrap.
- Composer PSR-4 autoloading.
- Service container.
- Application contract.
- Module contract.
- Module manager.
- Admin interface foundation.
