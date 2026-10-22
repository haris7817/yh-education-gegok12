# QA Report — YH Education (GegoK12) — Local Development

_Environment: local Docker (app php-fpm 8.4, nginx :8090, MySQL 8.4 `school`, node:22). Synthetic demo data only. Date: 2026-10-22._

> All passwords below are the factory default `password` for **synthetic demo accounts** (`*@mailinator.com`). Not real data; local only; not to be exposed publicly.

| ID | Feature | Steps | Expected | Actual | Result |
|----|---------|-------|----------|--------|--------|
| T-01 | Stack startup | `docker compose up -d` | 5 containers Up | nginx, app, db, node(22), phpmyadmin all Up | PASS |
| T-02 | Composer deps | `composer install` | vendor/ + lock built | vendor/ OK, composer.lock generated (595 KB) | PASS |
| T-03 | Laravel boot | `php artisan --version` | boots | Laravel 12.70.1 | PASS |
| T-04 | APP_KEY | `key:generate` | key set | set successfully | PASS |
| T-05 | NPM deps | `npm install` (node:22) | installs | 1125 packages, package-lock.json created | PASS |
| T-06 | Migrations | `php artisan migrate` | 108 tables | all migrations DONE | PASS |
| T-07 | Seeders | `php artisan db:seed` | reference + demo data | all seeders DONE | PASS |
| T-08 | Reference data | query DB | schools/standards/subjects present | 1 school, 2 academic years, 15 standards, 152 subjects, 7 roles, 9 settings | PASS |
| T-09 | Login page | GET /login | real login form | form with email+password+CSRF; title "YH Education" | PASS |
| T-10 | Admin login | POST /login (demoschool@mailinator.com) | 302 → admin dashboard | 302 → /admin/dashboard | PASS |
| T-11 | Admin dashboard | GET /admin/dashboard (authed) | renders widgets | 200, Student/Teacher/Fee widgets render | PASS |
| T-12 | Teacher login | POST /login (teacher) | authenticates | 302; /teacher/dashboard 200 | PASS |
| T-13 | Student login | POST /login (student) | authenticates | 302; /student/dashboard 200 | PASS |
| T-14 | Parent login | POST /login (parent) | authenticates | 302 (auth OK) but no web dashboard route (404) | PASS (auth) / see L-1 |
| T-15 | Role separation | cross-role dashboard access | blocked to own area | admin/teacher/student each redirected to own dashboard; no cross access | PASS |
| T-16 | Static assets | GET /css/app.css, /css/admin.css, /js/app.js, /js/manifest.js | 200 | all 200 (100KB / 441KB / 27KB / 6.7KB) | PASS |

## Batch 2 — module, import, parent & branding tests (2026-10-22)

| ID | Feature | Expected | Actual | Result |
|----|---------|----------|--------|--------|
| T-17 | Student Excel import — insert | rows create students | 3 students inserted, correct field mapping | PASS |
| T-18 | Import — skip invalid | bad class/section skipped | row 5 skipped → `skipped_rows:[5]` | PASS |
| T-19 | Import — duplicate parent | same parent_mobile links, not duplicated | parent created once, linked to 2 students (parents +2 not +3) | PASS |
| T-20 | Import — validation (qualification) | — | empty/multi-match `parent_qualification` silently drops parent (see D-2) | PARTIAL |
| T-21 | Admin student mgmt `/admin/students` | renders | 200 (~5.5s) | PASS |
| T-22 | Classes `/admin/standardlinks` | renders | 200 (~11.8s) | PASS |
| T-23 | Attendance `/admin/attendance/{list,add}` | renders | 200 | PASS |
| T-24 | Timetable (per standard-link) | renders | route exists; >30s over 330-student demo data — retest on target host | SLOW |
| T-25 | Fees (`/admin/report/fees`, per standard-link) | renders | route exists; >25–30s over demo data — retest on target host | SLOW |
| T-26 | School profile name | set to "YH Education" | `schools.name`='YH Education', slug='yh-education' | PASS |
| T-27 | Parent interface | determine support | parents use **mobile app** (`/api/parent/login`, APK at `/api/apk/parent`); no web portal | DOCUMENTED |

## Defects found
- **D-1 — Login 500 (FIXED).** `LogSuccessfulLogin` listener sends a login email over credential-less SMTP (`530`). **Fix:** `MAIL_MAILER=log` + `MAIL_STATUS=off` in local `.env`. Verified.
- **D-2 — Importer drops parents on empty/ambiguous `parent_qualification` (NOT fixed — needs approval).** The importer does `display_name LIKE '%<value>%'` then `implode('')` of **all** matching qualification ids. Empty value matches every ug/pg qualification → concatenated invalid id → FK violation on `parent_profiles.qualification_id` → parent silently rolled back (`Log::info` only, student still created → **orphaned student**). Works only when `parent_qualification` matches **exactly one** qualification. Impact for client's real import: every parent row must use a full, unique qualification name. Fixing the importer logic is a code change — flagged for approval (see SCOPE_REVIEW S-5).
- **D-3 — `php artisan route:list` / `route:cache` crash.** `routes/addon.php` references `App\Http\Controllers\Admin\Addon\AddonInstallExamController`, which doesn't exist in core (paid Exam add-on stub) → `ReflectionException`. App runs fine (we don't route:cache), but **`route:cache` will fail on deployment** — relevant to Phase H. Minimal fix (guard/remove the addon route) flagged for approval (SCOPE_REVIEW S-6).

