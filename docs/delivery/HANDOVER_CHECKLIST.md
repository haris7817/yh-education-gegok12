# Handover Checklist — YH Education (GegoK12)

_Final acceptance checklist for the agreed $100 engagement. Updated 2026-10-22._

---

## A. Delivered & verified (local)
- [x] Original GegoK12 installed, configured, and running locally (Docker)
- [x] PHP 8.4 / Laravel 12 / MySQL 8.4 environment; Composer + Node 22 dependency install
- [x] Frontend assets build and serve; login page renders ("YH Education")
- [x] Database migrations (108) + seeders run cleanly (synthetic/reference data)
- [x] Admin, teacher, student logins work; role separation enforced
- [x] Attendance pages and admin student/class lists load
- [x] Student Excel import tested (insert / skip-invalid / duplicate-parent / blank-qualification) — no orphaned students
- [x] Manual WhatsApp click-to-chat button on parent detail (configurable country code, HK 852)
- [x] Currency configurable; set to **HK$**; school profile name "YH Education"; timezone Asia/Hong_Kong
- [x] Install fixes: login 500 (mail), importer qualification (S-5), add-on route crash (S-6)
- [x] Regression tests added and passing (9 unit + import regression)
- [x] Work committed to branch `yh-education-delivery` (not pushed)

## B. Documentation delivered (`docs/delivery/`)
- [x] INSTALLATION.md · ADMIN_GUIDE.md · DEPLOYMENT.md · QA_REPORT.md
- [x] CONFIGURATION/CHANGELOG (CHANGELOG.md) · SCOPE_REVIEW.md · KNOWN_LIMITATIONS.md · PROGRESS.md · HANDOVER_CHECKLIST.md

## C. Pending client inputs (needed to finish)
- [ ] **Hosting decision** — Ubuntu VPS (recommended) or Render+Aiven — and **client-owned account**
- [ ] **`yheduhk` domain/subdomain** + DNS access
- [ ] **SMTP** provider/credentials (or confirm email stays off)
- [ ] Official **logo / slogan / colours** for branding
- [ ] Confirmation to proceed with **real student data** (format, data-protection, backup)
- [ ] Decision on **parent app** (not bundled — obtain from vendor if parents need access)

## D. Deployment (after approval — not yet done)
- [ ] Provision VPS; install LEMP + PHP 8.4 (`DEPLOYMENT.md` §3)
- [ ] Deploy branch; `composer install --no-dev`; build assets; production `.env` (`APP_DEBUG=false`)
- [ ] Migrate + first admin; HTTPS via Let's Encrypt on the `yheduhk` domain
- [ ] Remove/rename `public/installer`; lock down DB (localhost only); no public phpMyAdmin
- [ ] Nightly DB + uploads backup configured and a **test restore** verified
- [ ] Re-run the QA checklist against the live URL; re-time timetable/fee pages with real data
- [ ] Change seeded admin password; secure admin handover + walkthrough

## E. Remaining tests (to complete at/after deployment)
- [ ] Mobile responsiveness spot-check
- [ ] Timetable & fee pages re-timed on production host with real data
- [ ] Email sending via real SMTP
- [ ] Payslip HK$ visual confirmation (with a payroll record)

## F. Support
- **7 days** of support for defects directly related to our agreed installation/configuration, starting from go-live/handover.
- **Excludes:** new feature development, paid add-ons, the parent app/portal, automated WhatsApp/messaging, data entry, and issues caused by changes made by others.

## G. Security sign-off (at go-live)
- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] Strong, unique DB + admin passwords; demo accounts removed/disabled
- [ ] `.env` never committed; no secrets in logs; HTTPS enforced
- [ ] Database port not publicly exposed; phpMyAdmin not deployed to production
- [ ] Backups running and restore-tested
