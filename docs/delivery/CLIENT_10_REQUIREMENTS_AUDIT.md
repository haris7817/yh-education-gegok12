# YH Education — Client 10-Requirements Audit

_Audit date: 2026-10-27/28 · Auditor role: scope & technical verification · Production: https://yheduhk.duckdns.org (Oracle Ampere A1, Ubuntu 24.04 ARM64) · Branch: `yh-education-delivery`_

**Method.** Evidence-based, audit-first. No production data was modified; no features were implemented. New functional tests were run on the **local** Docker stack (identical code) and safe read-only / disposable checks on production (backup restore into a throwaway DB, immediately dropped). Companion docs: `ORIGINAL_SCOPE_VS_CLIENT_SPECIFICATION.md` (agreement evidence), `EXISTING_GEGOK12_FEATURE_VERIFICATION.md` (core vs paid add-ons), `MOBILE_APP_INTEGRATION_FEASIBILITY.md`, `FINAL_UAT_CHECKLIST.md`, `REMAINING_WORK_AND_ESTIMATES.md`.

> **Addendum 2026-10-28:** the two confirmed delivery gaps below were closed after this audit: (R9) three demo accounts — teacher, student, parent — now exist in production and are login/RBAC-verified (P-17/P-18 in `YH_EDUCATION_TEST_RESULTS.md`); (R4 defect side) all fee endpoints that crashed on the missing paid add-on now degrade gracefully (commit `898da2e4`, P-16). The scope classifications themselves are unchanged.

**Critical context discovered by this audit:** GegoK12's open-source core does **not** contain the Fee module or the Exam/Gradebook module. Both are paid vendor add-ons (`Gegok12\Fee\*`, `Gegok12\Exam\*` composer packages, referenced only behind `class_exists()` guards). Several core routes assume the add-ons and **crash (HTTP 500)** without them — reproduced and logged (`Class "App\Models\Fee" not found`). Earlier delivery reports described fee pages as "accessible"; that statement was based on page-level HTTP 200s and is **corrected** by this audit.

---

## Master comparison table

