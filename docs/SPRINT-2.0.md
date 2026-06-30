# Sprint 2.0 (alpha1)

## Goal

Turn the installable-but-thin alpha2 foundation into a milestone where every
module actually does what its name says, in proper PSR-12, still PHP 7.0
compatible.

## Included

- Foundation rewritten to PSR-12; `ApplicationInterface`/`ContainerInterface`
  removed (single implementation, no interface needed); dead
  `PlaceholderModule` removed
- HTTPS: HSTS (+ preload), force SSL, reverse proxy / Cloudflare-aware HTTPS
  detection, on top of the existing redirect
- Security Headers: every header from the spec, individually configurable
- CSP: full directive builder, nonce support (script + style), manual hash
  allowlisting, learning mode with a REST violation-report endpoint and a
  one-click "add to policy" UI
- security.txt: full RFC 9116 field editor with a live preview of the
  published path
- Scanner: a real local-only readiness scan (12 checks), replacing the
  placeholder
- Diagnostics: new read-only environment module
- Reports: new module — on-screen summary, CSV/JSON export; PDF gated to Pro
- `EditionManager` + `docs/FREE-PRO.md`: the requested Free/Pro split proposal
  and the gate that backs it
- Every module setting now saves immediately via AJAX (no Save button),
  matching the existing module-toggle behaviour
- `docs/ARCHITECTURE.md`, `docs/ROADMAP.md` added

## Not included (see `docs/ROADMAP.md`)

- Automated tests
- Elementor/WooCommerce/Cookiebot/Google Fonts/Maps/YouTube/Vimeo CSP
  compatibility profiles
- Online internet.nl / sikkerpånettet.dk scoring
- PDF report generation (Pro, stubbed)

## Suggested commit

```text
Build out HTTPS, Headers, CSP, security.txt, Scanner, Diagnostics and Reports modules

- Rewrite foundation in PSR-12; drop single-implementation interfaces
- HTTPS: HSTS, preload, force SSL, reverse proxy / Cloudflare awareness
- Headers: full per-header configuration
- CSP: directive builder, nonce + hash support, learning mode with REST
  violation reporting and one-click policy suggestions
- security.txt: full RFC 9116 editor
- New Scanner (real local checks), Diagnostics and Reports modules
- Add EditionManager for the proposed Free/Pro split
- All module settings now save via AJAX, no Save button
```
