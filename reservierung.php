<?php
/* =============================================================================
   Café Restaurant Villa Rixdorf — secure reservation form (self-processing)
   -----------------------------------------------------------------------------
   Security / DSGVO measures:
     • HTTPS-only secure session cookie (HttpOnly, Secure, SameSite=Strict)
     • CSRF token (random_bytes, compared with hash_equals)
     • Honeypot (checked first, hard stop) + time-trap (refreshed on each render)
     • Session rate-limit (max 8 attempts / hour) against targeted abuse
     • Strict server-side validation of every field
     • E-mail header-injection protection (newlines rejected)
     • Output escaping (htmlspecialchars, ENT_QUOTES) on every echoed value
     • Data minimisation: nothing stored to disk; data is only e-mailed
     • Explicit consent (Art. 6(1)(a)/(b) DSGVO) + link to Datenschutz
   -----------------------------------------------------------------------------
   SET BEFORE GOING LIVE:
     - RESERVATION_RECIPIENT  → your inbox
     - MAIL_FROM / ENVELOPE_FROM → a real address on YOUR domain
     - For reliable delivery use SMTP (PHPMailer). With mail() you MUST have valid
       SPF, DKIM and DMARC DNS records or messages will be junked/rejected.
   ========================================================================== */

declare(strict_types=1);

const RESERVATION_RECIPIENT = 'reservierung@villa-rixdorf.com'; // TODO
const MAIL_FROM             = 'noreply@villa-rixdorf.com';      // TODO (your domain)
const ENVELOPE_FROM         = 'noreply@villa-rixdorf.com';      // TODO (your domain)
const MIN_SECONDS = 3;        // submitted faster than this = bot
const MAX_SECONDS = 7200;     // form older than 2 h = expired
const RATE_MAX    = 8;        // max submit attempts per hour per session

// --- Secure session ---
session_set_cookie_params([
  'lifetime' => 0,
  'path'     => '/',
  'secure'   => true,       // requires HTTPS (forced by .htaccess)
  'httponly' => true,
  'samesite' => 'Strict',
]);
session_start();

if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
// Refresh the time-trap every time the form is SHOWN (fixes false "expired").
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  $_SESSION['form_loaded_at'] = time();
}

$errors  = [];
$success = false;
$old     = ['name' => '', 'email' => '', 'phone' => '', 'date' => '', 'time' => '', 'guests' => '', 'message' => ''];