| # | Requirement | Original Agreement Evidence | Existing GegoK12 Support | Implemented | Tested | Scope Classification | Remaining Work |
|---|---|---|---|---|---|---|---|
| 1 | Complete deployment | Recorded scope: "standard install… one deployment" (`SCOPE_REVIEW.md`) | Full (core) | Yes — live | **P-01…P-14** + re-verified 2026-10-27 (certbot timer, MySQL 127.0.0.1, installer disabled, 5 users only) | **COMPLETED & VERIFIED** | None |
| 2 | Branding & dashboards | Recorded scope: "basic branding, minor styling" | Admin/Teacher/Student dashboards in core; **no parent web portal (by GegoK12 design)** | Yes — name, official logo (commit `835f815b`), EN+中文 info, 3 dashboards live | Live logins all 3 roles (P-04/06/07); responsive markup verified; physical-device pass = UAT | **COMPLETED & VERIFIED** (parent portal: upstream design limit; "dashboard customization": **SCOPE UNCLEAR — AGREEMENT EVIDENCE NEEDED**) | Client UAT on phone; parent access is via mobile app only (see #7) |
| 3 | Attendance tracking | "testing existing features" (recorded scope) | Full in core (`attendances` table, absentee-model, forenoon/afternoon sessions) | Upstream feature, preserved | **NEW: end-to-end HTTP save test PASSED 2026-10-28** (login → POST `/admin/attendance/add` → `{"success":"Attendance Added Successfully"}` → row persisted → cleaned up); list/history/export routes 200; teacher+admin role paths | **COMPLETED & VERIFIED** | One client UAT click-through on live site |
| 4 | Fees, invoices, PDF receipts | Recorded scope **explicitly excludes** "PDF invoice workflows, custom ledgers"; add-ons gated (S-2) | **Fee module is a PAID add-on** (`Gegok12\Fee`). Core: no `Fee` model, no `fees` migration, zero "invoice/receipt" strings; fee drill-downs **500** without add-on (verified) | Not possible from core | Verified absent: `/admin/dashboard/feeslist/1` → 500, log `Class "App\Models\Fee" not found`; dompdf exists but is used for payslips/ID cards/lesson plans only | **MISSING upstream / OUTSIDE CONFIRMED SCOPE** | Client decision: buy vendor Fee add-on (then ~2–4 h config) or commission custom fee+invoice build (~25–40 h) |
| 5 | Academic tracking | Not in recorded scope; add-ons gated (S-2); "custom reports" excluded | Exams/marks/gradebook/report cards/analytics = **paid Exam add-on** (`Gegok12\Exam`, `class_exists` guards everywhere; marks pages `return back()` without it). Core has: assignments with marks, lesson plans (+PDF), timetable, promotions | Core parts work; exam parts absent | Code-level verification (guards traced in `app/Traits/Dashboard.php`, `app/Schoolplus/StudentService.php`, `StudentDetailsController@marksGraph`) | **PARTIALLY COMPLETED** (core parts) / exam-gradebook-report-cards **OUTSIDE CONFIRMED SCOPE** (paid add-on) | Client decision as #4: vendor Exam add-on or custom (~40–60 h); report-card PDFs +10–20 h |
| 6 | Security & data isolation | In scope ("role/school config", testing) | Laratrust + per-role middleware + gates | Yes | RBAC live (P-04…P-07); **NEW 2026-10-27**: unauth direct URLs all blocked (401: `/admin/students`, `/teacher/dashboard`, `/student/dashboard`, `/api/v2/myinfo`); bcrypt; MySQL localhost; sessions invalidated on logout | **COMPLETED & VERIFIED** (tested boundaries) | Record-level IDOR fuzzing & formal pen-test were never in a $100 scope; no high-severity issue observed |
| 7 | Teacher/Parent mobile apps | Recorded scope **explicitly excludes** "native mobile apps"; parent-app gap documented since 2026-10-22 (`SCOPE_REVIEW.md`) | Official apps exist on Google Play (GegoSoft). Server API present & responding (sanctum; `/api/parent/login` live). **APKs not bundled**; `public/android-apk/` absent; `/api/apk/*` return empty; **no docs on pointing Play-Store apps at a self-hosted server** | Backend ready; app connection unproven | API endpoints respond on production (200/302); app-side connectivity **cannot be proven** without vendor info + devices | **REQUIRES CLIENT INPUT** (vendor) + **OUTSIDE CONFIRMED SCOPE** as recorded | Ask GegoSoft how Play-Store apps select a server; see `MOBILE_APP_INTEGRATION_FEASIBILITY.md` |
| 8 | Backups & scheduled tasks | In scope (deployment hardening) | n/a (added by us) | Yes — nightly 02:15 + scheduler cron (root crontab verified) | **NEW 2026-10-27: real restore test PASSED** — `db-2026-10-27.sql.gz` restored into throwaway DB `yhedu_restore_test`: 113 tables, 5 users, 9 settings; DB dropped after. 14-day retention; docs in deployment guide + script header | **COMPLETED & VERIFIED** | Recommend occasional offsite copy (client) |
| 9 | Test accounts & documentation | In scope (handover) | n/a | Docs: complete (6 named docs + working set). Admin account: live, credential handover procedure defined. **Teacher + student/parent test accounts: NOT present** (QA accounts were disposable and deleted after P-06/P-07, per deployment instructions) | Prod user table verified 2026-10-27: 5 users = 1 admin + 4 locked demo staff; groups 5/6/7 empty | **PARTIALLY COMPLETED** | Create 3 named test accounts (teacher, student, parent) at handover — ~0.5–1 h, reasonable to include in $100 acceptance |
| 10 | UAT & agreed defect corrections | In scope (7-day defect support recorded) | n/a | UAT checklist now delivered: `FINAL_UAT_CHECKLIST.md` | All agreed defects fixed & regression-tested (importer orphans, addon route crash, login 500s, storage/installed, faker, --no-tablespaces) | **IMPLEMENTED BUT NOT FULLY TESTED** (client confirmation pending by definition) | Client executes checklist; defects found → 7-day support |

---

## Requirement-by-requirement evidence

### R1 — Deployment: COMPLETED & VERIFIED
- P-01…P-14 (`YH_EDUCATION_TEST_RESULTS.md` §0), re-checks 2026-10-27: certbot timer next run 2026-10-28 07:10 HKT; `ss -tlnp` MySQL on 127.0.0.1:3306 only; installer moved out of webroot (P-13); production has exactly 5 users — admin (rotated credentials) + 4 demo-staff accounts **locked with random passwords** (not default credentials); 108 migrations + settings live.

### R2 — Branding & dashboards: COMPLETED & VERIFIED (with two notes)
- Live: YH Education name, official 言希教育 logo (login page + favicon + school profile, commit `835f815b`), bilingual address/slogans, admin/teacher/student dashboards each 200 under their own role.
- Note A: **parents have no web dashboard in GegoK12 by design** — parent access is the vendor's mobile app (see R7). Documented since `KNOWN_LIMITATIONS.md` §1.
- Note B: the client's phrase "dashboard customization requested in the original specification" cannot be checked — **the original five-phase specification is not in this workspace** (see `ORIGINAL_SCOPE_VS_CLIENT_SPECIFICATION.md`). No dashboard redesign was recorded in the accepted scope; the recorded scope lists "basic branding, minor styling".

### R3 — Attendance: COMPLETED & VERIFIED (new functional proof)
- 2026-10-28, local stack (same code as production): scripted browser-equivalent flow — admin login → GET `/admin/attendance/add` (CSRF) → POST with `standardLink_id`, `date`, `session=forenoon`, absentee list → JSON success → row persisted in `attendances` (verified by SQL) → `/admin/attendance/list` 200 → disposable rows deleted (0 remain).
- Model note: GegoK12 records **absentees** (status 0) per class/date/session (forenoon/afternoon); present students have no row. Duplicate same-class/date/session submissions are rejected by validation ("Attendance already updated").
- History & filtering: per-student history (`/admin/student/show/attendance/{name}`), per-class view + CSV export (`/admin/attendance/export/{standardLink_id}`), absentee lists by date. Teacher role has the same flow (`routes/teacher.php:227-231`) plus the teacher mobile API (`routes/teacherapi.php:236-242`).
- Permissions: routes sit behind role middleware; teacher class access is gate-checked (`Gate::allows('standardlink')`).

### R4 — Fees/invoices/PDF receipts: MISSING upstream, OUTSIDE CONFIRMED SCOPE
- The Fee module is a **paid GegoK12 marketplace add-on**. Core evidence:
  - No `App\Models\Fee` / `FeePayment` model files; no `fees`/`fee_payments` migrations (113 production tables came from migrations + add-on-independent seeds; no fee tables).
  - `grep -ri "invoice|receipt"` over `app/`, `routes/`, `resources/views/` → **zero matches**.
  - Code branches like `if (class_exists('Gegok12\Fee\Models\Fee'))` (`app/Http/Controllers/Teacher/StandardsLinkController.php:312`, `app/Traits/AdmissionUser.php:181`).
  - `routes/setting.php:26` upstream comment: `//error in fees addon`.
  - **Authenticated live test (local): `/admin/dashboard/feeslist/1` → HTTP 500, `/admin/dashboard/unpaidList/null` → HTTP 500**; Laravel log: `Class "App\Models\Fee" not found` (×2). These dashboard drill-downs will 500 on production too if clicked.
- PDF engine (dompdf v3.1.1) **is** installed, but upstream uses it only for payslips, staff/teacher ID cards, lesson plans, visitor logs and student exports — **no invoice or receipt template exists anywhere**.
- Recorded agreement explicitly excludes "PDF invoice workflows" and "custom ledgers"; paid add-ons were gated on client approval since 2026-10-22 (S-2).
- Path forward: vendor Fee add-on purchase (then configuration ~2–4 h) **or** custom development (~25–40 h). See `REMAINING_WORK_AND_ESTIMATES.md`.

### R5 — Academic tracking: PARTIALLY (core) / exam stack OUTSIDE CONFIRMED SCOPE
- In core and working: **assignments with marks entry** (teacher: `assignment/addMarks`, `student_assignments` table), **lesson plans** (incl. PDF print), timetable, promotions, student profile history.
- Not in core (paid `Gegok12\Exam` add-on): exams, exam schedules, marks/gradebook, grades, report cards, marks analytics. Evidence: every reference is `class_exists`-guarded (`app/Traits/Dashboard.php:134-209`, `app/Schoolplus/StudentService.php`, `app/Observers/AcademicYearObserver.php`); `StudentDetailsController@marksGraph` does `return back()` when the add-on is absent; `routes/addon.php` labels Exam "a paid GegoK12 marketplace module… NOT installed".
- **No report-card or printable academic report exists without the add-on.**

### R6 — Security & isolation: COMPLETED & VERIFIED (tested boundaries)
- Live production: admin/teacher/student RBAC verified with disposable accounts (P-04…P-07, since deleted); cross-role URLs redirect to own dashboard.
- 2026-10-27 unauthenticated direct-URL probe (prod): `/admin/students`, `/teacher/dashboard`, `/student/dashboard`, `/api/v2/myinfo` → all **401**.
- bcrypt hashes ($2y$, 60 char); session invalidation on logout verified; rate limiter functional (P-05); MySQL localhost-only (P-10); no high-severity authorization vulnerability observed.
- Honest limits: exhaustive record-level IDOR fuzzing and a formal penetration test were not performed (and are not $100-scope activities). Teacher class access is gate-checked in code.

### R7 — Mobile apps: REQUIRES CLIENT INPUT / OUTSIDE CONFIRMED SCOPE
Full analysis in `MOBILE_APP_INTEGRATION_FEASIBILITY.md`. Summary:
- Official **GegoK12 Teacher App** and **GegoK12 Parent: School App** exist on Google Play (publisher GegoSoft Technologies). Sources: [Google Play developer page](https://play.google.com/store/apps/dev?id=8582811272927365111), [gegok12.com](https://gegok12.com/).
- Our server exposes the matching API (sanctum; parent login by mobile number, children/attendance/leave/messages endpoints; separate teacher API) and it responds on production.
- **Blockers to proving integration:** app binaries/source are not in the open-source distribution (`public/android-apk/` absent; `/api/apk/parent` returns an empty body); no public documentation explains how the Play-Store builds select a server (hardcoded vendor cloud vs. per-customer build vs. in-app setting — unknown); exam/fee API routes are commented out (add-on-dependent); push notifications need FCM credentials.
- Recorded scope lists "native mobile apps" under exclusions; the parent-app gap was documented for the client on 2026-10-22.
- **Do not claim it works; do not sideload unverified APKs.** Next step is a vendor question, not engineering.

### R8 — Backups & scheduling: COMPLETED & VERIFIED
- Root crontab (verified live): `schedule:run` every minute; `/usr/local/bin/backup-yhedu` nightly 02:15 → `/var/backups/yhedu/` (today's `db-2026-10-27.sql.gz` 74 KB + `uploads-2026-10-27.tar.gz` 2.2 MB present), 14-day retention.
- **Restore test executed 2026-10-27** on an isolated throwaway DB (`yhedu_restore_test`): gunzip → import → **113 tables, 5 users, 9 settings** → dropped. Production DB untouched.
- Recovery documentation: `backup-yhedu` script header + `YH_EDUCATION_DEPLOYMENT_GUIDE.md`.

### R9 — Test accounts & documentation: PARTIALLY COMPLETED
- Delivered: admin account (live, rotated credentials, secure handover via server-side credentials file), setup guide, admin guide, deployment guide, backup/recovery docs, delivery checklist, UAT list.
- **Gap:** no functional teacher, student, or parent test accounts exist in production right now (groups 5/6/7 are empty — the QA accounts used for P-06/P-07 were disposable by instruction and removed; the 4 locked demo-staff accounts are librarian/receptionist/accountant/stock-keeper and deliberately unusable). The client's expectation of three working role accounts is reasonable for acceptance: creating them is ~0.5–1 h of configuration and should be done at handover (needs one class/section to attach the student to — or they can be created live together with the client during UAT).

### R10 — UAT & defect corrections: checklist delivered, client confirmation pending
- `FINAL_UAT_CHECKLIST.md` (new) covers every in-scope confirmed feature with pass/fail columns.
- All defects agreed during the engagement are fixed and committed (importer orphans D-2, addon route crash D-3, two login-500 causes, `storage/installed`, faker-in-production, mysqldump privileges).
- Final acceptance remains the client's call — not claimed here.

---

## Executive summary

The $100 engagement's recorded scope — install, configure, brand, test existing features, deploy once, document, 7-day defect support — is delivered and now largely **proven**, including two verifications this audit added: a true end-to-end attendance save and a real backup restore. Three of the client's ten requirements, however, rest on functionality that **does not exist in open-source GegoK12**: fee/invoice/PDF-receipt management and the exam/gradebook/report-card stack are paid vendor add-ons (and the recorded agreement explicitly excluded PDF invoices, custom reports and native mobile apps), while the Play-Store companion apps cannot be proven to connect to a self-hosted server without information only the vendor (GegoSoft) can supply. One genuine delivery gap is ours to close: three working test accounts (teacher/student/parent) do not currently exist in production and should be created at handover (~1 h). The original Fiverr five-phase specification is not present in this workspace, so any client claim that rests on its exact wording needs that document produced before it can be settled.
