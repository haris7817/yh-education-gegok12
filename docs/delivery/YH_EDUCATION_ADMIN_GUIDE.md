# YH Education — Administrator Guide

_A plain-language guide for the YH Education team. Every feature described here was verified working on 2026-10-26; items needing one-time confirmation on your live site are marked. Technical detail lives in `ADMIN_GUIDE.md`._

## 1. Logging in
1. Open the site and you land on the **login page** (it shows the YH Education name and both slogans).
2. Enter your **email and password** → you arrive at the dashboard for your role:
   - **Administrator** → full admin dashboard
   - **Teacher** → teacher dashboard
   - **Student** → student dashboard
3. People only see their own area — a teacher or student who tries an admin page is simply sent back to their own dashboard.
4. **Log out** from the menu at the top right.
5. Forgot password? Use **"Forgot Your Password?"** on the login page (email sending starts working once the live server's email service is configured).
6. ⚠️ **First thing after go-live:** change the administrator password (the installed one is a placeholder) and remove the sample staff accounts, as listed in the deployment guide.

## 2. Managing students
- **See all students:** Students menu → the student list (searchable, by class).
- **Add one student:** Students → **Add Student**. The form is simplified for Hong Kong (updated 2026-10-28):
  - **English Name** (required) and **Chinese Name 中文姓名** (optional) — both save and display correctly;
  - **Date of Birth** shows the calculated **age** as you type; **Gender** Boy/Girl;
  - **Student ID** (your own code, e.g. `YH2026001` — letters and numbers allowed);
  - **Mother Tongue** dropdown (Cantonese / Mandarin / English / Other);
  - **School Name** and **Grade** — the student's *day school* (you are a tutorial centre);
  - **Address** is typed manually (Chinese or English, no Google Maps needed);
  - **Home Phone / Student Mobile / Email** — all optional; HK 8-digit numbers accepted;
  - **Photo is optional** — a neutral default is used based on gender;
  - **Guardian / Emergency Contact** — name, relationship and phone right on the form. If the phone matches an existing parent, the student is **linked** to that parent (no duplicates); otherwise a parent record is created automatically.
  - Choose the **class** and roll number, set the **Enrollment Date**, save. (Classes must exist first — see §4.)
- Indian-specific fields (blood group, caste, Aadhaar, siblings, EMIS, transport) are no longer on the form; old data is preserved in the database.
- **View / edit:** click a student to open their record; the profile shows the day school, grade, mother tongue, home phone and the guardian under the **Family** tab; use Edit to change anything, including the guardian contact.
- **Parents:** you can still manage full parent records in the **Parents** section — guardian contacts created from the student form appear there too.

## 3. Managing teachers
- **Teachers** menu → list of teachers; **Add Teacher** to create one with their login email.
- The teacher form is simplified too (2026-10-28): **English Name**, **Chinese Name 中文姓名**, **Teacher ID**, phone (HK 8-digit fine), email, designation and joining date. Blood group, Aadhaar, marital status and "interested in" are no longer asked.
- A teacher's profile shows their **assigned classes** (from the class-teacher and subject assignments you make under classes/subjects).
- Teachers then sign in themselves and see their own dashboard, classes and attendance tools.

## 4. Classes and sections
Before adding or importing students, create your **classes/sections** (e.g. Class 1 Section A) in the classes area and assign a class teacher. Students always belong to a class+section.

## 5. Attendance
- Admin: **Attendance → Add** to mark attendance; **Attendance → List** to review records. Teachers mark attendance for their own classes from the teacher dashboard.
- ⚠️ One-time check at go-live: mark attendance for one class, reload, and confirm it saved (this save step is the one flow we ask you to click through once on the live site).
- Note: prepaid lesson credits / automatic deductions are **not** part of this system (they were discussed as possible future work).

## 6. Importing students from Excel
1. In the student import area, **download the sample template** — the import needs its exact column headings.
2. Fill one row per student. The essentials:
   - `class` and `section` must match classes you created in §4 (e.g. class `1`, section `A`) — rows that don't match are **skipped and reported**, nothing half-saved;
   - `parent_mobile_no` identifies the parent — siblings with the same number are linked to **one** parent (no duplicates);
   - `parent_qualification`: ideally one exact qualification name from the system; if blank or unrecognised the parent is still created, just without a qualification;
   - dates like `date_of_birth` in a normal date format;
   - **new optional columns** (2026-10-28): `school_name` (day school), `grade`, `home_phone` — leave blank if unknown;
   - Chinese text (names, addresses) imports correctly;
   - `country`/`state`/`city` may be left blank — they default to Hong Kong.
3. Upload. The app tells you how many rows were imported and which were skipped. Check the new students appear under the right classes.
4. 🔒 Import real student data only after the format and backup arrangements are agreed.

## 7. Fees
The existing fee features are available from the admin dashboard (fee summaries) and per class/student fee pages; the currency shows as **HK$**. Custom invoicing/ledgers are not included. If a fee report page feels slow on day one, tell us — on the live server with your real student numbers it should be quick.

## 8. School branding
- **Name, address, phone, email, slogans** are already set to YH Education (address in English + 中文; slogans shown on the login page in both languages).
- **To change them:** admin → School Details → edit. The slogan field (`School Moto`) holds both slogans separated by `|` — e.g. `English slogan | 中文口號` (up to 150 characters). ⚠️ After editing Chinese text in this form once, reload and confirm it saved correctly (a one-time check we ask for).
- **Logo:** when you have your official logo (PNG/JPG), upload it in School Details (**School Logo**) — and the site logo/favicon under Settings → General Settings. No logo has been invented on your behalf.

## 9. WhatsApp contact button
- Open any **parent's detail page** — next to their mobile number is a green **"Chat on WhatsApp"** link. Click it to open a WhatsApp chat with that parent (WhatsApp Web on a computer, the app on a phone). You type the message yourself — nothing is ever sent automatically.
- Parents' numbers are stored without a country code, so the system adds **+852** automatically. To change the default country code, set `WHATSAPP_COUNTRY_CODE` in the server's `.env` file (ask your technical contact). Numbers already in international format are left untouched; if a parent has no usable number the button is hidden.

## 10. Day-to-day safety
- Back-ups run nightly on the server (database + uploaded files) — keep an occasional offsite copy too.
- Never share the admin password; create separate accounts for staff.
- The "parent app": parents do not have a web login in this system by design — GegoK12 offers a separate parent mobile app from its vendor if you want parent access later.