## Known limitations (upstream behavior, not defects we caused)
- **L-1 — No parent web dashboard (by design).** Parents authenticate on web but have no web portal (→404). The supported parent interface is the **GegoK12 Parent mobile app (Android)**: token login at `POST /api/parent/login`, APK served at `GET /api/apk/parent`. Web-side, parents are only *managed* by admins (`/admin/parent/*`). **Client confirmation needed:** parents will not have web access, only the mobile app. Building a parent web portal is out of scope.
- **L-2 — Performance over demo dataset.** Pages are slow (5–30s+) because of (a) the oversized demo seed (330 students / 181 teachers vs. the client's ~60/4) and (b) the Windows bind-mount I/O penalty in dev. Expected to be far faster on the Linux target host with real data volume; retest fee/timetable there.

## WhatsApp manual button — placement analysis (identification only; NOT implemented)
- **Phone fields:** `users.mobile_no` (primary, for students/parents/teachers) and `userprofiles.alternate_no` (parent alternate). **No country-code/dial-code column**; numbers are stored **bare** (e.g., `2268456918`, no `+852`).
- **Which number to open:** the **parent's** `mobile_no` (usergroup 7) — students are minors, parents are the contact.
- **Where the button should appear:** `resources/views/admin/parent/show.blade.php` (parent detail, next to the mobile number); optionally the parent list `admin/parent/index.blade.php` per row.
- **Simplest maintainable approach (for approval):** a click-to-chat link `https://wa.me/<countrycode><mobile_no>` opening in a new tab. **Key dependency:** because numbers lack a country code, a configurable default (HK = `852`) must be prepended, and the number sanitized (strip spaces/leading zero). This is a **manual** click — not automated messaging. **Not implemented pending approval** (placement + country-code handling confirmed with client).

## Batch 3 — approved fixes verified (2026-10-22)

| ID | Item | Verification | Result |
|----|------|-------------|--------|
| T-28 | S-6 add-on route | `route:list` → 1242 routes (no crash); `route:cache` → "cached successfully"; then cleared | PASS |
| T-29 | S-5 importer fix | `tests/Regression/import_qualification_regression.php` → **ALL PASSED** (insert / skip / blank+unknown qualification create parent / duplicate parent linked / **no orphans**) | PASS |
| T-30 | S-7 WhatsApp helper | `tests/Unit/WhatsappNumberTest.php` → OK (9 tests) | PASS |
| T-31 | S-7 button render | parent detail page returns 200 and contains `wa.me/<number>` + "Chat on WhatsApp" | PASS |
| T-32 | S-3 currency config | `config('app.currency_symbol')`=**HK$**, `whatsapp_country_code`=852; payslips use the config | PASS |
| T-33 | Regression after all fixes | admin/teacher/student logins 302; route:list healthy; unit + import regression green | PASS |

## Investigations (per additional instructions)

- **Slow timetable/fee pages (no rewrite).** Response time scales with data volume: `/admin/students` 5.5s and `/admin/standardlinks` 11.8s over the **330-student / 181-teacher demo seed** (≈5× the client's real ~60/4); the per-class/report fee + timetable pages aggregate across the whole class and exceed 30s. Two compounding causes: (a) oversized demo data, (b) the **Windows bind-mount I/O penalty** (every request reads/compiles many files — absent on a Linux host). Conclusion: **not a code defect**; no rewrite warranted. **Action:** retest on the Linux target host with real data volume; if any page is still slow there, add eager-loading to that specific query (separately scoped). Warming the view cache (`php artisan view:cache`) noticeably reduces first-hit latency in production.

- **Android parent app availability/compatibility.** Backend API is present (`POST /api/parent/login`, served by `TokenController@issueToken`). The APK metadata endpoint `GET /api/apk/parent` (`ApkController@parentApp`) reads `public/android-apk/parent_apk_detail.json` — **this file and the `public/android-apk/` directory are NOT present** in the open-source core. So the **parent app binary/metadata is not bundled**; the server supports the parent app's API, but the app itself must be obtained separately from GegoK12 (e.g. a published Play Store build pointed at this server, or a vendor-provided APK). **Client decision needed.** Not developed (out of scope).

## Still pending (next: deployment preparation)
- Mobile responsiveness spot-check · production asset rebuild (committed assets serve fine today) · security review (phpMyAdmin/DB port exposure for prod) · payslip HK$ visual check (needs a payroll record).
