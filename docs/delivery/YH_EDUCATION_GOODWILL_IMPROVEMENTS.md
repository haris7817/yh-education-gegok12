# YH Education — Goodwill Improvements (Hong Kong Localization Iteration)

_Implemented, tested and deployed to production on **2026-10-28** at no charge, as a goodwill gesture on top of the completed $100 engagement. Commit `7e17c381` on `yh-education-delivery` (+ docs commit). Live at https://yheduhk.duckdns.org._

## The five improvements

### 1. Hong Kong localization & address fix — DONE
- **Google Maps removed.** The address widget (`resources/assets/js/components/GoogleMap.vue`) previously loaded the Google Maps JavaScript + Places APIs, which require an API key and Google Cloud **billing** — without them the client saw a Chinese-language Maps error. It is now a plain manual address field (same component name/props, so student, teacher, staff, parent and school-details forms all inherit the fix); the `maps.googleapis.com` script tags were removed from all 10 blades; the one component with its own inline map code (`parent/Edit.vue`) is guarded. **No API key, no billing, no error.** Chinese and English addresses save and display; existing stored addresses untouched.
- **India-specific defaults replaced.** New idempotent reference seeder (`HongKongReferenceSeeder`) adds **Hong Kong** country/state/city rows (no demo data); the student form now defaults to them invisibly. Tamilnadu/Chennai etc. remain in the database for old records but are no longer offered or required.
- **Traditional Chinese verified.** utf8mb4 end-to-end: 陳小明-style names, 將軍澳 addresses and 言希教育 render byte-intact through forms, the database, profiles and the Excel import (test evidence below).

