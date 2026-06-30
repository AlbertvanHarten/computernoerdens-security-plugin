# Free vs. Pro

You asked me to propose the split rather than guess at one mid-build. Here's the
reasoning and the proposed line — change anything that doesn't fit your plans.

## Principle

**Free is a genuinely complete hardening plugin on its own.** Nothing in Free is
artificially crippled to push an upgrade — every module fully protects a site.
Pro is for things that save *time* (automation, reporting, multisite/agency
workflows) or require *infrastructure the plugin itself doesn't want to run*
(online scanning against internet.nl/sikkerpånettet.dk, which means outbound
requests to a third party — not something Free should ever do silently).

This matches the spec: "the architecture should be designed to support a Free
edition and a future Pro edition without major refactoring" — the split below
follows infrastructure/automation lines, not "disable half the security."

## Free (everything currently built)

- HTTPS: redirect, HSTS (+ preload), force SSL, reverse proxy / Cloudflare awareness
- Security Headers: all current headers, fully configurable
- CSP: full per-directive builder, enforce/report-only, manual nonce support,
  manual hash allowlisting, learning mode + violation log + one-click "add to
  policy" from real traffic
- security.txt: full RFC 9116 editor
- Scanner: full local readiness scan (no outbound requests)
- Diagnostics: full environment overview
- Reports: on-screen summary + CSV/JSON export

## Proposed Pro

| Feature | Why Pro |
|---|---|
| **Automatic CSP generation** — crawl the site's own rendered output and pre-fill directives instead of starting from the learning-mode log | Real automation/time-saving, not a missing protection |
| **Plugin/theme compatibility profiles** (Elementor, WooCommerce, Cookiebot, Google Fonts/Maps, YouTube, Vimeo auto-detected CSP rules) | Ongoing maintenance burden (these break with every third-party update) that's reasonable to fund directly |
| **Online readiness scoring** against internet.nl / sikkerpånettet.dk | Requires outbound calls to a third party on the customer's behalf — infrastructure cost, and something Free should never do without explicit, separate opt-in anyway |
| **Branded, white-labelled PDF client reports** | Agency/client-facing workflow (this is literally how Computernørden would bill clients) |
| **Multisite network-wide policy management** | Agency/multi-client workflow |
| **Scheduled scans + email/Slack alerting** | Automation convenience, not a missing protection |
| **Hash auto-generation** (compute and insert a hash for a specific inline script/style by URL, instead of pasting it manually) | Convenience on top of an already-working manual feature |

## How the architecture already supports this

- `EditionManager` (`src/Settings/EditionManager.php`) is the single gate. It
  reads `apply_filters('cno_security/edition', 'free')`. A Pro add-on flips
  this with one `add_filter()` call — Free code never needs to change.
- `ReportsModule::pdfAvailable()` already calls `EditionManager::isPro()` as a
  worked example of the pattern — the Diagnostics page shows the PDF button as
  disabled/"(Pro)" today.
- Every module's `boot()` only wires hooks it owns. A Pro add-on can register
  *additional* modules through the same `ModuleManager::register()` used by
  Free modules, or extend an existing module's behaviour through the same
  WordPress hooks Free already uses (`wp_inline_script_attributes`,
  `script_loader_tag`, `style_loader_tag`, the `cno_security_csp_nonce()`
  helper function), without subclassing or monkey-patching Free classes.
