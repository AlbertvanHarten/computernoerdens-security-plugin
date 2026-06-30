# Computernørden's Security Plugin

A professional Security Center for WordPress, built to be one of the
technically strongest hardening plugins available — aimed at compliance with
[sikkerpånettet.dk](https://sikkerpaanettet.dk/), [internet.nl](https://internet.nl/),
modern browser security standards, and OWASP/current HTTP security best
practice.

## Sprint 2.0 (alpha1)

- Security Center dashboard with score, recommendations, warnings, latest scan
- Modules page — every module configurable inline, no Save button, everything
  saves immediately via AJAX
- **HTTPS** — redirect, HSTS (+ preload), force SSL, reverse proxy / Cloudflare
  aware HTTPS detection
- **Security Headers** — X-Content-Type-Options, Referrer-Policy,
  Permissions-Policy, X-Frame-Options, COOP/CORP/COEP
- **Content Security Policy** — per-directive builder, nonce support, manual
  hash allowlisting, enforce/report-only, learning mode with a REST violation
  endpoint and one-click policy suggestions from real traffic
- **security.txt** — full RFC 9116 editor, published at `/.well-known/security.txt`
- **Scanner** — local-only readiness scan (no outbound requests)
- **Diagnostics** — read-only environment overview
- **Reports** — on-screen summary, CSV/JSON export (PDF is a planned Pro
  feature, see `docs/FREE-PRO.md`)

See `docs/ARCHITECTURE.md` for how it's put together and `docs/ROADMAP.md` for
what's intentionally not here yet.

## Requirements

- PHP 7.0+ (developed against 8.5 locally; the codebase deliberately avoids
  anything newer than 7.0 — see the checklist in `docs/ARCHITECTURE.md`)
- WordPress 6.5+

## Development

```bash
composer dump-autoload
composer run lint   # php -l across src/
composer run cs     # PSR-12 via phpcs.xml.dist (requires `composer install`)
```

## Coding standards

PSR-1, PSR-4, PSR-12. Namespaces and Composer autoloading throughout. No
custom database tables — settings live in a single option, read/written
through `SettingsRepository`. Interfaces only where more than one
implementation exists (`ModuleInterface`) — `Application` and `Container` are
concrete classes.
