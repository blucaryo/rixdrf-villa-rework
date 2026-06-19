# Villa Rixdorf — Security + DSGVO + Design Rework (v2)

Complete, reviewed package. Work top to bottom; the order is by priority.

> **Two things I can't do from here:** deploy to your server, and read your exact
> source-CSS colours. The palette lives in CSS variables you swap in (Step 6);
> Steps 1–7 are what you or your host run.

## What changed in this version (fixes from the code review)
- **Time-trap** now refreshes every time the form is shown → no more false
  "Formular abgelaufen" for returning visitors.
- **Honeypot** is checked first as a hard stop and renamed to a non-semantic field
  → browser/password-manager autofill can no longer silently drop real bookings.
- **Session rate-limit** added (max 8 attempts/hour) against targeted form abuse.
- **HTTPS redirect** is now proxy-aware → no redirect loop behind Cloudflare etc.
- **HSTS** starts conservative (180 days, no `includeSubDomains`) with an upgrade path.
- **E-mail envelope sender** (`-f`) set for deliverability; SPF/DKIM/DMARC note added.
- **Native form validation** restored (better UX) + **double-submit guard** in JS.
- **Cache-busting** (`?v=…`) on the shared CSS/JS; `text/javascript` covered in Apache.
- **Charset** warning added; **`color-mix()` fallbacks** added for older browsers;
  the no-op `frame-ancestors` removed from the `<meta>` CSP (kept in the header).

---

## File structure (upload to your web root, keeping this layout)

```
/.htaccess                      ← HTTPS + security headers (Apache)
/index.html                     ← homepage (replaces the ENTER splash)
/assets/css/styles.css          ← design system (light + dark, responsive)
/assets/js/theme.js             ← light/dark toggle + mobile menu + submit guard
/villa/geschichte.html          ← rebuilt on the shared system
/villa/reservierung.php         ← SECURE reservation form (was .html)
/villa/datenschutz.html         ← DSGVO privacy policy (TEMPLATE — fill + legal check)
/villa/impressum.html           ← Impressum (TEMPLATE — fill + legal check)
/villa/_template.html           ← copy this for karte / galerie / aktuelles / profil
```

Your existing `/images/` folder stays as is.

---

# STEP 1 — Security & DSGVO

### 1. HTTPS / TLS (first)
The site runs on `http://` with no encryption. Activate a free **Let's Encrypt**
certificate, then upload **`.htaccess`** — it forces `https://` (proxy-aware, so no
redirect loop behind a CDN) and sends the security headers.
Test: https://www.ssllabs.com/ssltest/ and https://securityheaders.com

### 2. Security headers & hardening (`.htaccess`)
HSTS (conservative to start), `X-Content-Type-Options`, `X-Frame-Options`,
`Referrer-Policy`, `Permissions-Policy`, a `Content-Security-Policy` (strict scripts),
PHP/Apache version hidden, directory listing off, hidden/backup files blocked,
UTF-8, compression, caching.
**HSTS upgrade path:** once *every* subdomain is HTTPS, raise the header to
`max-age=31536000; includeSubDomains` (and only then consider `; preload`).

### 3. Secure reservation form (`reservierung.php`)
Built in: **CSRF** (`hash_equals`), **honeypot first** + **time-trap** (refreshed each
render), **session rate-limit**, strict **server-side validation**, **e-mail
header-injection** protection, **output escaping**, **data minimisation** (nothing
written to disk), and a **consent checkbox** (Art. 6(1)(a)/(b) DSGVO).
**To set:** `RESERVATION_RECIPIENT`, `MAIL_FROM`, `ENVELOPE_FROM` (all on your domain).
Needs **PHP hosting**. With `mail()` you **must** have valid **SPF, DKIM and DMARC**
DNS records or messages get junked/rejected — switching to **SMTP via PHPMailer** is
the robust option. (On a static demo host with no PHP, use Netlify Forms / Formspree.)

### 4. Privacy policy + Impressum (legally required)
`datenschutz.html` and `impressum.html` are complete **templates**. Fill every
`[Platzhalter]` and have them reviewed — real fining risk, and I'm not a lawyer.
Reputable generators: e-recht24.de, datenschutz-generator.de.

### 5. DSGVO wins already built in
No external fonts, no Google Analytics, no embedded Google Maps (map is an outbound
link) → no third-party data transfer, so **no cookie banner needed**. The only browser
storage is the light/dark choice (functional). **Action:** sign a Data Processing
Agreement (AV-Vertrag) with your host.

---

# STEP 2 — Same-palette rework, desktop + mobile, light/dark

All visual work lives in **`assets/css/styles.css`**, shared by every page, so the
look and behaviour are identical everywhere ("jeder Unterpunkt").

- **Light mode = your existing palette.** Dark mode reuses the **same hues** (Bordeaux
  + gold) on warm dark surfaces — not a generic grey theme.
- **Theme toggle** follows the OS by default, remembers a manual choice, no flash.
- **Responsive:** mobile-first; the menu collapses into an accessible hamburger (with
  focus management) on phones, horizontal bar on desktop. Fluid type, responsive image
  grids, 44px touch targets.
- **Detail pass:** visible keyboard focus, `prefers-reduced-motion`, print stylesheet,
  sticky header, CSS divider (no more `linie.gif`), `color-mix()` fallbacks.

### Step 6 — drop in your exact colours
Open `assets/css/styles.css`, find `:root { … }`, and replace the six values under the
"LIGHT palette" comment (`--color-bg`, `--color-surface`, `--color-text`,
`--color-heading`, `--color-accent`, `--color-line`). To read your real values: open
the live site → right-click a coloured area → **Inspect** → copy the hex. Dark-mode
values sit just below. (Send me the stylesheet and I'll set them exactly.)

---

## STEP 7 — Deploy checklist
1. **Back up** the current site (download everything).
2. Activate **HTTPS** (Let's Encrypt).
3. Upload **`.htaccess`**, load over `https://`, click around. *(HSTS is conservative,
   so this is safe; raise it later per the upgrade path.)*
4. Upload `assets/`, `index.html`, and the `villa/` files.
5. **Convert any legacy pages you keep to UTF-8** before relying on `AddDefaultCharset`
   (the old site had Windows-1252 artefacts): `iconv -f WINDOWS-1252 -t UTF-8 …`.
6. **Match your palette** (Step 6).
7. Fill the **TODO/[Platzhalter]**: form recipient + From/envelope; phone, e-mail and
   opening hours on the homepage and in the structured data; the legal pages.
8. Replace `logo.gif` with an **SVG/PNG**; consider **WebP** for the photos.
9. **When you later edit `styles.css`/`theme.js`, bump the `?v=` in the HTML** so
   visitors get the new file immediately.
10. Re-test on securityheaders.com and on your phone (light *and* dark).
11. Add a `sitemap.xml` and submit it in Google Search Console.

### One detail to watch
If your logo is a dark wordmark on transparency, it may look faint in dark mode.
Export a light version for dark mode, or use an adaptive SVG.

---

## To finish the site, send me:
- Your **CSS file** → exact colour/font match.
- The current **guestbook** code → I'll secure it or advise retiring it (old guestbooks
  are a common stored-XSS/spam target; it's intentionally left out of the new nav).
- Content for **Speisekarte, Bilder, Aktuelles, Über uns** → I'll fill the template.
