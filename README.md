# Café Restaurant Villa Rixdorf — Website Relaunch

A modern relaunch of a Berlin restaurant's
website (est. 1870, Richardplatz, Berlin-Neukölln) VILLA RIXDORF.

**🔗 Live:** https://blucaryo.github.io/rixdrf-villa-rework/

---

## Highlights
-  **Security & GDPR** — forced HTTPS, security headers (CSP, HSTS, X-Frame-Options),
  hardened reservation form (CSRF, spam protection, server-side validation), no
  third-party tracking, no cookie banner needed
-  **Light & dark mode** — faithful to the original colour palette
-  **Fully responsive** — mobile-first, scales cleanly from phone to desktop
-  **Accessible** — keyboard navigation, ARIA labels, sufficient contrast
-  **Fast & lightweight** — static HTML, minimal JavaScript, no frameworks

## Tech stack
HTML5 · CSS3 (custom properties, Grid/Flexbox) · Vanilla JavaScript · PHP (reservation
form) · Apache (`.htaccess`)

## Project status
Preview / demo build. The security features and the PHP reservation form are active on
real PHP/Apache hosting only — **not** on GitHub Pages (which serves static files and
cannot run PHP). See `SECURITY-AND-UPGRADE-GUIDE.md` for production deployment.

---

*Built by Blucaryo — Web Development.*