/** Escape helper for safe HTML output. */
function e(string $v): string {
  return htmlspecialchars($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // 1) Honeypot FIRST. If filled, it's a bot → show generic success, do nothing else.
  if (!empty($_POST['vr_hp_token'])) {
    $success = true;
  } else {

    // 2) Rate limit (per session): drop timestamps older than 1 h, then check.
    $now  = time();
    $hits = array_values(array_filter($_SESSION['rl'] ?? [], static fn($t) => $t > $now - 3600));
    if (count($hits) >= RATE_MAX) {
      $errors[] = 'Zu viele Anfragen in kurzer Zeit. Bitte versuchen Sie es später erneut.';
    }
    $hits[] = $now;
    $_SESSION['rl'] = $hits;

    // 3) CSRF
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
      $errors[] = 'Sicherheitsprüfung fehlgeschlagen. Bitte laden Sie die Seite neu.';
    }

    // 4) Time-trap
    $age = time() - (int) ($_SESSION['form_loaded_at'] ?? 0);
    if ($age < MIN_SECONDS || $age > MAX_SECONDS) {
      $errors[] = 'Das Formular ist abgelaufen. Bitte senden Sie es erneut ab.';
    }

    if (!$errors) {
      // 5) Collect + validate
      $old['name']    = trim((string) ($_POST['name'] ?? ''));
      $old['email']   = trim((string) ($_POST['email'] ?? ''));
      $old['phone']   = trim((string) ($_POST['phone'] ?? ''));
      $old['date']    = trim((string) ($_POST['date'] ?? ''));
      $old['time']    = trim((string) ($_POST['time'] ?? ''));
      $old['guests']  = trim((string) ($_POST['guests'] ?? ''));
      $old['message'] = trim((string) ($_POST['message'] ?? ''));
      $consent        = !empty($_POST['consent']);

      if ($old['name'] === '' || mb_strlen($old['name']) > 80) {
        $errors[] = 'Bitte geben Sie Ihren Namen an (max. 80 Zeichen).';
      }
      if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse an.';
      }
      if ($old['phone'] !== '' && !preg_match('/^[0-9+\/().\s-]{5,30}$/', $old['phone'])) {
        $errors[] = 'Die Telefonnummer enthält ungültige Zeichen.';
      }
      $d = DateTime::createFromFormat('Y-m-d', $old['date']);
      if (!$d || $d->format('Y-m-d') !== $old['date'] || $d < new DateTime('today')) {
        $errors[] = 'Bitte wählen Sie ein gültiges Datum (heute oder später).';
      }
      if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $old['time'])) {
        $errors[] = 'Bitte wählen Sie eine gültige Uhrzeit.';
      }
      $guests = filter_var($old['guests'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 50]]);
      if ($guests === false) {
        $errors[] = 'Bitte geben Sie die Personenzahl an (1–50).';
      }
      if (mb_strlen($old['message']) > 1000) {
        $errors[] = 'Ihre Nachricht ist zu lang (max. 1000 Zeichen).';
      }
      // Reject header-injection attempts in fields used in mail headers
      if (preg_match('/[\r\n]/', $old['name'] . $old['email'])) {
        $errors[] = 'Ungültige Eingabe erkannt.';
      }
      if (!$consent) {
        $errors[] = 'Bitte stimmen Sie der Datenschutzerklärung zu.';
      }

      // 6) Send
      if (!$errors) {
        $to      = RESERVATION_RECIPIENT;
        $subject = '=?UTF-8?B?' . base64_encode('Neue Reservierungsanfrage – Villa Rixdorf') . '?=';
        $bodyText =
          "Neue Reservierungsanfrage über die Website:\n\n" .
          "Name:      {$old['name']}\n" .
          "E-Mail:    {$old['email']}\n" .
          "Telefon:   " . ($old['phone'] !== '' ? $old['phone'] : '–') . "\n" .
          "Datum:     {$old['date']}\n" .
          "Uhrzeit:   {$old['time']} Uhr\n" .
          "Personen:  {$guests}\n" .
          "Nachricht: " . ($old['message'] !== '' ? $old['message'] : '–') . "\n";

        $headers = [
          'From: Website Villa Rixdorf <' . MAIL_FROM . '>',
          'Reply-To: ' . $old['email'],
          'Content-Type: text/plain; charset=UTF-8',
          'MIME-Version: 1.0',
        ];

        // 5th arg sets the envelope sender (Return-Path) for deliverability.
        // Some hosts disable additional_params; remove it if mail() then fails.
        $sent = @mail($to, $subject, $bodyText, implode("\r\n", $headers), '-f' . ENVELOPE_FROM);

        if ($sent) {
          $success = true;
          $_SESSION['csrf'] = bin2hex(random_bytes(32));  // rotate to prevent replay
          $_SESSION['form_loaded_at'] = time();
          $old = array_fill_keys(array_keys($old), '');
        } else {
          $errors[] = 'Die Nachricht konnte nicht gesendet werden. Bitte rufen Sie uns an.';
        }
      }
    }
  }
}

