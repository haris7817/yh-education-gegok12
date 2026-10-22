# Deployment Guide — YH Education (GegoK12)

_Prepared 2026-10-22. For ~60 students and 3–4 teachers. No production deployment has been performed — this is the prepared, repeatable procedure. Pricing is indicative (confirm at signup; it changes)._

---

## 1. Hosting comparison

| Criterion | Option A — Render + Aiven MySQL | Option B — Ubuntu VPS |
|---|---|---|
| **PHP 8.4 / Laravel 12** | ✅ via a Docker deploy on Render (Render has no native PHP runtime) | ✅ native (`ppa:ondrej/php`), full control |
| **Persistent file storage** | ⚠️ **Render filesystem is ephemeral** — resets on every deploy/restart. GegoK12 stores uploads on local disk (`FILESYSTEM_DISK=uploads`, student photos, documents, media library) → **uploads would be lost**. Requires a paid **persistent disk** (~$0.25/GB/mo, paid plans only) or reconfiguring to AWS S3 (extra account + cost) | ✅ native local disk — uploads persist with zero extra work |
| **MySQL connectivity** | Aiven managed MySQL over the public internet + **TLS required** (CA cert in `.env`); Render has no native MySQL | ✅ local MySQL 8.4 on the same box — fast, no TLS/connection-limit hassle |
| **Composer / Node build** | Runs in the Docker build on deploy; works, but rebuilds each deploy | Run once on the server (or in CI); full control |
| **HTTPS** | ✅ free managed TLS (auto) | ✅ free via Let's Encrypt / Certbot |
| **Database backups** | Aiven **free tier has no SLA**; backup/retention is limited on free, better on paid | Self-managed `mysqldump` + offsite copy (cron) — simple, fully in your control |
| **Security** | Managed platform (less to patch) but ephemeral-FS workarounds add moving parts | You patch the OS; one server, well-understood LEMP hardening |
| **Resource needs (60 users)** | Render **Starter $7/mo** (free tier spins down after 15 min idle → not usable for a live school) + disk; Aiven **free MySQL = 1 GB** (ample for 60 students) or paid hobbyist | 1–2 vCPU / 2–4 GB RAM / 40 GB SSD is plenty |
| **Setup complexity** | Moderate–High: Docker on Render, external DB + TLS, persistent-disk or S3 config, multiple dashboards | Moderate: one-time LEMP setup, then a single repeatable box |
| **Ongoing cost (realistic, always-on)** | ~**$7/mo** (Render Starter) **+** persistent disk **+** Aiven (free 1 GB or paid) ≈ **$8–20/mo**, across two vendors | **$4–12/mo** single vendor (e.g. Hetzner ~€4–5, DigitalOcean/Vultr/Linode $5–12), all-in |
| **Key limitation** | Ephemeral FS vs. the app's local-disk uploads; free tiers unsuitable for live use | You own OS patching & backups |

---

## 2. Recommendation — **Option B: a single Ubuntu VPS**

For this small, stable workload (~64 users, low concurrency) the VPS is the **simplest reliable** fit because it matches the application's architecture directly:

- GegoK12 writes uploads to the **local filesystem** and runs against a **local MySQL** — exactly what a VPS provides with no workarounds. Render's ephemeral filesystem fights this (needs a paid persistent disk or an S3 rewrite), and Aiven adds an external, TLS-secured DB hop plus free-tier/SLA caveats.
- **One vendor, one box, one predictable bill** (~$5–12/mo all-in) vs. two dashboards and a part-paid/part-free split.
- **Repeatable and portable**: the same steps work on any provider (Hetzner, DigitalOcean, Vultr, Linode), avoiding lock-in.

Render + Aiven remains viable if YH Education prefers a fully managed platform and continuity with their previous setup — but budget for **Render Starter + a persistent disk** (never the free tier for live use) and the external-DB TLS configuration.

**Suggested VPS spec:** Ubuntu 24.04 LTS, 2 vCPU, 2–4 GB RAM, 40 GB SSD, in or near Hong Kong (e.g. an Asia region) for latency.

