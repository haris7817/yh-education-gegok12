# Known Limitations & Out-of-Scope — YH Education (GegoK12)

_Honest record of what is and isn't included/verified. Nothing untested is claimed to work._

---

## 1. Parent access (important)
- **Parents have no web portal.** Parent accounts authenticate but there is no parent web dashboard (the app redirects them nowhere usable). This is GegoK12's design — parents are intended to use a **mobile app**.
- **The parent Android app is NOT bundled** in the open-source core. The backend parent API exists (`/api/parent/login`), but the app binary and its metadata (`public/android-apk/parent_apk_detail.json`) are **not present** in this package. To give parents app access, YH Education must obtain the GegoK12 parent app separately from the vendor (gegok12.com).
- A parent **web** portal was **not** built (out of scope and explicitly excluded).

## 2. Features present but needing on-host re-verification
- **Timetable and fee detail/report pages** render but were **slow (>30s) over the large demo dataset** (330 demo students vs the centre's ~60) on the Windows dev machine (bind-mount I/O). This is a data-volume + dev-environment effect, **not a code defect**. They should be re-timed on the production Linux host with real data before final sign-off.
- **Teacher day-to-day flows** (attendance entry, lesson plans, schedules) exist for the teacher role; the teacher dashboard was verified to load, but each specific workflow the centre will use should be walked through on the live site.
- **Payslip HK$ display** is wired to the currency config but was not visually confirmed (needs a payroll record to render a payslip).

## 3. Not tested / out of scope
- **Mobile responsiveness** spot-check not yet performed.
- **Email delivery** is disabled locally (`MAIL_STATUS=off`); production needs a real SMTP provider configured and tested.
- Modules outside the agreed walkthrough (library, payroll, inventory/stock, quizzes, admissions portal, social feed/pages) exist in the codebase but were **not** individually tested as part of this engagement.

## 4. Paid / vendor-dependent
- GegoK12 has a **paid add-on marketplace** (e.g. the Exam add-on). No add-on was installed, activated, or bypassed. The add-on route was guarded so a missing paid controller no longer breaks `route:list`/`route:cache`.
- The add-on marketplace shows **vendor-currency** prices; these were intentionally left unchanged.

## 5. Student import caveats (works, with rules)
- The importer requires `class`/`section` to match configured classes and a single-match `parent_qualification`; see `ADMIN_GUIDE.md` §5. Rows that don't match are safely skipped and reported.
- **Real student data has not been imported.** Do so only after the format, data-protection and backup approach are confirmed by the client.

## 6. Explicitly excluded (not built)
Automated WhatsApp reminders / WhatsApp API / message templates / scheduling; new payment gateways; parent web portal; native mobile apps; major UI redesign; custom dashboards or report/certificate systems; any paid module. The WhatsApp feature delivered is a **manual** click-to-chat button only.

## 7. Testing hazard — upstream Feature tests (now guarded)
The 4 upstream `tests/Feature/*` tests use `RefreshDatabase` (`migrate:fresh`). In this setup the `phpunit.xml` / `.env.testing` sqlite isolation does **not** take effect (confirmed: the test env still resolves to `mysql`/`school`), so without protection, running the full `vendor/bin/phpunit` executes `migrate:fresh` against the **live MySQL database and erases all data** (this happened once during Phase K).

**Safeguard now in place** (`tests/TestCase.php`): every test aborts (is skipped) **before** `RefreshDatabase` runs if the database isn't in-memory SQLite — so the dev/prod DB can no longer be wiped by the test suite. Verified: MySQL-targeted tests are skipped, the dev DB stayed intact, and the Unit suite + import regression still pass. To actually *run* the Feature suite, first fix the sqlite isolation so the guard allows it (the guard message tells you what to set). Safe to run today: `vendor/bin/phpunit --testsuite Unit` and `tests/Regression/import_qualification_regression.php`.

## 8. Environment-specific notes (local build machine)
- Local antivirus HTTPS scanning (AVG) had to be disabled to let containers download dependencies; `node:24` and the official `composer` image were corrupt on the host and were worked around (`node:22`, host-downloaded composer). These are local build-environment issues and do not affect the production VPS deployment.
