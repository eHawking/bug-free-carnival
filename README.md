# Hammer of Night Rider (HONR)

A responsive, static landing page with a local, error-free Checkout (Cash on Delivery only). Built for quick deployment on any static host.

## Highlights
- Brand gold: `#D4AF37` across CTAs and accents (see `css/theme-overrides.css`).
- Pricing cards, reviews grid, and responsive layout.
- Local Checkout pages under `checkout/`:
  - `cod.html` – COD-only checkout (no external services).
  - `thank-you.html` – Order confirmation (reads `sessionStorage`).
  - Legacy compiled `index.html` kept for reference (Next.js build from a vendor), not used by default.

## How to run locally
- Open `index.html` in a browser.
- Use any "Add to Cart" button. It routes to `checkout/cod.html?sku=EPK..&price=...`.
- Fill the form and place order. You’ll be redirected to `checkout/thank-you.html` with a local order summary.

## Structure
- `index.html` – main landing page.
- `css/theme-overrides.css` – brand variables and page-specific tweaks.
- `images/` – product and UI assets.
- `checkout/` – standalone, local checkout experience.

## Development notes
- `.gitignore` ignores backups (`*.bak`) and common junk files.
- External tracking/processor links have been removed or replaced in the main flow.

## License
Copyright © 2025. All rights reserved.