---

## 3. Deployment procedure — Ubuntu VPS (recommended)

> Run everything over SSH as a non-root sudo user. Replace placeholders in `< >`. **Nothing here has been executed** — it is the prepared runbook, pending your hosting approval.

### 3.1 Provision & base hardening
```bash
sudo apt update && sudo apt upgrade -y
sudo timedatectl set-timezone Asia/Hong_Kong
# Firewall: SSH + web only
sudo ufw allow OpenSSH && sudo ufw allow 80 && sudo ufw allow 443 && sudo ufw enable
# Optional: fail2ban for SSH
sudo apt install -y fail2ban
```

### 3.2 Install the stack (PHP 8.4, MySQL 8.4, Nginx, Composer, Node 22)
```bash
sudo add-apt-repository ppa:ondrej/php -y && sudo apt update
sudo apt install -y nginx mysql-server \
  php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl \
  php8.4-zip php8.4-gd php8.4-bcmath php8.4-intl php8.4-fileinfo unzip git
# Composer
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
# Node 22 (for asset build; can also be built in CI and uploaded)
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash - && sudo apt install -y nodejs
```

### 3.3 Database (local, non-production-free credentials)
```bash
sudo mysql_secure_installation            # set root pw, remove test db/anon users
sudo mysql -e "CREATE DATABASE yheducation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'yhedu'@'localhost' IDENTIFIED BY '<STRONG_DB_PASSWORD>';"
sudo mysql -e "GRANT ALL PRIVILEGES ON yheducation.* TO 'yhedu'@'localhost'; FLUSH PRIVILEGES;"
```

### 3.4 Deploy the application
```bash
cd /var/www
sudo git clone <YOUR_REPO_URL> yheducation && cd yheducation
sudo git checkout yh-education-delivery        # the delivery branch
sudo chown -R $USER:www-data /var/www/yheducation
composer install --no-dev --optimize-autoloader
npm ci && npm run production                    # build CSS/JS (or upload prebuilt public/)
```

### 3.5 Production `.env`
```bash
cp .env.example .env
```
Set at minimum:
```
APP_NAME="YH Education"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<yheduhk-domain>
TIMEZONE='Asia/Hong_Kong'
CURRENCY_SYMBOL="HK$"
WHATSAPP_COUNTRY_CODE=852
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yheducation
DB_USERNAME=yhedu
DB_PASSWORD=<STRONG_DB_PASSWORD>
MAIL_MAILER=smtp            # configure a real SMTP provider for notifications
MAIL_STATUS=on              # turn login/notification emails back on in production
```
Then:
```bash
php artisan key:generate                  # writes a fresh APP_KEY (keep secret)
php artisan storage:link
php artisan migrate --force               # creates tables

# Reference-only seeding — VERIFIED to create 0 demo students/teachers/parents.
# Do NOT run the full `php artisan db:seed` in production: UsersStudentTableSeeder
# is NOT environment-guarded and would insert ~330 demo students + 660 parents.
for S in UsergroupTableSeeder CountriesTableSeeder StatesTableSeeder CitiesTableSeeder \
  PlansTableSeeder MailTemplatesSeeder SettingsTableSeeder SmsTemplatesTableSeeder \
  KeywordsTableSeeder QualificationTableSeeder AbsentReasonsTableSeeder RolesTableSeeder \
  SchoolsTableSeeder SchoolDetailsTableSeeder AcademicYearsTableSeeder SectionsTableSeeder \
  StandardsTableSeeder SubjectsTableSeeder UsersSchoolAdminTableSeeder SubscriptionsTableSeeder \
  BooksCategoryTableSeeder BooksTableSeeder ScholasticGradesTableSeeder NonScholasticGradesTableSeeder \
  LeaveTypesTableSeeder RoleUsersTableSeeder PageCategoryTableSeeder PayCategoryTableSeeder \
  PayrollItemTableSeeder TransactionTypeTableSeeder TrasactionAccountTableSeeder TagSeeder; do
  php artisan db:seed --class=$S --force
done

php artisan config:cache && php artisan route:cache && php artisan view:cache
```
> **After seeding:** immediately change the seeded admin (`demoschool@mailinator.com`) email + password, and remove/rename the demo support-staff accounts (librarian / receptionist / accountant / stock_keeper `@mailinator.com`). The admin then creates the real classes/sections, teachers, and imports students. This reference-only seed was validated on a throwaway database: 0 errors, reference data + subscription present, **0 students / teachers / parents**.
> Student data: do **not** import real records until the client supplies them and the format/data-protection/backup approach is confirmed. The verified Excel template and importer are documented in `QA_REPORT.md`.

