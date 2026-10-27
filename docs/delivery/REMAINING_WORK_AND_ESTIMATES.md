# Remaining Work & Estimates

_2026-10-27 · Split into: (A) needed for $100 acceptance, (B) small goodwill fixes, (C) separate paid work / client decisions. Hours are engineering estimates, not quotes._

## A. Needed to close the $100 engagement

| Item | Missing functionality | Root cause | Type | Est. | Risk | Dependencies |
|---|---|---|---|---|---|---|
| A1. Three functional test accounts (teacher, student, parent) | ~~Prod has only admin + 4 locked demo staff~~ **DONE 2026-10-28** — demo teacher/student/parent + demo class created and login/RBAC-verified live (P-17/P-18); credentials in `/home/deploy/YHEDU_DEMO_ACCOUNTS.txt` | — | Configuration | done | — | — |
| A2. Client UAT + sign-off | Client confirmation of acceptance | Process step | Client action (we support) | — | Low | `FINAL_UAT_CHECKLIST.md`; admin credentials pickup |
| A3. Revoke temporary sudo | — | Awaiting client confirmation (by agreement) | Client action | 5 min | Low | A2 |

## B. Small defect-support candidates (recommend doing; needs approval since it's a code change)

| Item | Detail | Type | Est. | Risk |
|---|---|---|---|---|
| B1. Guard the crashing fee endpoints | **DONE 2026-10-28** (commit `898da2e4`, deployed, P-16): all reachable fee endpoints that referenced missing paid-add-on classes (`feeslist`, `unpaidList`, `structuralList` ×2, `sendReminder`, admin/teacher `standardLink show fees`, teacher student fees, fee CSV export) now return a graceful empty response with "Fee module is not installed." | Code (defensive) | done | — |
| B2. SMTP configuration | Password-reset/notification emails off until client supplies an SMTP account | Configuration | 0.5 h | Low — must never enable `MAIL_STATUS=on` without working SMTP |

## C. Separate paid work / client decisions (outside the $100 agreement)

| Item | Option 1 (vendor) | Option 2 (custom build) | Needed for $100 acceptance? |
|---|---|---|---|
| C1. Fees, invoices, PDF receipts | Buy GegoK12 **Fee add-on** from gegok12.com (price unknown — client asks vendor), then ~2–4 h install/config/test | Custom fee structures + assignment + payment recording + printable PDF invoice/receipt on the existing dompdf stack: **~25–40 h**, medium risk (money-handling logic, edge cases) | **No** — recorded exclusions list "PDF invoice workflows" |
| C2. Exams / gradebook / report cards / analytics | Buy GegoK12 **Exam add-on**, ~2–4 h config | Custom exams + marks + grade boundaries + report-card PDF + charts: **~40–60 h** (+10–20 h for polished bilingual report cards), medium-high risk | **No** — paid add-on; "custom reports" excluded |
| C3. Mobile app connection (Teacher/Parent) | **Step 0 (free): client emails GegoSoft** — "how do the Play-Store apps select a server; do you provide builds for self-hosted installs?" If supported: ~4–8 h (APK metadata, FCM push keys, device testing) | Building/fixing apps ourselves: 80+ h — not recommended | **No** — "native mobile apps" recorded as excluded; vendor-gated regardless of scope reading |
| C4. Fee/exam API routes for apps | Included in C1/C2 outcomes (routes are commented out pending add-ons) | — | No |
| C5. Centre-wide WhatsApp button, prepaid lesson credits, renewal alerts, scheduling calendar, custom ledgers, automated WhatsApp, theme colours, dedicated 中文 address field | — | Quoted separately on request (as already listed in `YH_EDUCATION_PENDING_CLIENT_ITEMS.md`) | No |

## Decision needed from the client

1. Produce the original Fiverr order text if they believe items in C were included — classification will be revisited against it.
2. Choose vendor-add-on vs custom-build vs defer, for C1 and C2.
3. Send the free vendor inquiry for C3.
4. Approve B1 (recommended) and schedule A1+A2.
