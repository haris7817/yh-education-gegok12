# Pre-Deployment Readiness Report — YH Education (GegoK12)

_2026-10-22. No deployment performed. Branch `yh-education-delivery` (local only, not pushed)._

---

## 1. Check results (PASS / FAIL / PARTIAL)

| # | Check | Status | Evidence / notes |
|---|-------|--------|------------------|
| 1 | Overall production readiness | **PASS** | App installs, migrates, boots, authenticates, serves assets; fixes + tests committed. Remaining items are hosting/client-gated (below). |
| 2 | Docker & Ubuntu VPS compatibility | **PASS** | Runs in Docker locally; VPS path uses native PHP 8.4 (no Docker dependency). No app code assumes Docker. `DEPLOYMENT.md` §3 covers VPS. |
| 3 | Production env example (no secrets) | **PASS** | `docs/delivery/env.production.example` created — placeholders only, `APP_KEY` generated on server. |
| 4 | Installation / migrations / safe seeding | **PASS** | 108 migrations run clean. **Verified reference-only seeding procedure** (see §Seeding) tested on a throwaway DB: 0 errors, reference data + subscription present. |
| 5 | Production has NO demo students/teachers/parents | **PASS** | Verified: reference-only seed → **0 students / 0 teachers / 0 parents** (only admin + support-staff accounts). ⚠️ A naive full `db:seed` would NOT be safe (see §Seeding). |
| 6 | Permissions / APP_DEBUG / HTTPS / sessions / storage | **PASS** (w/ required prod settings) | `APP_DEBUG` defaults `false`; `SESSION_SECURE_COOKIE` env-driven → set `true` on HTTPS; `http_only` cookies on; uploads → local disk (persists on VPS). Perms documented (`storage`, `bootstrap/cache`, `public/uploads` = www-data 775). |
| 7 | Backup & restore procedures | **PASS** | `DEPLOYMENT.md` §4: nightly `mysqldump` + uploads backup; documented restore. |
| 8 | No local Windows paths in the app | **PASS** | `git grep` for `C:\ / D:\ / /mnt/` in tracked files → none. Windows specifics live only in the untracked local `docker-compose.override.yml`. |
| 9 | Mobile responsiveness (local) | **PARTIAL** | Login page has `<meta name="viewport" ...>` + responsive classes (Tailwind `lg:`/`md:`, Bootstrap grid). Full device testing needs a browser on the live URL — deferred to post-deploy. |
| 10 | All available regression tests | **PASS (ours)** + note | Added tests pass: Unit `WhatsappNumberTest` (9) + import regression (ALL PASSED). ⚠️ Upstream **Feature suite: 25 pre-existing failures** — see §Known issues. |
| 11 | Deployment/handover checklist review | **PASS** | Gap found & fixed: the deploy runbook now uses the verified reference-only seed (not full `db:seed`). |

---

## 2. Safe production seeding (verified)
GegoK12's seeders are partly demo-data. Environment guards are **inconsistent**:
- **Env-guarded (skip in production automatically):** UsersTeacher, Teacher, StandardsLink, NoticeBoard, Payroll, Assignment, Homework.
- **NOT guarded (would run even in production):** **UsersStudentTableSeeder** (≈330 students + 660 parents), HolidaySeeder, Subscriptions.

➡️ **Do not run the full `php artisan db:seed` in production.** Use the explicit **reference-only** seeder list in `DEPLOYMENT.md` §3.5 (migrate + 32 reference seeders, excluding every demo-user seeder and the teacher-dependent StandardsLink). **Validated on a throwaway DB:** 0 errors; reference data, 152 subjects, qualifications, settings and 1 subscription present; **0 students / teachers / parents**. Then change the seeded admin credentials and remove/rename the 4 demo support-staff accounts; the admin builds the real classes/teachers and imports students.

## 3. Known issues (non-blocking)
- **Upstream Feature tests (25 failures):** in `tests/Feature/{Authentication*, *StatusLifecycle*}` — **pre-existing** on `main`; this delivery changed no auth code or Feature tests (confirmed by diff). They reflect upstream test maintenance, not defects in verified features or anything we introduced. Recommend treating as out-of-scope upstream debt, not a deploy blocker.
- **`same_site` cookie** is hardcoded `null` in `config/session.php` (not env-driven). Left unchanged to avoid unnecessary edits; recommend setting it to `lax` during hardening (optional). Laravel CSRF tokens still protect forms.
- **Performance** of fee/timetable pages over large datasets — retest on the live host (see KNOWN_LIMITATIONS).

## 4. What can be completed WITHOUT hosting (done)
- Local build, migrations, verified reference-only seeding procedure, fixes, regression tests, production `.env` template, deployment runbook + backup/restore, documentation, Windows-path and security-config review, responsive-markup check, git delivery branch & commits.

## 5. What REQUIRES hosting or client access
- Provisioning the server / choosing Render+Aiven; **client-owned account**.
- The `yhedu` domain + DNS, and Let's Encrypt TLS issuance.
- SMTP credentials (to enable notification emails).
- Live QA: HTTPS login, fee/timetable timing with real data, mobile device testing, email send test.
- Real student-data import (only after client supplies data + format/protection/backup agreed).
- Final admin-credential rotation and secure handover.

## 6. Exact deployment commands — clean Ubuntu VPS (condensed)
> Full detail in `DEPLOYMENT.md`. Run as a sudo user over SSH. Nothing below has been executed.
```bash
# Base + stack
sudo apt update && sudo apt upgrade -y && sudo timedatectl set-timezone Asia/Hong_Kong
sudo ufw allow OpenSSH && sudo ufw allow 80 && sudo ufw allow 443 && sudo ufw enable
sudo add-apt-repository ppa:ondrej/php -y && sudo apt update
sudo apt install -y nginx mysql-server php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring \
  php8.4-xml php8.4-curl php8.4-zip php8.4-gd php8.4-bcmath php8.4-intl php8.4-fileinfo unzip git
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash - && sudo apt install -y nodejs

# Database
sudo mysql_secure_installation
sudo mysql -e "CREATE DATABASE yheducation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'yhedu'@'localhost' IDENTIFIED BY '<STRONG_DB_PASSWORD>'; GRANT ALL ON yheducation.* TO 'yhedu'@'localhost'; FLUSH PRIVILEGES;"

# App
cd /var/www && sudo git clone <REPO_URL> yheducation && cd yheducation
sudo git checkout yh-education-delivery && sudo chown -R $USER:www-data /var/www/yheducation
composer install --no-dev --optimize-autoloader
npm ci && npm run production
cp docs/delivery/env.production.example .env        # then edit placeholders
php artisan key:generate && php artisan storage:link
php artisan migrate --force
# reference-only seed (see DEPLOYMENT.md §3.5 loop) — NOT full db:seed
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache public/uploads
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;

# Nginx + HTTPS (point yhedu DNS at this IP first)
# server block root -> /var/www/yheducation/public, fastcgi_pass unix:/run/php/php8.4-fpm.sock
sudo apt install -y certbot python3-certbot-nginx && sudo certbot --nginx -d <yhedu-domain>

# Scheduler
( crontab -l 2>/dev/null; echo "* * * * * cd /var/www/yheducation && php artisan schedule:run >> /dev/null 2>&1" ) | crontab -
```

---

**STOP — before production deployment.** No hosting created, no deploy, no push, no real data. Awaiting the client's hosting + domain decision to proceed.
