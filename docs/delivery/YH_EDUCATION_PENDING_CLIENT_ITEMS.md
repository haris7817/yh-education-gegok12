# YH Education — Pending Client Items

_Updated 2026-10-27 — **the site is LIVE at https://yheduhk.duckdns.org**. Everything below is what remains._

## Handover (now)
| # | Item | Detail |
|---|---|---|
| 1 | **Admin credentials** | Login: `yh.eduhk@gmail.com`. The generated password is on the server in `/home/deploy/YHEDU_CREDENTIALS.txt` (permissions 600; also holds the DB password). Retrieve it over SSH, store it in your password manager, then delete the file: `shred -u /home/deploy/YHEDU_CREDENTIALS.txt`. Change the password after first login if you prefer your own. |
| 2 | **Client acceptance test** | Log in, click through students/teachers/attendance, confirm all good (UAT list below). |
| 3 | **Revoke temporary sudo** (after you confirm the site works) | From the `ubuntu` admin account: `sudo rm /etc/sudoers.d/deploy` — the deploy user then returns to non-privileged access. **Not revoked yet, by agreement, pending your confirmation.** |
| 4 | **Demo test accounts** (added 2026-10-28) | Three working demo accounts (teacher, student, parent) + one demo class exist for your acceptance testing. Credentials: `/home/deploy/YHEDU_DEMO_ACCOUNTS.txt` (permissions 600) — retrieve over SSH, then `shred -u` the file. All three logins + role boundaries verified live. The parent account works through the mobile API (parents have no web portal in GegoK12). Delete or re-password these accounts after UAT. |

## 10–15 minute UAT (live site)
1. Create a class/section, add a teacher, add a student — then **mark attendance** for that class, reload, confirm saved.
2. Edit the school slogan (Chinese text) in School Details, save, reload — confirm 中文 saves through the browser form.
3. Open the site on a phone (responsiveness).
4. Open a parent record → click **Chat on WhatsApp** → confirms the right number opens.
5. Fee pages with your real data volume.

## Optional configuration (not blockers — site runs without them)
| # | Item | Detail |
|---|---|---|
| 4 | **SMTP (email)** | Password-reset / notification emails are OFF until supplied. Then: edit `/var/www/yheducation/.env` → `MAIL_MAILER=smtp`, host/port/user/pass, `MAIL_STATUS=on`, run `php artisan config:cache`. ⚠️ Never set `MAIL_STATUS=on` without working SMTP (logins would error). |
| 5 | ~~Official logo~~ **DONE 2026-10-27** | Client logo (言希教育 badge) deployed: login page, sitelogo + favicon settings, school-profile logo. Replaceable anytime via Settings → General Settings. |
| 6 | **Real student data** | Import the ~60 students via the Excel template (admin guide §6) once classes exist. Backups run nightly beforehand. |
| 7 | **WhatsApp centre number** | The per-parent chat button is live. A single centre-wide contact button would be a small separate addition — confirm if wanted. |
| 8 | **Domain note** | `yheduhk.duckdns.org` is a free DuckDNS name — fine to run on; a paid custom domain can be pointed at 129.150.50.78 later (one certbot command adds it). |

## Backup & recovery (already running)
Nightly 02:15: database dump + uploads → `/var/backups/yhedu/` (14-day retention; first backup verified). Restore commands are inside `/usr/local/bin/backup-yhedu` header and `YH_EDUCATION_DEPLOYMENT_GUIDE.md`. Recommend an occasional offsite copy.

## Future paid work (explicitly out of the $100 scope — quoted separately if desired)
Prepaid lesson credits & deductions · renewal alerts · scheduling calendar · invoices/ledgers · automated WhatsApp · parent mobile app (vendor) · custom reports · theme colours · dedicated 中文 address field.