$csrf = $_SESSION['csrf'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reservierung – Café Restaurant Villa Rixdorf</title>
  <meta name="description" content="Reservieren Sie Ihren Tisch im Café Restaurant Villa Rixdorf am Richardplatz 6 in Berlin-Neukölln.">
  <meta name="robots" content="index,follow">
  <meta name="referrer" content="strict-origin-when-cross-origin">
  <meta http-equiv="Content-Security-Policy"
        content="default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'">
  <link rel="icon" href="logo.gif">
  <link rel="stylesheet" href="styles.css?v=2026-06-18">
  <script src="theme.js?v=2026-06-18"></script>
</head>
<body>
  <a class="skip-link" href="#main">Zum Inhalt springen</a>

  <header class="site-header">
    <div class="container site-header__inner">
      <a class="logo" href="index.html" aria-label="Startseite – Café Restaurant Villa Rixdorf">
        <img src="logo.gif" width="220" height="72" alt="Café Restaurant Villa Rixdorf">
      </a>
      <div class="site-header__controls">
        <button class="icon-btn theme-toggle" type="button" aria-pressed="false" aria-label="Zu dunklem Design wechseln">
          <svg class="icon icon-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8z" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <svg class="icon icon-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5L19 19M19 5l-1.5 1.5M6.5 17.5L5 19" stroke-linecap="round"/></svg>
        </button>
        <button class="icon-btn nav-toggle" type="button" aria-expanded="false" aria-controls="primary-nav" aria-label="Menü öffnen">
          <span class="nav-toggle__bars"><span></span></span>
        </button>
      </div>
      <nav id="primary-nav" class="main-nav" aria-label="Hauptnavigation">
        <ul>
          <li><a href="index.html">Startseite</a></li>
          <li><a href="geschichte.html">Geschichtliches</a></li>
          <li><a href="karte.html">Speisekarte</a></li>
          <li><a href="galerie.html">Bilder</a></li>
          <li><a href="reservierung.php" aria-current="page">Reservierung</a></li>
          <li><a href="aktuelles.html">Aktuelles</a></li>
          <li><a href="profil.html">Über uns</a></li>
          <li><a href="impressum.html">Impressum &amp; Datenschutz</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main id="main" class="container">
    <h1>Reservierung</h1>

    <?php if ($success): ?>
      <div class="notice notice--ok" role="status">
        Vielen Dank! Ihre Reservierungsanfrage wurde gesendet. Wir melden uns zur Bestätigung bei Ihnen.
      </div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="notice notice--err" role="alert">
        <strong>Bitte prüfen Sie Ihre Angaben:</strong>
        <ul>
          <?php foreach ($errors as $msg): ?>
            <li><?= e($msg) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="card">
      <p class="prose">Reservieren Sie Ihren Tisch bequem über das Formular. Felder mit <span class="req">*</span> sind erforderlich.</p>

      <form method="post" action="reservierung.php" data-guard>
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <!-- honeypot: hidden from humans (off-screen, not focusable); bots tend to fill it -->
        <div class="hp-field" aria-hidden="true">
          <label for="vr_hp_token">Dieses Feld bitte leer lassen</label>
          <input type="text" id="vr_hp_token" name="vr_hp_token" tabindex="-1" autocomplete="off">
        </div>

        <div class="form-grid form-grid--2">
          <div class="field">
            <label for="name">Name <span class="req">*</span></label>
            <input type="text" id="name" name="name" maxlength="80" required autocomplete="name" value="<?= e($old['name']) ?>">
          </div>
          <div class="field">
            <label for="email">E-Mail <span class="req">*</span></label>
            <input type="email" id="email" name="email" maxlength="120" required autocomplete="email" value="<?= e($old['email']) ?>">
          </div>
          <div class="field">
            <label for="phone">Telefon</label>
            <input type="tel" id="phone" name="phone" maxlength="30" autocomplete="tel" value="<?= e($old['phone']) ?>">
          </div>
          <div class="field">
            <label for="guests">Personen <span class="req">*</span></label>
            <input type="number" id="guests" name="guests" min="1" max="50" required value="<?= e($old['guests']) ?>">
          </div>
          <div class="field">
            <label for="date">Datum <span class="req">*</span></label>
            <input type="date" id="date" name="date" required value="<?= e($old['date']) ?>">
          </div>
          <div class="field">
            <label for="time">Uhrzeit <span class="req">*</span></label>
            <input type="time" id="time" name="time" required value="<?= e($old['time']) ?>">
          </div>
        </div>

        <div class="field" style="margin-top:var(--space-4)">
          <label for="message">Nachricht (optional)</label>
          <textarea id="message" name="message" maxlength="1000"><?= e($old['message']) ?></textarea>
          <span class="field--hint">z. B. Anlass, Sitzwunsch (Terrasse), Allergien</span>
        </div>

        <div class="consent" style="margin-top:var(--space-4)">
          <input type="checkbox" id="consent" name="consent" value="1" required>
          <label for="consent">
            Ich habe die <a href="datenschutz.html">Datenschutzerklärung</a> gelesen und stimme der
            Verarbeitung meiner Daten zur Bearbeitung meiner Reservierungsanfrage zu. <span class="req">*</span>
          </label>
        </div>

        <p style="margin-top:var(--space-4)">
          <button class="btn btn--primary" type="submit">Reservierung senden</button>
        </p>
      </form>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">
      <nav class="footer-nav" aria-label="Fußnavigation">
        <ul>
          <li><a href="index.html">Startseite</a></li>
          <li><a href="geschichte.html">Geschichtliches</a></li>
          <li><a href="karte.html">Speisekarte</a></li>
          <li><a href="galerie.html">Bilder</a></li>
          <li><a href="reservierung.php" aria-current="page">Reservierung</a></li>
          <li><a href="aktuelles.html">Aktuelles</a></li>
          <li><a href="profil.html">Über uns</a></li>
          <li><a href="impressum.html">Impressum</a></li>
          <li><a href="datenschutz.html">Datenschutz</a></li>
        </ul>
      </nav>
      <p class="footer-meta">Café Restaurant Villa Rixdorf · Richardplatz 6 · 12055 Berlin</p>
    </div>
  </footer>
</body>
</html>
