# Hasan Journal — PHP/MySQL edition

A from-scratch PHP 8.1+ / MySQL port of the Next.js + Payload application in
the repository root, built specifically to deploy on ordinary shared hosting:
no Composer install step, no CLI/SSH requirement, no Node.js. Same content
model, same design, same security properties (draft isolation, guarded
media delivery, CSRF, signed preview links) — reimplemented directly in
plain PHP with a web-based installer.

**This is a genuinely separate implementation**, not a build output of the
Next.js app. The two live side by side in this repository; neither depends
on the other.

## Requirements

- PHP 8.1 or newer, with `pdo_mysql`, `gd`, `fileinfo`, `mbstring`, `openssl`
  (all of these are enabled by default on essentially every shared host)
- MySQL 5.7+ or MariaDB 10.3+
- Apache with `mod_rewrite` (LiteSpeed with Apache-compatible `.htaccess`
  support also works — this is the default on cPanel hosting)

No shell access is required. Everything here is verified against **real**
Apache + mod_rewrite in a container (`Dockerfile.apache-test`), not just
PHP's built-in dev server — the built-in server does not exercise
`.htaccess` at all, which was worth spelling out because it is an easy thing
to test past accidentally.

## Deploying to shared hosting

1. **Create a MySQL database and user** in your host's control panel
   (cPanel: MySQL Databases). Note the database name, username, password,
   and host (usually `localhost`).

2. **Upload the contents of this `php/` directory.** Two supported layouts:

   **A. Your host lets you point the domain's document root at a
   subfolder** (most cPanel hosts do, via "Domains" → document root, or by
   using an addon domain). This is the setup verified above:
   - Upload the whole `php/` folder content anywhere under your home
     directory, e.g. `~/hasan-journal/`.
   - Point the domain's document root at `~/hasan-journal/public`.
   - `app/`, `storage/` and `migrations/` stay outside the web root
     entirely — the most secure arrangement, and the one this project is
     built around.

   **B. Your host only lets you use `public_html/` as the document root**
   (some very basic/free hosting):
   - Copy the *contents* of `public/` (not the folder itself) directly into
     `public_html/`.
   - Upload `app/`, `storage/` and `migrations/` as siblings of
     `public_html/` in your home directory (e.g. `~/app/`, `~/storage/`,
     `~/migrations/`) — cPanel homes almost always allow this even when the
     domain itself is locked to `public_html/`.
   - Nothing else changes: every path in the code is computed relative to
     the actual file location (`dirname(__DIR__)` etc.), not hardcoded, so
     this layout works without editing any PHP.
   - `app/.htaccess` and `storage/.htaccess` are defense-in-depth for the
     case where a document root *does* end up including them by mistake —
     the real protection is that they are outside the document root at all.

3. **Make `app/` and `storage/uploads/` writable** temporarily (FTP client:
   set permissions to 755, or 775 if 755 doesn't work on your specific
   host). The installer writes `app/config.php` and stores uploaded media
   under `storage/uploads/`. You can tighten `app/` back down after
   installation if you want; `storage/uploads/` must stay writable.

4. **Visit `https://yourdomain.com/install.php`** and follow the wizard:
   requirements check → database connection (also creates the schema) →
   site URL + owner account. This is the *only* way an owner account is
   ever created — there is no other signup path, and the installer refuses
   to run a second time once `app/config.php` exists.

5. **Delete or rename `install.php`** once done, for your own peace of mind
   (the app already refuses to run it again, but one less thing to think
   about — see the note the wizard itself shows on the last step).

6. Log in at `/admin/login` and start writing.

### HTTPS

