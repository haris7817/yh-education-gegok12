# Existing GegoK12 Feature Verification — Core vs Paid Add-ons vs Our Branch

_2026-10-27/28 · Evidence from code inspection + live tests (local stack & production). Rule applied: a model, route, or menu item is NOT counted as a working feature without a functional test or a traced code path._

## 1. The add-on architecture (why this matters)

GegoK12's open-source core ships with an add-on marketplace (`ADDON_API_URL`, `routes/addon.php`). At least two major modules are **paid vendor packages**, absent from the MIT core:

- **`Gegok12\Fee`** — fee structures, fee payments. Referenced only inside `class_exists()` guards (`app/Http/Controllers/Teacher/StandardsLinkController.php:312`, `app/Traits/AdmissionUser.php:181`).
- **`Gegok12\Exam`** — exams, schedules, marks, grades (`app/Traits/Dashboard.php:134-209`, `app/Schoolplus/StudentService.php:198-305`, `app/Observers/AcademicYearObserver.php:44-68`, `app/Traits/RegisterUser.php:757`).

The core is **inconsistently guarded**: some code paths check `class_exists()` and degrade gracefully; others import `App\Models\Fee` (which does not exist anywhere) and **fatal** when executed.

## 2. Verified feature matrix

| Feature | In open-source core? | State on our branch / production | Evidence |
|---|---|---|---|
| Auth, roles, RBAC | Yes | **Working** | Live logins all roles; unauth URLs 401 (2026-10-27) |
| Student CRUD + Excel import | Yes | **Working** (importer bug fixed by us) | Import regression ALL PASSED; CRUD pages live |
| Teacher/staff management | Yes | **Working** | Live |
| **Attendance** (absentee model, forenoon/afternoon) | Yes | **Working — functionally proven 2026-10-28**: HTTP save → DB row → list; export + history routes | `attendance_e2e` test; `attendances` row verified & cleaned |
| Timetable, assignments (+marks per assignment), lesson plans (+PDF), promotions, events, bulletins, leave | Yes | Present; pages render; not individually POST-tested | Routes + controllers traced; dashboard live |
| Payroll/payslips (staff) + PDF | Yes | Present (HK$ symbol wired by us) | `PayrollController` PDF::loadView; S-3 |
| ID-card PDF, visitor-log PDF, student-export PDF | Yes (dompdf 3.1.1) | Present | Controller trace |
| **Fee management** | **No — paid add-on** | **Absent.** Dashboard fee widgets render but drill-downs crash: authenticated `/admin/dashboard/feeslist/1` and `/admin/dashboard/unpaidList/null` → **HTTP 500**; log: `Class "App\Models\Fee" not found` (2026-10-28, local stack = same code as prod). No `fees` migrations; no Fee model; `routes/setting.php:26` upstream comment "error in fees addon" | Reproduced + logged |
| **Invoices / PDF receipts** | **No** — zero "invoice"/"receipt" occurrences in `app/`, `routes/`, `resources/views/` | Absent | grep audit |
| **Exams / marks / gradebook / report cards / analytics** | **No — paid add-on** | Absent; guarded pages degrade (`marksGraph` → `return back()`); no report-card template exists | Code trace |
| **Parent web portal** | **No** — parents are mobile-app users by design | Absent (upstream design) | `KNOWN_LIMITATIONS.md` §1 |
| Parent/Teacher mobile **APIs** | Yes (sanctum) | **Endpoints live on production** (`/api/schools/list` 200, `/api/school/info` 200, `/api/parent/login` responds); fee/exam API routes commented out upstream | curl probes 2026-10-27 |
| Mobile **app binaries** | **No** — `public/android-apk/` not in repo; `/api/apk/parent` returns empty 200 | Absent | Live probe |
| Add-on installer routes | Stub only (crashed `route:cache` upstream) | Guarded by us (S-6) | `routes/addon.php` |

## 3. What we changed (full diff of intent, branch `yh-education-delivery`)

Configured: YH branding (name/logo/favicon/slogans/school profile EN+中文), HK$ currency config, WhatsApp country code 852, timezone, production env, reference-only seeding, deployment/backup/TLS tooling.
Custom code (all minor, approved): importer orphan fix (S-5), addon-route guard (S-6), WhatsApp click-to-chat button (S-7), currency config keys (S-3), HK-friendly validation (`DetailRequest`), slogan rendering on login, logo fallback in `logo.blade.php`, test-safety guard (`tests/TestCase.php`).
**Nothing was disabled or removed.** No upstream feature broke from our changes — the fee 500s exist in stock upstream code (missing paid add-on), reproducible on a clean upstream checkout.

## 4. Corrections to earlier delivery reports

1. `YH_EDUCATION_FINAL_DELIVERY_CHECKLIST.md` row "Fee management (existing) — DONE\*" and `YH_EDUCATION_100_SCOPE_AUDIT.md` §G described fee functionality as "present and wired… PASS (access)". **Corrected:** fee routes exist but the module behind them is the paid add-on; drill-down endpoints 500. The earlier "PASS" measured page rendering, which this audit's own rule rejects as proof.
2. "Fee pages with your real data volume" in the client UAT list implies usable fee pages; the UAT list is superseded by `FINAL_UAT_CHECKLIST.md`, which marks fee flows **not available without the add-on**.

## 5. Possible cheap mitigation (not implemented — needs approval)

Guarding the two crashing dashboard endpoints the same way upstream guards the teacher path (`class_exists('Gegok12\Fee\Models\Fee')` → return an empty list) would turn admin-dashboard fee clicks from a 500 into a graceful "no fee records" response: ~1–2 h, arguably within defect-support spirit since it's a crash reachable from the delivered UI.
