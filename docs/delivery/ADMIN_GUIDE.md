# Administrator & Teacher Guide — YH Education (GegoK12)

_Covers the features verified during delivery. Where a feature was only partially tested, it is clearly marked. Nothing here is claimed to work that was not checked._

---

## 1. Signing in
- Open the site URL; you are redirected to **/login**.
- Enter email + password. Each role lands on its own dashboard:
  - **Administrator** → `/admin/dashboard`
  - **Teacher** → `/teacher/dashboard`
  - **Student** → `/student/dashboard`
- Roles are kept separate — a teacher/student cannot reach the admin area (verified).
- **Logout** is in the top-right user menu.

> Role separation and all three logins were tested and pass.

## 2. Roles available (seeded)
Administrator (SchoolAdmin), Teacher, Student, Parent, plus Librarian, Receptionist, Accountant, Stock Keeper. Parents authenticate but have **no web dashboard** — see `KNOWN_LIMITATIONS.md`.

## 3. Administrator tasks (verified to open and render)
- **Dashboard** (`/admin/dashboard`) — student/teacher/fee summary widgets.
- **Students** (`/admin/students`) — student management list.
- **Classes** (`/admin/standardlinks`) — class/section (standard-link) list.
- **Attendance** (`/admin/attendance/list`, `/admin/attendance/add`).
- **Parents** (`/admin/parent/...`) — manage parent records; parent detail page includes the WhatsApp button (§6).

> **Attendance, timetable and fees exist and are wired in.** Attendance pages and the admin/student/class lists were loaded successfully. The **per-class timetable and fee detail/report pages render but were slow over the large demo dataset** (see `KNOWN_LIMITATIONS.md`); they should be re-verified on the production host with real data volume before sign-off.

## 4. Teacher tasks
- Teacher dashboard (`/teacher/dashboard`) loads (verified).
- Teacher-specific features (attendance entry, lesson/schedule views) are present in the application and belong to the teacher role; verify the specific day-to-day flows the centre will use during the walkthrough on the live site.

## 5. Student Excel import (verified)
**Where:** Admin student area → import (uses the built-in importer).

**Template:** download the sample format from the app (it produces the exact 50-column header the importer expects: `firstname, lastname, mobile_no, email, gender, date_of_birth, blood_group, class, section, address, city, state, country, pincode, …, parent_firstname, parent_lastname, parent_mobile_no, parent_qualification, …, relation`).

**Rules that make an import succeed (verified by testing):**
- `class` must match an existing class (e.g. `1`–`12`, or `prekg/lkg/ukg`) **and** `section` (`A`/`B`) must exist as a configured class-section; rows that don't match are **skipped and reported** (they do not create partial records).
- `parent_mobile_no` is the parent identifier — reusing the same number **links** children to one existing parent (no duplicate parent).
- **`parent_qualification`** should be a single qualification name that matches one entry in the system (e.g. "Bachelor of Commerce (B.Com.)"). Blank or unknown values are now handled safely — the parent is still created without a qualification (previously this silently dropped the parent; **fixed and tested**).
- Dates (`date_of_birth`, `joining_date`) in a standard date format.

**After import:** the app reports how many rows were inserted and which were skipped. Verify the new students appear under the correct class.

> Tested with synthetic records: inserts, skips, duplicate-parent linking, and blank/unknown qualifications all behave correctly, with **no orphaned students**. **Do not import real student data** until the format, data-protection and backup approach are confirmed.

## 6. Manual WhatsApp chat button (new, verified)
- On a **parent's detail page** (`/admin/parent/show/{name}`), a green **"Chat on WhatsApp"** link appears next to the parent's mobile number.
- Clicking it opens **wa.me** in a new tab for that parent's number, ready for you to type a message manually.
- The number is normalised to international format using the configured country code (**Hong Kong 852** by default); numbers already in international form are not double-prefixed. If a parent has no usable number, the button is hidden.
- **This is a manual click-to-chat only** — it never sends anything automatically. There are no automated WhatsApp reminders, templates, or scheduling.

## 7. School branding & configuration
- **School name:** set to **YH Education** (the school profile record and `APP_NAME`).
- **Currency:** configurable via `.env` `CURRENCY_SYMBOL` (set to **HK$**) → used on payslips. Student fee amounts display as plain numbers in the core app.
- **Timezone:** `Asia/Hong_Kong` (`.env` `TIMEZONE`).
- **Logo / slogan / colours:** supply the official assets; apply via the app's built-in school settings. Placeholders are used until provided — no official logo/slogan was invented.

## 8. Security & passwords (important)
- The seeded accounts use a default demo password and are **synthetic** — in production, **change the administrator password on first login** and remove/disable demo accounts.
- Keep `APP_DEBUG=false` and `APP_ENV=production` live.
- Never expose phpMyAdmin or the database port publicly (phpMyAdmin is local-dev only).
- See `DEPLOYMENT.md` §3.9 for the full go-live security checklist.