### 3.6 Permissions
```bash
sudo chown -R www-data:www-data storage bootstrap/cache public/uploads
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
```

### 3.7 Nginx + HTTPS
- Create an Nginx server block with `root /var/www/yheducation/public;`, `fastcgi_pass unix:/run/php/php8.4-fpm.sock;` (the repo's `nginx.conf` is a close template — adjust the fastcgi socket).
- Point the **`yheduhk`** domain/subdomain's DNS A record at the VPS IP.
- `sudo apt install -y certbot python3-certbot-nginx && sudo certbot --nginx -d <yheduhk-domain>` for free auto-renewing TLS.

### 3.8 Background jobs & scheduler
```bash
# Laravel scheduler (reminders, cleanups the app defines)
( crontab -l 2>/dev/null; echo "* * * * * cd /var/www/yheducation && php artisan schedule:run >> /dev/null 2>&1" ) | crontab -
# Queue worker (QUEUE_CONNECTION=sync works for this scale; a worker is optional)
# sudo systemctl enable --now a supervisor/systemd 'php artisan queue:work' unit if async queues are used
```

### 3.9 Go-live security checklist
- `APP_DEBUG=false`, `APP_ENV=production` ✅
- **Remove/rename `public/installer`** and delete `storage/installed` only if using CLI install (not the web installer).
- `.env` not web-readable (it's below `public/`), never committed.
- **No phpMyAdmin / no public DB port** (3306 bound to localhost only; not in `ufw`).
- Strong, unique DB and admin passwords; change the seeded admin password on first login.
- HTTPS enforced; automatic OS security updates (`unattended-upgrades`).

---

## 4. Backup & recovery
```bash
# Nightly DB backup (keep 14 days), plus the uploads directory
( crontab -l 2>/dev/null; echo "15 2 * * * mysqldump -u yhedu -p'<STRONG_DB_PASSWORD>' yheducation | gzip > /var/backups/yhedu-\$(date +\%F).sql.gz" ) | crontab -
# Also back up /var/www/yheducation/public/uploads and storage/app to offsite (e.g. rclone to object storage).
```
Restore: `gunzip < backup.sql.gz | mysql -u yhedu -p yheducation` and restore the uploads directory. Test a restore before go-live.

---

## 5. Client information required before deployment
1. **Hosting decision** — VPS (recommended) or Render+Aiven; and **who owns the account** (must be client-owned; secure collaborator access only — never personal Google passwords).
2. **Domain** — confirm the `yheduhk` domain/subdomain and DNS access.
3. **SMTP** provider + credentials (for login/notification emails), or confirm email stays off.
4. **Currency** final confirmation (HK$ set).
5. **Real student data** — only when ready, with agreed format, data-protection and backup approach.

---

## 6. Exact next deployment steps (after approval)
1. Client provisions (or authorises) the VPS and provides SSH access + the `yheduhk` domain.
2. Run §3.1–3.7, verify the login page over HTTPS.
3. Run the QA checklist (QA_REPORT.md) against the live URL; change the seeded admin password.
4. Configure backups (§4) and verify a test restore.
5. Hand over admin access securely + walkthrough.

**No production deployment, account creation, or paid resource will be provisioned without explicit client authorization.**
