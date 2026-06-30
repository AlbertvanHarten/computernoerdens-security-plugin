# Roadmap

What's deliberately not in this milestone, roughly in the order it'd make
sense to tackle:

## Near-term

- **Automated tests.** `tests/` is still empty. Given WordPress dependencies,
  the practical path is [Brain Monkey](https://github.com/Brain-WP/BrainMonkey)
  or `WP_Mock` for unit tests against the module classes (most logic — CSP
  directive sanitisation, scanner scoring, settings merging — is pure PHP and
  testable without a full WordPress install).
- **PHPCompatibility in CI.** `.github/workflows/lint.yml` currently runs
  `php -l` only. Adding the `PHPCompatibility` PHPCS ruleset (targeting 7.0)
  to that workflow would catch version drift automatically instead of relying
  on the manual checklist in `docs/ARCHITECTURE.md`.
- **Hash auto-generation.** Let an admin paste a URL or pick an enqueued
  script/style and have the plugin compute the sha256 hash, instead of typing
  it in by hand.

## Medium-term (mostly Pro, see `docs/FREE-PRO.md`)

- **Third-party CSP compatibility profiles** for Elementor, WooCommerce,
  Cookiebot, Google Fonts, Google Maps, YouTube, Vimeo — one-click "this site
  uses X" toggles that pre-populate the relevant directives, instead of
  learning mode being the only path to a working policy.
- **Automatic CSP generation** from a crawl of the site's own rendered pages.
- **Online readiness scoring** against internet.nl and sikkerpånettet.dk —
  needs its own outbound-request architecture (queuing, rate limiting,
  caching results) since the rest of the plugin deliberately never makes
  outbound calls.
- **Branded PDF client reports** (`ReportsModule::pdfAvailable()` is already
  gated and waiting).
- **Scheduled scans + alerting** (email/Slack) on top of the existing
  on-demand `ScannerModule::runScan()`.
- **Multisite network-wide policy management.**

## Smaller things worth doing whenever convenient

- Inline `<style>` nonce coverage: `wp_add_inline_style()` output doesn't go
  through a filter the way `wp_add_inline_script()` does, so nonce coverage
  for inline styles is currently enqueued-stylesheet-only (`style_loader_tag`).
  Worth re-checking each WordPress release in case a filter is added.
- `docs/SPRINT-1.2.md` is kept as a historical record of the original
  foundation milestone; newer milestones should get their own dated doc
  rather than editing it in place.
- Internationalisation: strings are written in English with `'computernoerdens-security-plugin'`
  as the text domain throughout, but no `.pot` file exists yet in `languages/`.
