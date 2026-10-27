# YH Education — Final UAT Checklist (Client-Facing)

_Site: **https://yheduhk.duckdns.org** · Please tick each item Pass/Fail and note anything unexpected. Items only you can do (real browser, real phone) are marked 👤. Allow ~30–40 minutes._

**Status key used in the right column — what we already verified for you:**
✅ verified by us on the live site · 🧪 verified by automated test on identical code · ⬜ needs your confirmation · 🚫 not available in this edition (see notes)

## A. Access & security
| # | Test | Steps | Expected | Our status |
|---|---|---|---|---|
| A1 | HTTPS | Open the site; check the padlock | Valid certificate, no warnings | ✅ |
| A2 | Admin login | Log in with your admin email + password (from the credentials file) | Admin dashboard loads | ✅ |
| A3 | Wrong password | Try a wrong password once | Error message, no crash | ✅ |
| A4 | Logout | Log out, press Back | Dashboard not accessible without login | ✅ |
| A5 👤 | Change admin password | Profile → change password → re-login | Works with the new password | ⬜ |

## B. Branding & language
| # | Test | Steps | Expected | Our status |
|---|---|---|---|---|
| B1 | Logo & slogans | Look at the login page | 言希教育 logo; English + 中文 slogans | ✅ |
| B2 | School details | Admin → School Details | Name, EN+中文 address, +852 phone, email correct | ✅ |
| B3 👤 | Chinese via form | Edit the 中文 slogan slightly, save, reload, restore it | Chinese saves correctly from your browser | ⬜ |
| B4 👤 | Phone layout | Open the site on your phone | Pages readable, no broken layout | ⬜ |

## C. Core school workflow (create real starting data as you go)
| # | Test | Steps | Expected | Our status |
|---|---|---|---|---|
| C1 👤 | Create class & section | Admin → Standards/Classes → add one | Saved and listed | ⬜ |
| C2 👤 | Create a teacher | Add teacher with email+password; log in as them in a private window | Teacher sees own dashboard; cannot open /admin pages | ✅ (tested with disposable account) / ⬜ repeat with your real one |
| C3 👤 | Create a student | Add student assigned to the class | Appears in students list | ⬜ |
| C4 👤 | Excel import (optional now) | Download template, import 2–3 test rows | Students + parents created, duplicates linked | 🧪 regression-tested |
| C5 👤 | **Attendance** | Attendance → Add → pick class, date, session (forenoon/afternoon), mark an absentee → save → reload list | "Attendance Added Successfully"; absentee appears in list/history | 🧪 end-to-end save proven 2026-10-28 |
| C6 👤 | Attendance duplicate guard | Submit the same class/date/session again | Rejected: "Attendance already updated" | 🧪 |
| C7 👤 | WhatsApp button | Open a parent record → "Chat on WhatsApp" | WhatsApp opens with the right +852 number | ✅ |
| C8 👤 | Assignments (optional) | Teacher → create assignment → enter marks for your test student | Marks saved and visible | ⬜ |

## D. Not available in this edition — do NOT test
| Area | Why |
|---|---|
| 🚫 Fee records, invoices, PDF receipts | Fee module is a paid GegoK12 add-on, not in the open-source core. **Avoid the fee links on the admin dashboard** — two of them produce an error page in the stock code. See `REMAINING_WORK_AND_ESTIMATES.md` for options |
| 🚫 Exams, gradebook, report cards, marks analytics | Paid GegoK12 add-on (same options) |
| 🚫 Parent web login | Parents use the vendor's mobile app (see mobile feasibility doc); no web portal exists in GegoK12 |
| 🚫 Email notifications / password-reset emails | Off until you provide an SMTP account (then a 10-minute config) |

## E. Operations
| # | Test | Steps | Expected | Our status |
|---|---|---|---|---|
| E1 | Nightly backup | (Done for you) | Daily DB+uploads in /var/backups/yhedu, 14-day retention | ✅ files verified |
| E2 | Restore drill | (Done for you) | Backup restored into an isolated test DB: 113 tables, data intact | ✅ 2026-10-27 |
| E3 👤 | Sign-off | When A–C pass to your satisfaction | Reply confirming acceptance → we revoke the temporary sudo access | ⬜ |

**Found a problem?** Anything in sections A–C that fails is covered by the 7-day defect support — report it with a screenshot and the time it happened.
