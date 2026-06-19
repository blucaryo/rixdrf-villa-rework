# Café Restaurant Villa Rixdorf – Website-Relaunch

Moderner, sicherer und DSGVO-konformer Relaunch der Website des **Café Restaurant
Villa Rixdorf** (Richardplatz 6, Berlin-Neukölln). Gleiche Optik wie das Original,
aber neu aufgebaut: verschlüsselt, responsiv, barrierearm und mit hellem/dunklem Design.

🔗 **Live-Demo:** `https://DEIN-NUTZERNAME.github.io/villa-rixdorf-relaunch/`

> **Hinweis:** GitHub Pages zeigt nur die statische Ansicht. Das sichere PHP-Formular
> und die Server-Schutzmaßnahmen (`.htaccess`) sind erst auf echtem PHP-/Apache-Hosting
> aktiv – siehe Abschnitt „Demo vs. Live".

---

## Features

### 🔒 Sicherheit & DSGVO
- HTTPS-Erzwingung (proxy-sicher) und Security-Header: CSP, HSTS, X-Frame-Options,
  X-Content-Type-Options, Referrer-Policy, Permissions-Policy
- Sicheres Reservierungsformular: serverseitige Validierung, Spam-Schutz (Honeypot,
  Rate-Limit, Time-Trap), Schutz vor E-Mail-Header-Injection, Ausgabe-Escaping,
  Datensparsamkeit (keine Speicherung auf der Platte)
- Keine externen Schriftarten, kein Tracking, keine eingebetteten Karten
  → **kein Cookie-Banner nötig**
- Datenschutzerklärung & Impressum als Vorlagen (rechtlich prüfen lassen)

### 🎨 Design & Bedienung
- Helles **und** dunkles Design – folgt dem System, manuell umschaltbar, ohne Flackern
- Vollständig responsiv (Mobile-first; Hamburger-Menü am Handy, Leiste am Desktop)
- Barrierearm: Tastaturbedienung, sichtbarer Fokus, ARIA-Attribute, Alt-Texte,
  Rücksicht auf `prefers-reduced-motion`
- Gleiche Farbpalette wie das Original, zentral über CSS-Variablen anpassbar

### ⚡ Performance
- Statisches HTML, nahezu kein JavaScript
- Lazy-Loading der Bilder, Kompression (gzip/brotli) und Caching über `.htaccess`

---

## Tech-Stack
- **HTML5**, **CSS3** (Custom Properties, Grid/Flexbox), **Vanilla JavaScript** (keine Frameworks)
- **PHP** – ausschließlich für die Verarbeitung des Reservierungsformulars
- **Apache** (`.htaccess`) für Sicherheit, Kompression und Caching

---

## Projektstruktur
```
.
├── .htaccess                 # HTTPS + Security-Header (nur Apache/Live)
├── index.html                # Startseite
├── assets/
│   ├── css/styles.css        # Design-System (hell + dunkel, responsiv)
│   └── js/theme.js           # Theme-Umschalter + Mobile-Menü + Absende-Schutz
└── villa/
    ├── geschichte.html       # Geschichtliches
    ├── reservierung.html     # Reservierung – statische Demo (sendet nicht)
    ├── reservierung.php      # Reservierung – sichere Live-Version (PHP)
    ├── datenschutz.html      # Datenschutzerklärung (Vorlage)
    ├── impressum.html        # Impressum (Vorlage)
    └── _template.html        # Vorlage für weitere Seiten (Karte, Bilder, …)
```

---

## Demo vs. Live
| | GitHub Pages (Demo) | Echtes Hosting (Live) |
|---|---|---|
| `.htaccess` | wird ignoriert | aktiv (HTTPS, Header, Caching) |
| Reservierung | `reservierung.html` (statisch) | `reservierung.php` (sicher) |
| Formular sendet | nein | ja |

**Beim Live-Gang:** TLS-Zertifikat aktivieren (Let's Encrypt), `.htaccess` in den
Webroot, und das Menü von `reservierung.html` auf `reservierung.php` umstellen.
Details stehen im `SECURITY-AND-UPGRADE-GUIDE.md`.

---

## Offene Punkte / Status
- [ ] Bilder zu **WebP/AVIF** konvertieren; Logo als **SVG/PNG**
- [ ] Platzhalter ausfüllen: Telefon, E-Mail, Öffnungszeiten, Empfänger-Adresse
- [ ] Datenschutz & Impressum **rechtlich prüfen** lassen
- [ ] Exakte **Markenfarben** in `styles.css` eintragen (6 CSS-Variablen)
- [ ] **SPF/DKIM/DMARC** einrichten bzw. SMTP für zuverlässigen Mailversand

---

## Autor
**[Dein Name]** — Webentwicklung · [E-Mail / Kontakt]

## Lizenz
Kundenprojekt / Demonstration. Alle Rechte vorbehalten, sofern nicht anders angegeben.