### 2. Simplified student management — DONE
Add Student / Edit Student / Profile now use one consistent HK-oriented form:
- **English Name*** (`firstname`) and **Chinese Name 中文姓名** (`lastname`) — validation now accepts unicode letters (was Latin-only, max 15 → max 50).
- **Date of Birth*** with live **calculated age**; **Gender** Boy/Girl.
- **Student ID*** (`registration_number`) — now **alphanumeric** (e.g. `YH2026001`; was numbers-only).
- **Mother Tongue** dropdown: Cantonese / Mandarin / English / Other.
- **School Name** and **Grade** (the student's *day school*) — two new **nullable** `userprofiles` columns (`school_name`, `grade`), migration `2026_10_28_100000` (rollback: `migrate:rollback --step=1`).
- **Manual HK address**, **Home Phone** (`alternate_no`), optional **Student Mobile** (HK **8-digit** numbers now accepted; was exactly 10) and **Email**.
- **Enrollment Date** (`joining_date`), **Class**, **Roll Number**, **Notes**; student **status** is managed by the existing block/unblock workflow.
- **Avatar optional** — the upstream gender-based default (`uploads/male.png` / `female.png`) now actually applies because the "avatar required" rule was dropped. No image libraries added.
- **Hidden (not deleted):** Blood Group, Aadhaar, Caste/Sub-caste, Siblings, EMIS, Board Registration Number, Birth/Native Place, Pincode, Transport/Driver, India location selects. All columns and stored data remain; validation accepts their absence.
- **HKID:** not added — no business requirement was stated, and we avoid storing sensitive identity data unnecessarily.

### 3. Guardian / emergency contact on the student form — DONE
- Contact **Name**, **Relationship** (Father/Mother/Guardian) and **Phone** are now on Add Student and Edit Student, and display on the profile's **Family** tab (existing UI).
- Behaviour (`StudentController::syncGuardian`, mirrors the Excel importer):
  - phone matches an existing parent account → **linked, never duplicated** (verified: two students, one parent record);
  - student already has a linked parent → that parent's details are **updated in place**;
  - otherwise a real **parent account** is created (random password — usable with the parent mobile app after a password set/reset), never a plain-text field.
- Parent accounts, student-parent links, parent web management screens and the **parent mobile API are untouched and re-verified live** (sanctum token issued post-deploy). Students without a guardian save without errors.

### 4. Simplified teacher management — DONE
- Add/Edit Teacher: **English Name / Chinese Name 中文姓名** (unicode validation), **Teacher ID** (relabeled employee ID), phone (**HK 8-digit** accepted), email, designation, joining date.
- Hidden (not deleted): Blood Group, Aadhaar, Marital Status, "Interested In". Validation relaxed to match (`TeacherProfileAddRequest`, `TeacherUpdateRequest`).
- **Assigned classes** already display on the teacher profile (upstream `TeacherShowController@showClasses` via subject/class-teacher assignments) — verified working; assignment management is the existing classes/subjects workflow. The schema requires a class teacher per class; relaxing that would be a structural change and was deliberately **not** attempted (documented limitation).

### 5. Course, class & Excel compatibility — DONE (targeted), with documented limits
- **Structure:** GegoK12 models a "course/class" as Standard × Section (`standards_link`), with optional sub-**groups** per class, one enrollment per student per academic year (`student_academics`), teachers assigned per class/subject (`teacherlink`), attendance keyed to `standardLink_id`. Nothing was altered — class IDs and attendance references are preserved.
- **Course code/name:** there is **no course-code column upstream**. Recommendation: name Standards with the centre's own codes (e.g. standard "ENG-P4" + section "A"). A dedicated code→name catalogue would be new development and **needs the client's actual course list** (not invented).
- **Multiple classes per student:** **not supported by the schema** (one `standardLink` per student per year). Class **groups** under one class are supported out of the box. Honestly documented rather than hacked.
- **Excel import** (`UsersImport`): new optional columns `school_name`, `grade`, `home_phone` (template updated); Traditional Chinese imports byte-intact; blank `country`/`state`/`city` now default to Hong Kong — this also **fixed a real bug** where a blank cell LIKE-matched `'%%'` and silently assigned the first country in the table; legacy-format rows still import unchanged; duplicate parents still linked, not duplicated; per-row transaction (no orphans) retained.

## Files changed (commit `7e17c381`)
`app/Http/Requests/{UserProfileAddRequest,UserProfileUpdateRequest,TeacherProfileAddRequest,TeacherUpdateRequest}.php` · `app/Http/Controllers/Admin/StudentController.php` (+`syncGuardian`) · `app/Traits/RegisterUser.php` · `app/Http/Resources/UserDetail.php` · `app/Models/Userprofile.php` · `app/Imports/UsersImport.php` · `app/Exports/StudentFormatExport.php` · `database/migrations/2026_10_28_100000_add_hk_fields_to_userprofiles.php` · `database/seeders/HongKongReferenceSeeder.php` · `resources/assets/js/components/{GoogleMap,parent/Edit,student/Create,student/Edit,student/profile/myprofile,teacher/Create,teacher/Edit}.vue` · 10 blades (maps tags removed) · rebuilt `public/js`, `public/css`, `public/mix-manifest.json` · `tests/Regression/hk_import_chinese_check.php` · admin guide.

## Database changes
1. Migration `2026_10_28_100000_add_hk_fields_to_userprofiles` — adds nullable `userprofiles.school_name` + `userprofiles.grade`. Backward compatible; rollback drops only these columns.
2. `HongKongReferenceSeeder` — inserts Hong Kong country/state/city **reference** rows if missing (idempotent, no demo data). Both ran on production 2026-10-28.

## Tests performed (all passed)
| Test | Where | Result |
|---|---|---|
| PHP lint, all changed files | local + production | PASS |
| Unit suite (10 tests) | local | OK (1 expected DB-guard skip) |
| Import regression (orphans/qualifications/dupes) | local | ALL PASSED |
| **NEW** HK import regression (Chinese byte-intact, new columns, HK fallback, legacy format) | local | ALL PASSED (after fixing the blank-cell country bug it exposed) |
| HK profile validation regression | local | ALL PASSED |
| Student create e2e (Chinese name/school/address, no avatar → gender default, alphanumeric ID, 8-digit phones, guardian created+linked) | local (container, clean UTF-8) | PASS, persisted byte-intact |
| Student edit e2e (grade/address/guardian phone updated **in place**, no duplicate parent) | local | PASS |
| Guardian dedupe (2nd student, same phone → same parent) | local | PASS |
| Profile endpoints (`showDetails`, `showRelations`, `editStudent`) incl. new fields | local | PASS/200 |
| Teacher validation (Chinese name, 8-digit phone, no blood group) | local | 200 |
| Attendance e2e save/retrieve re-run after all changes | local | PASS (row persisted, cleaned) |
| Webpack production build | local | Compiled successfully |
| **Production:** deploy + migration + seeder | live | DONE, clean |
| **Production:** student/teacher add pages, attendance pages | live | all 200 |
| **Production:** disposable Chinese student + guardian create → verify → **hard-delete** | live | PASS (user count back to 8) |
| **Production:** fee guards still graceful | live | 200, "Fee module is not installed." |
| **Production:** demo teacher/student logins + RBAC, wrong-password 302s, responsive meta | live | PASS |
| **Production:** parent API token issuance (after clearing the test device registration — the single-device rule itself behaved correctly) | live | PASS |
| **Production:** log sweep post-deploy | live | **0 errors** |

## Preserved / unaffected
Existing student/teacher/parent records (old fields readable; hidden, not dropped) · logins & RBAC · attendance · classes & class-teacher links · parent accounts + mobile API · Excel legacy format · fee-guard behaviour · branding · backups (fresh backup taken **before** deploying: `db-2026-10-28.sql.gz`).

## Not implemented / client input needed
- **Course code catalogue** — needs the client's real course list; current recommendation is encoding codes in Standard names.
- **Multiple simultaneous class enrollments per student** — schema limit; not safe to shortcut.
- **HKID field** — skipped deliberately (sensitive data, no stated requirement).
- **Client's real spreadsheet** — import tested with a synthetic fixture; send one anonymised real sheet to confirm the column mapping before bulk import.
- ~~Known upstream behaviour: students created through the admin UI receive the default password `password`~~ — **FIXED 2026-10-28** (commit `8746267a`): all creation paths now assign cryptographically random passwords; accounts are activated with `php artisan yh:set-password <email|mobile>` or (once SMTP exists) the email reset flow. See admin guide §2b.
- Scope exclusions remain: no credits/deductions, renewal alerts, calendars, paid Fee/Exam add-ons, invoices, mobile app builds, automated WhatsApp, redesigns.

## Disk usage
Local repo: 1.2 GB before and after (built assets replaced in place). Server: `/var/www/yheducation` 498 MB; root disk 9% used. No duplicate release archives created; the deployment tar was deleted from `/tmp` after extraction.
