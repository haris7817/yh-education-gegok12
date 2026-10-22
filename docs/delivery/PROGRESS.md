# YH Education (GegoK12) — Progress Log

_Last updated: 2026-10-22_

## Legend
✅ done · 🔄 in progress · ⛔ blocked · ⏳ pending

---

## Phase 1 — Repository audit ✅
- Audited repo (GegoK12 v1.1, MIT core, Laravel 12 / PHP 8.4 / MySQL 8.4).
- Identified: no lockfiles, committed `storage/installed` marker, dev-version constraints, paid add-on marketplace.

## Phase 2 — Local Docker environment ✅
- Docker engine verified (29.3.1), Compose v5.1.1, WSL2 OK.
- Resolved host port 3306 conflict (WSL `wslrelay`) → MySQL on host **3307** via additive `docker-compose.override.yml`.
- Removed fragile NodeSource Node install from `app` Dockerfile (assets build in `node:24` service).
- Pinned phpMyAdmin to `5.2.1` (latest tag crash-looped).
- All 5 containers start; app URL reachable (pre-install PHP fatal = expected before `composer install`).

## UPDATE 2026-10-22 (session 2) — local app is WORKING ✅
AVG HTTPS scanning disabled (host) → network unblocked. Completed: `composer install` (vendor + composer.lock), `APP_KEY`, `npm install` (1125 pkgs, package-lock.json), `migrate` (108 tables), `db:seed` (synthetic data). Fixed login 500 (mail). **Admin, Teacher, Student logins all work; role separation enforced; assets serve.** Full results in [QA_REPORT.md](QA_REPORT.md). Images note: `node:24` layers corrupt on this host → pinned `node:22`.

## UPDATE 2026-10-22 (session 3) — module + import + parent testing
- **Student Excel import tested** (synthetic data): insert ✅, skip-invalid ✅, duplicate-parent linking ✅. Found **D-2** (empty/multi-match `parent_qualification` → FK violation → parent dropped, student orphaned). Full importer format documented in QA_REPORT.
- **Modules:** attendance ✅, student mgmt ✅, classes ✅ (all 200). Timetable/fees render but slow over 330-student demo data (L-2) — retest on target host.
- **School profile** name set to **YH Education**.
- **Parent interface determined:** no web portal (by design) — parents use the **GegoK12 Android app** (`/api/parent/login`, APK `/api/apk/parent`). Parent web login → 404 (L-1).
- Found **D-3**: `routes/addon.php` references missing `AddonInstallExamController` → `route:list`/`route:cache` crash (blocks deploy caching).
- **WhatsApp button:** placement + phone-field analysis done (parent `mobile_no`, no country code stored → needs HK 852 prefix). **Not implemented** — awaiting approval (S-7).
- Open items for approval: S-5 (importer fix), S-6 (addon route), S-7 (WhatsApp), S-3 (currency HK$).

## UPDATE 2026-10-22 (session 4) — approved fixes S-5/6/7/3 DONE
- **S-6** add-on route guarded (`class_exists`) → `route:list`/`route:cache` work. ✅
- **S-5** importer qualification fix + per-row transaction (no orphaned students) → regression test ALL PASSED. ✅
- **S-7** manual WhatsApp button on parent detail (`SiteHelper::whatsappNumber`, config country code, HK 852) → 9 unit tests pass, renders live. ✅
- **S-3** currency now `config('app.currency_symbol')` (env), set **HK$**; payslips wired; marketplace untouched. ✅ (Note: PHP built-in `CURRENCY_SYMBOL` constant collides — used config instead.)
- Regression after all changes: admin/teacher/student logins OK, route:list healthy, unit + import tests green.
- **Android parent app NOT bundled** (`public/android-apk/` missing) — client must obtain separately; backend API present.
- Slow fee/timetable pages = oversized demo data + Windows bind-mount, not a defect — retest on target host.
- **Next: deployment preparation** (Phase H) — awaiting hosting decision.

## Phase A — PHP & Composer ✅ (was blocked on network)
- Verified PHP 8.4.27 + all required extensions in `app` container. ✅
- Composer binary from `composer:2`/`composer:2.8` images arrives **0 bytes / exec-format error** in this Docker Desktop → worked around by installing a host-downloaded `composer.phar` (v2.10.3) into the container. ✅
- ⛔ `composer install` blocked: AVG Antivirus HTTPS interception causes cert-verification failure inside containers (Packagist/GitHub). AVG sends only the leaf cert, not its root, so the container cannot obtain the root from the handshake. **See blocker B-1.**

## Phase B — Laravel environment 🔄
- Created local `.env` (YH Education, `APP_ENV=local`, `APP_URL=http://localhost:8090`, DB→`db:3306`/`school`/root). ✅
- Used correct timezone key: `TIMEZONE=Asia/Hong_Kong` (verified `config/app.php:68` reads `env('TIMEZONE')`, **not** `APP_TIMEZONE`). ✅
- `.env` confirmed git-ignored. ✅
- ⏳ `php artisan key:generate`, bootstrap verification — pending `composer install` (needs vendor/).

## Phase C — Frontend ⛔ (blocked on network)
- ⛔ `npm install` blocked by the same AVG interception (B-1). Build script confirmed: `npm run production` (laravel-mix). Not yet run.

## Phase D — Database initialization ⏳ (gated — needs approval)
- Not started. Will verify empty/local DB first and **request approval** before migrate/seed per instructions.

## Phases E–J ⏳
- Pending the above.

---

## Blockers

### B-1 — AVG Antivirus HTTPS interception blocks container package downloads ⛔
- **Impact:** `composer install` and `npm install` cannot complete inside containers (TLS cert verification fails; AVG re-signs every HTTPS cert with `AVG Web/Mail Shield Root`, which containers don't trust).
- **Not fixable from inside the container:** AVG does not send its root CA in the TLS handshake, and exporting it from the Windows trust store was blocked by the safety classifier. Disabling cert validation is explicitly out of scope.
- **Decision needed from client/owner** (one-time, reversible host action). Options in the milestone report.

---

## Files changed so far
- `Dockerfile` — removed NodeSource Node install (infra).
- `docker-compose.override.yml` — new (db port 3307, phpMyAdmin 5.2.1).
- `.env` — new, local only, git-ignored.
- `docs/delivery/*` — documentation (this folder).
