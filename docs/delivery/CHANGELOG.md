# Changelog — YH Education (GegoK12) delivery

All changes are minimal and maintainable, kept compatible with upstream GegoK12. Grouped by type. Nothing committed/pushed without approval.

## Infrastructure (local dev)
- **Dockerfile** — removed the fragile NodeSource Node.js install from the `app` (php-fpm) image; assets build in the dedicated `node` service.
- **docker-compose.override.yml** (new, untracked) — host MySQL port → 3307 (3306 taken by WSL), phpMyAdmin pinned `5.2.1`, node service pinned `node:22` (node:24 image corrupt on this host).

## Dependency / environment
- `composer install` + generated **composer.lock**; `npm install` + generated **package-lock.json** (reproducible builds).
- Local `.env` created (local, disposable, git-ignored): `APP_ENV=local`, `APP_URL=http://localhost:8090`, DB→`db:3306/school`, `TIMEZONE='Asia/Hong_Kong'`, `MAIL_MAILER=log`, `MAIL_STATUS=off`, `CURRENCY_SYMBOL="HK$"`.

## Bug fixes
- **D-1 Login 500** — login emailed over credential-less SMTP. Fixed via `MAIL_MAILER=log` + `MAIL_STATUS=off` (config only).
- **S-5 / D-2 Student import — parent_qualification** (`app/Imports/UsersImport.php`): resolve each qualification to a single valid id (exact match preferred, else first partial), de-duplicated; blank/unknown values now leave the parent without a qualification instead of building an invalid foreign key. Each row's student+parent creation wrapped in a DB transaction so a parent failure **rolls back the student** — no more silent orphans; failures are logged and the row is reported as skipped. Import format unchanged.
- **S-6 / D-3 Add-on route** (`routes/addon.php`): guarded the Exam add-on routes with `class_exists()` so the missing paid-module controller no longer crashes `route:list` / `route:cache`. Routes activate automatically if the add-on is ever installed. No add-on installed/activated/bypassed.

## Features (approved minor)
- **S-7 Manual WhatsApp button** — `app/Helpers/SiteHelper::whatsappNumber()` (normalise + validate a stored number to a wa.me international number; configurable country code, HK `852` default; avoids double-prefixing `+`/`00`/already-coded numbers; returns null when unusable). Button added to `resources/views/admin/parent/show.blade.php` beside the parent mobile, hidden when no usable number. Opens `https://wa.me/<number>` in a new tab. Manual only — no automated messaging, APIs, templates or scheduling.

## Localisation
- **S-3 Currency** — added `config/app.php` → `currency_symbol` (env `CURRENCY_SYMBOL`, default `₹`) and `whatsapp_country_code` (env `WHATSAPP_COUNTRY_CODE`, default `852`). The 3 payslip views (the only place a currency symbol was hardcoded, `&#8377;`) now use `config('app.currency_symbol')`; set to **HK$** for this install. Add-on marketplace prices (vendor currency) intentionally left unchanged. No financial data modified. (Note: the built-in PHP constant `CURRENCY_SYMBOL` collides and is unusable; config is the correct mechanism.)
- **School profile** name set to **YH Education**.

## Tests added
- `tests/Unit/WhatsappNumberTest.php` — 9 unit tests for phone normalisation (all pass).
- `tests/Regression/import_qualification_regression.php` — re-runnable import regression against the seeded local DB (insert / skip / blank+unknown qualification / duplicate parent / no-orphan). All pass.

## Final audit (2026-10-26)
- **Fixed:** failed-login HTTP 500 — php-fpm (`www-data`) could not create the rate-limiter lock in `storage/framework/cache/data` (root-owned 755). Local fix `chmod -R a+rwX storage/framework bootstrap/cache` added to INSTALLATION.md; the production runbook's `www-data` ownership already prevents it on the server.
- **ARM64 audit:** full compatibility confirmed for Oracle Ampere A1 / Ubuntu 24.04 Minimal aarch64 (no native PHP/npm dependencies); Oracle-specific deployment guide added.
- **Client delivery pack added:** `YH_EDUCATION_100_SCOPE_AUDIT.md`, `YH_EDUCATION_TEST_RESULTS.md`, `YH_EDUCATION_DEPLOYMENT_GUIDE.md`, `YH_EDUCATION_ADMIN_GUIDE.md`, `YH_EDUCATION_PENDING_CLIENT_ITEMS.md`, `YH_EDUCATION_FINAL_DELIVERY_CHECKLIST.md`.

## Documentation
- `docs/delivery/` — PROGRESS, QA_REPORT, SCOPE_REVIEW, CHANGELOG.