Use `https://` for the site URL during installation. The app allows `http://`
only for a genuinely local/loopback install (`localhost`/`127.0.0.1`); any
other hostname must be `https://`. Most hosts provide a free TLS certificate
(Let's Encrypt/AutoSSL) — enable it before running the installer if possible,
since changing the site URL afterward means editing `app/config.php` by hand.

## Local development

Testing this against a real MySQL without touching a shared host:

```bash
docker compose -f compose.dev.yaml up -d --wait   # local MariaDB on 127.0.0.1:3307
php -S 127.0.0.1:8090 public/router-dev.php       # public/router-dev.php only exists
                                                    # to emulate .htaccess for `php -S`;
                                                    # it is never used in production
```

Then visit `http://127.0.0.1:8090/install.php`.

To verify the *real* `.htaccess`/mod_rewrite path (recommended before any
real deployment, and how this app was actually verified):

```bash
docker build -f Dockerfile.apache-test -t hasan-journal-apache-test .
docker run -d --name apache-test --network hasan-journal-php-dev_default \
  -p 127.0.0.1:8091:80 hasan-journal-apache-test
```

## Content model

Same as the root project's `docs/CONTENT_MODEL.md`, mapped onto MySQL
tables (`migrations/001_initial.sql`): posts, projects, media (+ generated
`thumbnail`/`medium`/`large` variants via GD), categories, tags, redirects,
site settings, a single owner user.

**AVIF is not supported** in this edition (JPEG/PNG/WebP only) — GD's AVIF
support depends on the host's libavif build, which is inconsistent enough
across real shared-hosting PHP builds that it isn't safe to rely on for a
"just works everywhere" deployment target.

### Draft/publish model

A post or project row always holds the *live* (currently public) state.
Editing an already-published document and choosing **Save draft** stages the
change in a `pending_draft_json` column instead of touching the live
columns — visitors keep seeing the previously published version until you
click **Publish**, which merges the staged change in. **Unpublish** is a
separate, explicit action that flips the live row immediately; it is not the
same as "save as draft" on a published document.

Every save snapshots the previous state into a `*_versions` table; **Restore
as draft** always writes into `pending_draft_json`, never directly over the
live row, so restoring history can never silently replace what visitors see.

### Media privacy

Uploaded files are stored in `storage/uploads/`, *outside* the public web
root. The only way to fetch one is `/media/{filename}`
(`Controllers/Public/MediaController`), which checks the asset's
`visibility` column (and the owner session, for private assets) on every
request — never assume a webserver path is protection on its own. Delivery
is always `Cache-Control: private, no-store`, so flipping an asset back to
private takes effect immediately for new requests (a copy already cached by
a visitor's browser is a separate, unavoidable limitation, same as the
Next.js edition).

### Rich text

A small dependency-free contenteditable-based editor (`admin-editor.js`) —
no vendored third-party editor, no build step. Output is sanitized again on
the server (`App\Security\Sanitizer`, an allowlist walk over `DOMDocument`)
regardless of what the browser produced, rejecting `javascript:`/`data:`
link and image schemes and stripping `<script>`/`<style>`/`<iframe>` and
similar entirely.

## What's different from the Next.js edition

- **Auth**: PHP native sessions instead of JWT; a `preview_mode` session
  flag instead of Next's draft-mode cookie — same effective isolation
  (owner session required, explicit enter/exit, signed short-lived token),
  simpler mechanism for a traditional server-rendered app.
- **Motion**: relies on the CSS `@view-transition` cross-document API
  (already in the shared stylesheet) instead of a client-side router — a
  full-page-navigation PHP app gets this "for free" in supporting browsers,
  with no JavaScript required, and reduced-motion is still respected via the
  same CSS media query.
- **No automated test suite ships with this edition** (the Next.js edition
  has one; porting Vitest/Playwright-equivalent coverage to a PHP+MySQL
  stack was out of scope for this pass). Verification here was done by
  exercising the running app directly against real MariaDB and real Apache
  — see the command transcript in the project handoff notes for exactly
  what was checked.

## Known limitations

- No automated backup/restore scripts (unlike the Next.js edition's
  `pnpm backup`/`pnpm restore:check`) — shared hosts almost universally
  provide their own MySQL and file backup tools (cPanel Backup Wizard,
  cron + `mysqldump`), and duplicating that here didn't fit the
  "PHP/MySQL, no shell required" brief. Whatever backup mechanism your host
  offers, back up both the database *and* `storage/uploads/`.
- No CI workflow for this edition yet.
- SMTP send path is a small hand-written client (no vendored PHPMailer);
  it supports STARTTLS/SSL + AUTH LOGIN, which covers the common
  providers, but has not been tested against every SMTP server type.
