# YH Education — Test Results

## 0. PRODUCTION verification — https://yheduhk.duckdns.org (2026-10-27)

Deployed to the client's Oracle Ampere A1 (Ubuntu 24.04 ARM64, 129.150.50.78) via `deploy/deploy-yhedu.sh`. All checks below ran against the **live site**:

| # | Check | Result |
|---|---|---|
| P-01 | HTTPS 200, valid **Let's Encrypt** cert (expires 2027-01-25), auto-renew timer active | PASS |
| P-02 | HTTP → HTTPS redirect (301) | PASS |
| P-03 | Branding live: "YH Education" + both slogans incl. 中文 (言傳身教 希冀成材); school record name/phone/email correct | PASS |
| P-04 | **Admin login** (rotated credentials) → `/admin/dashboard`; dashboard/students/add/edit/teachers/attendance/school-details all 200 | PASS |
| P-05 | Wrong password → 302 + error (no 500; limiter healthy) | PASS |
| P-06 | **Teacher login** (disposable record) → own dashboard 200; blocked from `/admin/*` | PASS |
| P-07 | **Student login** (disposable record) → own dashboard 200; blocked from `/admin/*` | PASS |
| P-08 | **WhatsApp button** renders live (`wa.me/85291234567` from an 8-digit HK test number — 852 prefix applied) | PASS |
| P-09 | All disposable QA records **hard-deleted** (0 remaining incl. soft-deletes); prod has exactly 5 users: admin + 4 locked demo-staff, **zero demo students/teachers/parents** | PASS |
| P-10 | MySQL bound to 127.0.0.1 only; port 3306 unreachable from the internet | PASS |
| P-11 | Reference-only seeding executed (refused nothing; empty-DB guard verified in the aborted first run) | PASS |
| P-12 | Scheduler + nightly backup crons installed; test backup contains full schema+data (113 CREATE TABLE); `--no-tablespaces` fix applied | PASS |
| P-13 | Web installer disabled (`public/installer` moved out of webroot); `storage/installed` runtime marker present | PASS |
| P-14 | Log sweep: zero unexplained errors (all 5 entries were the fixed first-run Faker failure, tinker psysh-home artifacts, and one null view during a failed QA fetch) | PASS |

**Deployment defects found & fixed during rollout** (all committed):
1. Faker was a dev-only dependency but the reference seeders use model factories → first seed run failed under `--no-dev`; `fakerphp/faker` moved to production requires.
2. `storage/installed` is a **required runtime marker** (AppServiceProvider skips loading the settings table without it → login shows "maintenance"); the script/docs wrongly removed it — corrected to ensure it exists.
3. `mysqldump` needed `--no-tablespaces` under the least-privilege DB user.
Partially-seeded first-run DB (zero users) was dropped and recreated before re-seeding.

_Final audit run: **2026-10-26**, against the running local stack (Docker: php-fpm 8.4.27, nginx :8090, MySQL 8.4, node 22). Earlier phase evidence in `QA_REPORT.md` (T-01…T-41). **No result below is fabricated; items that could not be tested are listed as such.**_

## 1. Automated tests (executed 2026-10-26)

| Suite | Result | Notes |
|---|---|---|
| Unit suite (`vendor/bin/phpunit --testsuite Unit`) | **OK — 10 tests, 11 assertions, 1 skipped** | 9 WhatsApp-number tests pass; 1 test skipped **by the DB-safety guard** (expected — see §4) |
| Student-import regression (`tests/Regression/import_qualification_regression.php`) | **ALL PASSED** | insert / skip-invalid / duplicate-parent linking / blank-qualification safety / **zero orphaned students** |
| HK profile validation (`tests/Regression/hk_profile_validation_check.php`) | **ALL PASSED** | HK data accepted (+852, 8-digit, no pincode); legacy Indian-format data still valid; `moto` > 150 chars still rejected |
| Database integrity after all tests | **PASS** | dev DB intact (1,212+ users) — no wipe |

## 2. Live functional checks (executed 2026-10-26)

