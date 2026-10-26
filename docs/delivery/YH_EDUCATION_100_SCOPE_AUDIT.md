# YH Education — $100 Agreement Scope Audit

_Audit date: 2026-10-26 · Branch `yh-education-delivery` (8 commits over upstream `main`) · All statuses below were verified against the **running application**, not source inspection alone. Test evidence: `YH_EDUCATION_TEST_RESULTS.md`._

Legend: ✅ complete & verified · ⚠️ complete with a documented caveat · ⛔ blocked on client input · ❌ out of scope (not implemented, by agreement)

---

## A. GegoK12 installation & configuration — ✅

| Check | Status | Evidence |
|---|---|---|
| GegoK12 installed & configured | ✅ | Docker stack (php-fpm 8.4.27 / nginx / MySQL 8.4 / node 22); app at `http://localhost:8090` |
| Laravel starts without errors | ✅ | Laravel 12.70.1 boots; `/login` 200, `/` 302→login (verified 2026-10-26) |
| Migrations work | ✅ | 108 migrations run clean (`database/migrations/`); re-verified on a throwaway DB |
| Required configuration present | ✅ | `.env` (local, git-ignored); `docs/delivery/env.production.example` for production |
| Dependencies compatible | ✅ | `composer.lock` + `package-lock.json` now versioned (upstream shipped none — a root cause of the client's original install failures) |
| No demo data in production setup | ✅ | **Verified reference-only seeding procedure** (`DEPLOYMENT.md` §3.5): tested on a throwaway DB → 0 demo students/teachers/parents. ⚠️ Never run plain `php artisan db:seed` in production — `UsersStudentTableSeeder` is not environment-guarded and would insert ~330 demo students (`database/seeders/DatabaseSeeder.php`) |
| Deployable on Ubuntu 24.04 ARM64 | ✅ (analysis) ⛔ (execution) | Full ARM64 compatibility verified by static analysis — see §ARM64 below. Actual deployment blocked on SSH access (client item) |
| Upstream functionality preserved | ✅ | Changes are minimal & additive: importer fix, add-on route guard, validation relaxation, WhatsApp button, currency config, slogan display. Diff: `git diff main..yh-education-delivery` |

**Install defects found & fixed during the engagement** (all were blockers in the client's original attempts):
1. Committed `storage/installed` marker locked out the installer (root cause of "installer returns HTML not JSON").
2. No lockfiles + `dev-master`/alpha constraints → non-reproducible installs (lockfiles now versioned).
3. `routes/addon.php` referenced a missing paid-module controller → `route:list`/`route:cache` crashed (now guarded with `class_exists`, `routes/addon.php`).
4. Login returned HTTP 500 (login-notification email over credential-less SMTP) → `MAIL_MAILER=log`/`MAIL_STATUS=off` locally; real SMTP needed in production.
5. **(This audit)** Failed-login attempts returned HTTP 500: php-fpm (`www-data`) could not write the rate-limiter lock in `storage/framework/cache/data` (root-owned 755). Fixed (`chmod -R a+rwX storage/framework bootstrap/cache`); added to `INSTALLATION.md`. Production runbook already sets correct ownership.

## B. User roles & authentication — ✅

| Check | Status | Evidence (live, 2026-10-26) |
|---|---|---|
| Authentication works | ✅ | Admin/teacher/student logins → 302 → correct dashboards |
| Required roles exist | ✅ | Usergroups: SchoolAdmin, Teacher, Student, Parent (+ Librarian, Receptionist, Accountant, Stock Keeper); laratrust sub-roles seeded |
| Role-based access enforced | ✅ | Teacher & student blocked from `/admin/dashboard` (redirected to own dashboard); each role's own dashboard 200 |
| Wrong password rejected safely | ✅ | 302 back to `/login` with error message (fixed this audit — was 500, see A.5) |
| Passwords securely hashed | ✅ | bcrypt `$2y$`, 60 chars (`users.password`); hashing via `bcrypt()` in `app/Traits/RegisterUser.php` |
| Password reset | ⚠️ | `/password/reset` renders (200); **email delivery requires production SMTP** (off locally by design) |
| Logout | ✅ | POST `/logout` → 302 → `/`; subsequent dashboard access redirects to login |
| Students/Parents access | ⚠️ | Student web dashboard ✅. **Parents authenticate but have no web portal by GegoK12 design** — parents use the vendor's mobile app (API present: `POST /api/parent/login`; the app binary is **not bundled** in the open-source core). Documented in `KNOWN_LIMITATIONS.md` §1 |

## C. YH Education branding — ✅ (logo pending client asset)

| Check | Status | Evidence |
|---|---|---|
| School name displays | ✅ | "YH Education" — login `<title>`, school profile, `APP_NAME`, `schools.name` |
| Consistent branding | ✅ | App name + school record set; address (EN + 中文), phone `+852 54001530`, email `yh.eduhk@gmail.com` on the school profile |
| Logo configurable later | ✅ mechanism / ⛔ asset | Built-in uploads: school profile `school_logo` + site `sitelogo`/`favicon`. **No logo invented** — awaiting client asset |
| Slogan configurable | ✅ | Bilingual slogans in the `moto` field (validation raised 50→150, `app/Http/Requests/DetailRequest.php`); displayed on the login page (`resources/views/auth/login.blade.php`); verified rendering: "Nurturing Youthful Minds, Embracing Holistic Growth" + "言傳身教 希冀成材" |
| Demo branding removed | ⚠️ | Client-facing name/profile is YH Education. The login-page image is upstream's `gego_k12.png` placeholder — replaced when the client's logo is supplied (one upload). Demo accounts exist in the **dev** DB only; production uses the clean reference-only seed |

## D. Student management — ✅

| Check | Status | Evidence (live) |
|---|---|---|
| Create students | ✅ | `/admin/student/add` 200; `StudentController@store` → `CreateUser` with class assignment; create path exercised by the import regression (students persist with class + parent links) |
| Edit students | ✅ | `/admin/student/edit/{name}` 200 |
| View records | ✅ | `/admin/students` 200 (list); `/admin/student/show/{name}` 200 (detail) |
| Persistence | ✅ | Imported/created students persist across restarts (verified during Phases F/M) |
| Teacher management (related) | ✅ | `/admin/teachers` 200, `/admin/teacher/add` 200 |

## E. Excel student import — ✅ (existing feature, repaired & tested)

- Built-in importer exists: `app/Imports/UsersImport.php` (Maatwebsite/Excel), sample template via `app/Exports/StudentFormatExport.php` (50-column heading row).
- **Verified by repeatable regression test** (`tests/Regression/import_qualification_regression.php` — ALL PASSED, re-run 2026-10-26): inserts students with class assignment; **duplicate parents linked by `parent_mobile_no`, not duplicated**; invalid class/section rows **skipped and reported**; blank/unknown `parent_qualification` handled safely.
- **Defect found & fixed in scope:** upstream silently dropped the parent (and orphaned the student) when `parent_qualification` was blank or matched multiple entries. Fixed in `app/Imports/UsersImport.php`: single-match resolution + per-row transaction → **no orphaned students possible**.
- Required columns/validation documented in `YH_EDUCATION_ADMIN_GUIDE.md` §6. Real student data **not** imported (awaiting client data + data-protection confirmation).

## F. Existing attendance — ✅ pages verified / ⚠️ save flow to manual UAT

- `/admin/attendance/list` 200 and `/admin/attendance/add` 200 (admin); attendance is teacher-role functionality too (teacher dashboard 200).
- Viewing attendance records: list page renders with the seeded data.
- **Marking/saving attendance end-to-end was not POST-tested** (the marking UI is a Vue flow; automating its payload was out of proportion) — listed as a 10-minute manual UAT step in `YH_EDUCATION_PENDING_CLIENT_ITEMS.md`.
- ❌ Prepaid lesson credits / auto credit deduction / excused-leave rules / renewal alerts: **not implemented** (excluded from the $100 agreement, confirmed absent from scope work).

## G. Existing fee management — ✅ accessible / ⚠️ performance note

- Fee functionality present and wired: dashboard fee widgets render (verified live); fee routes exist (`/admin/dashboard/feeslist/{id}`, per-class `/admin/standardLink/show/fees/{id}`, per-student `/admin/student/show/fees/{name}`, `/admin/report/fees`).
- Permissions follow the admin/accountant middleware (role separation verified).
- ⚠️ Heavy fee/timetable report pages exceed 30 s over the oversized **demo** dataset (330 students) on the Windows dev bind-mount — a data-volume/dev-environment effect; re-time on the production server with the real ~60 students (`KNOWN_LIMITATIONS.md` §2).
- Nothing broken by our changes; payslips now use the configurable **HK$** symbol (`config('app.currency_symbol')`).
- ❌ Split ledgers / custom invoicing / new billing workflows: not implemented (excluded).

## H. WhatsApp contact button — ✅

- Implemented: manual click-to-chat on the **admin parent-detail page** next to the parent's mobile (`resources/views/admin/parent/show.blade.php`), opening `https://wa.me/<number>` in a new tab. Verified live: `wa.me/852…` + "Chat on WhatsApp" render.
- Number source: each **parent's stored mobile**; dialling code configurable via `.env` `WHATSAPP_COUNTRY_CODE` (default **852**, documented placeholder) → `config('app.whatsapp_country_code')`. Normalisation in `App\Helpers\SiteHelper::whatsappNumber()` (won't double-prefix `+`/`00`/already-coded numbers; hides the button when no usable number) — covered by 9 unit tests.
- Desktop/mobile: a standard `wa.me` link — WhatsApp Web on desktop, the app on mobile. Markup is responsive.
- ⛔ Open client question: whether `+852 54001530` should also become a **centre-wide** contact button (a separate small feature, not assumed).
- ❌ Automated WhatsApp reminders / API integration: not implemented (excluded).

## I. User interface & usability — ✅ with documented UAT items

- Login, admin dashboard, teacher dashboard, student dashboard, navigation, student/teacher/class tables, add/edit forms: all render (HTTP 200, live).
- Assets: `/css/app.css`, `/css/admin.css`, `/js/app.js`, `/js/manifest.js` all 200; viewport meta + responsive (Tailwind/Bootstrap) classes present; the slogan block is mobile-safe (`text-center`, `break-words`).
- Defects fixed: login 500 (mail), failed-login 500 (cache perms), importer orphan bug, `route:cache` crash.
- ⚠️ Remaining UAT items (need a real browser/devices — not automatable here): physical-device responsiveness pass; browser console-error sweep; confirm Chinese text saves through the profile **form** (the app's utf8mb4 layer verifiably preserves Chinese; the automated harness couldn't send clean multibyte). Listed in `YH_EDUCATION_PENDING_CLIENT_ITEMS.md`.

---

## ARM64 deployment compatibility (Oracle Ampere A1, Ubuntu 24.04 Minimal aarch64) — ✅ by analysis

| Component | ARM64 status | Evidence |
|---|---|---|
| PHP 8.4 + extensions | ✅ | Required extensions (mbstring, dom, intl, gd, zip, curl, pdo_mysql, bcmath, …) all ship as arm64 `php8.4-*` packages via `ppa:ondrej/php` (used in the runbook) |
| Composer packages | ✅ | Pure PHP — architecture-independent (`composer.lock` scanned: only `ext-*` platform requirements) |
| Node 22 + frontend build | ✅ | NodeSource publishes arm64; **no native-compiled npm modules** — `node-sass`/`canvas` not installed (optional-peer mentions only), `fsevents` is a macOS-only optional skip, `sass` is pure-JS dart-sass. Compiled assets are also committed, so the server build is optional |
| MySQL | ✅ | Ubuntu 24.04 `mysql-server` (8.0, arm64) or MySQL 8.4 LTS official arm64 repo — both fine (app verified on 8.4; migrations are standard SQL) |
| Web server / TLS | ✅ | nginx + Certbot: standard arm64 packages |
| Docker images (local dev only) | ✅ | php:8.4-fpm, mysql:8.4, nginx, node:22, phpmyadmin all publish arm64 manifests (production is native LEMP, no Docker required) |
| Ubuntu 24.04 **Minimal** caveats | documented | Must install `software-properties-common curl git unzip cron` first — in `YH_EDUCATION_DEPLOYMENT_GUIDE.md` |
| Oracle networking gotcha | documented | Ports 80/443 must be opened in **both** the VCN Security List **and** the instance's preconfigured iptables rules — exact commands in the deployment guide |

**Deployment itself: ⛔ NOT performed.** Server `129.150.50.78` is running but SSH access is unconfirmed; no access was attempted.

---

## Excluded features (section 2) — confirmed NOT implemented
Prepaid lesson credits · automatic credit deduction · excused-leave credit rules · renewal alerts · custom scheduling calendar · PDF invoice workflows · custom ledgers · automated WhatsApp · native mobile apps · custom reports · subscriptions · UI redesign. Existing related functionality preserved, not expanded. Logged in `SCOPE_REVIEW.md`.
