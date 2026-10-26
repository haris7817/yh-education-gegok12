# YH Education — Pending Client Items

_What we need from YH Education to finish. Everything not listed here is complete and verified locally (see `YH_EDUCATION_100_SCOPE_AUDIT.md`). Updated 2026-10-26._

## Blocking deployment (nothing can go live without these)
| # | Item | Detail |
|---|---|---|
| 1 | **SSH access to the Oracle server** | Server `129.150.50.78` (Ubuntu 24.04 Minimal ARM64) is running but access is unconfirmed. Authorize this **public** deploy key for the `ubuntu` user: `ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIJ0jC0ypy920OL2bVNWx6JGthZ/h7oNdyIjEfqamO/Kg yhedu-deploy-20261025` — then we deploy per `YH_EDUCATION_DEPLOYMENT_GUIDE.md`. (We will never ask for passwords or private keys.) |
| 2 | **Domain** | The preferred label is **yhedu** — confirm the exact domain/subdomain (availability not assumed) and provide DNS access, so HTTPS can be issued. |
| 3 | **SMTP (email) account** | Needed for password-reset and notification emails (e.g. a transactional provider or Gmail SMTP for `yh.eduhk@gmail.com`). Without it the app runs fine but sends no email. |

## Needed to complete branding
| # | Item | Detail |
|---|---|---|
| 4 | **Official logo file** (PNG/JPG) | Uploaded in two places (School Details → School Logo; Settings → site logo/favicon). The login page currently shows the upstream GegoK12 placeholder image until supplied. No logo was invented. |
| 5 | **Colour preference** (optional) | There is **no built-in theme-colour setting** in GegoK12 — custom colours would be separate approved work. Confirm if the default theme is acceptable for launch. |

## Needed before real data goes in
| # | Item | Detail |
|---|---|---|
| 6 | **Real student spreadsheet** + sign-off | Provide the ~60-student Excel (we map it to the verified template), confirm data-protection expectations and the backup arrangement. Only synthetic data has been used so far. |
| 7 | **WhatsApp number confirmation** | Is `+852 54001530` the centre's WhatsApp? The delivered button chats with **each parent's** stored number (in scope). A single centre-wide contact button would be a small separate addition — tell us if wanted. |

## One-time checks at go-live (10–15 minutes together, UAT)
| # | Check | Why |
|---|---|---|
| 8 | Mark attendance for one class, reload, confirm saved | The save click-through is the one flow not machine-verified |
| 9 | Edit the slogan (Chinese text) in School Details, save, reload | Confirm Chinese survives the browser form (the data layer verifiably preserves it; our automated harness couldn't send clean Chinese) |
| 10 | Open the site on a phone | Physical responsiveness pass |
| 11 | Trigger a password-reset email | Confirms SMTP (item 3) |
| 12 | Re-open the fee/timetable report pages | Re-time with real data volume (slow only on our oversized demo dataset) |

## Decisions already yours to make later (not blockers)
- **Parent access:** parents have no web portal by GegoK12 design; the vendor's parent **mobile app** is not bundled in the open-source core — obtain from gegok12.com if wanted.
- **Future features** (quoted separately if desired): prepaid lesson credits & deductions, renewal alerts, scheduling calendar, invoices/ledgers, automated WhatsApp, custom reports, theme colours, dedicated Chinese-address field, centre-wide WhatsApp button.