| # | Check | Result |
|---|---|---|
| 1 | App startup — `/login` 200, `/` → 302 login | PASS |
| 2 | Branding — title "YH Education"; **both slogans render** (EN + 言傳身教 希冀成材); school record: name / address (EN+中文) / `+852 54001530` / `yh.eduhk@gmail.com` | PASS |
| 3 | Admin login → 302 → `/admin/dashboard` (200, fee/student/teacher widgets render) | PASS |
| 4 | Teacher login → own dashboard 200; **blocked** from `/admin/*` (redirected to own dashboard) | PASS |
| 5 | Student login → own dashboard 200; **blocked** from `/admin/*` | PASS |
| 6 | Wrong password → 302 back to login **with error message** (was HTTP 500 — fixed this audit, see §3) | PASS (after fix) |
| 7 | Logout (teacher & student) → 302 → `/`; post-logout dashboard access requires login | PASS |
| 8 | Password hashing — bcrypt `$2y$`, 60 chars | PASS |
| 9 | Password-reset page `/password/reset` → 200 (email delivery needs production SMTP) | PASS (page) |
| 10 | Student CRUD pages — list `/admin/students` 200 · add 200 · edit `/admin/student/edit/{name}` 200 · view `/admin/student/show/{name}` 200 | PASS |
| 11 | Teacher management — `/admin/teachers` 200 · `/admin/teacher/add` 200 | PASS |
| 12 | Attendance — `/admin/attendance/list` 200 · `/admin/attendance/add` 200 | PASS (pages) |
| 13 | Fees — dashboard fee widgets render; fee routes present | PASS (see caveat C-2) |
| 14 | WhatsApp button — renders on parent detail, `wa.me/852…` link, "Chat on WhatsApp" | PASS |
| 15 | Assets — `/css/app.css`, `/js/app.js` → 200 | PASS |
| 16 | ARM64 dependency scan — no native npm modules; all PHP extensions standard | PASS (static) |

## 3. Defect found & fixed during this audit
**Failed login returned HTTP 500.** Cause: php-fpm workers run as `www-data`, but `storage/framework/cache/data` was root-owned `755`, so the login **rate-limiter** could not create its lock file (`fopen` → false → `flock()` TypeError). Fix: `chmod -R a+rwX storage/framework bootstrap/cache` (added to `INSTALLATION.md`; the production runbook already sets `www-data` ownership, so this is a local-dev issue only). Re-tested: wrong password now 302 + visible error; throttle path functional.

## 4. Destructive-test protection (context for the "skipped" test)
The four upstream `tests/Feature/*` suites use `RefreshDatabase` (`migrate:fresh`) and the sqlite test isolation does **not** engage in this environment — running them once wiped the dev database (recovered same day; synthetic data only). A **safety guard** (`tests/TestCase.php`) now skips any test whose database is not in-memory SQLite, *before* migrations can run. The guard itself is exercised by `tests/Unit/DatabaseSafetyGuardTest.php` (the "1 skipped"). **Do not run `php artisan test` / the full phpunit suite against a live database.** Details: `KNOWN_LIMITATIONS.md` §7.

## 5. Not tested / requires manual verification (honest gaps)

| Item | Why | How to verify |
|---|---|---|
| Attendance **save** (marking) end-to-end | Vue-driven POST; automating its payload was disproportionate | UAT: mark attendance for one class, reload, confirm saved (~10 min) |
| Chinese text via the profile **form** | The curl harness (Windows) mangles multibyte in transit; the app's utf8mb4 layer verifiably preserves Chinese (Eloquent round-trip PASS) | UAT: edit the slogan in a real browser, save, reload |
| Physical mobile-device pass & console-error sweep | Needs real browsers/devices | UAT on phone + desktop dev-tools |
| Fee/timetable report page timing | >30 s over the 330-student demo data on the Windows bind-mount (environment/data-volume effect) | Re-time on the production server with real (~60-student) data |
| Email sending (reset, notifications) | SMTP intentionally off locally | Configure SMTP in production, send test |
| Upstream Feature suite (25 pre-existing failures) | Pre-dates this engagement (zero auth/test files touched); unsafe to run against live DB | Out of scope — upstream test debt |
| **Production deployment** | SSH access to 129.150.50.78 not yet provided | Deploy per `YH_EDUCATION_DEPLOYMENT_GUIDE.md` once access is confirmed |
