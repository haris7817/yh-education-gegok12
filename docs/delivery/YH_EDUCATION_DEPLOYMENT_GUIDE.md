# YH Education — Deployment Guide (Oracle Ampere A1 · Ubuntu 24.04 Minimal · ARM64)

_Target: the client's Oracle Cloud instance — Ubuntu 24.04 Minimal **aarch64**, Ampere A1, public IP **129.150.50.78**. Status: server is running; **SSH access not yet confirmed — nothing has been deployed**. ARM64 compatibility of every component was verified (`YH_EDUCATION_100_SCOPE_AUDIT.md` §ARM64). General hosting comparison: `DEPLOYMENT.md`._

> Run all commands over SSH as the default `ubuntu` user (sudo). Replace `< >` placeholders. Steps assume a fresh instance.

## 0. SSH access (prerequisite — client action)
Authorize the deployment key on the server (e.g. paste into `~ubuntu/.ssh/authorized_keys`, or via the Oracle console):
```
ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIJ0jC0ypy920OL2bVNWx6JGthZ/h7oNdyIjEfqamO/Kg yhedu-deploy-20261025
```
(Public key only — never share private keys.) Then connection is `ssh ubuntu@129.150.50.78`.

## 1. Open web ports — Oracle needs BOTH layers (common gotcha)
**a) VCN Security List** (Oracle web console → the instance's subnet → Security List): add ingress rules — source `0.0.0.0/0`, TCP, destination ports **80** and **443**.

**b) Instance firewall** — Oracle's Ubuntu images ship preconfigured iptables rules with a blanket REJECT:
```bash
sudo iptables -I INPUT 5 -p tcp --dport 80  -j ACCEPT
sudo iptables -I INPUT 6 -p tcp --dport 443 -j ACCEPT
sudo apt install -y iptables-persistent && sudo netfilter-persistent save
```
Skipping either layer leaves the site unreachable even when everything else is correct.

## 2. Base packages (Ubuntu **Minimal** lacks several basics)
```bash
sudo apt update && sudo apt upgrade -y
sudo timedatectl set-timezone Asia/Hong_Kong
sudo apt install -y software-properties-common curl git unzip cron
```

## 3. Stack — PHP 8.4, MySQL, nginx, Composer, Node 22 (all arm64)
```bash
sudo add-apt-repository ppa:ondrej/php -y && sudo apt update
sudo apt install -y nginx mysql-server \
  php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl \
  php8.4-zip php8.4-gd php8.4-bcmath php8.4-intl php8.4-fileinfo
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash - && sudo apt install -y nodejs
```
Notes: Ubuntu 24.04's `mysql-server` is 8.0 (arm64) — fully compatible (dev verified on 8.4; migrations are standard SQL). For 8.4 LTS specifically, use MySQL's official apt repo (arm64 builds available). No native npm modules exist in this project, so the Node toolchain needs no compilers.

## 4. Database
```bash
sudo mysql_secure_installation
sudo mysql -e "CREATE DATABASE yheducation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'yhedu'@'localhost' IDENTIFIED BY '<STRONG_DB_PASSWORD>'; GRANT ALL ON yheducation.* TO 'yhedu'@'localhost'; FLUSH PRIVILEGES;"
```
MySQL must listen on localhost only (default). Do **not** open 3306 in the VCN or iptables; do not install phpMyAdmin in production.

## 5. Application
```bash
cd /var/www
sudo git clone <REPO_URL> yheducation && cd yheducation     # or unpack the delivery ZIP here
sudo git checkout yh-education-delivery
sudo chown -R $USER:www-data /var/www/yheducation
composer install --no-dev --optimize-autoloader
npm ci && npm run production          # optional — compiled assets are already included
cp docs/delivery/env.production.example .env   # then edit every <placeholder>
php artisan key:generate
php artisan storage:link
php artisan migrate --force
```

## 6. Seeding — ⚠️ reference data ONLY (never the full seeder)
`php artisan db:seed` would insert **~330 demo students + 660 demo parents** (`UsersStudentTableSeeder` has no production guard). Use the verified reference-only list instead (validated on a throwaway DB → 0 demo students/teachers/parents):
```bash
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
```
Immediately afterwards: change the seeded admin (`demoschool@mailinator.com`) email + password; remove/rename the four demo support-staff accounts (`librarian1@…` etc.); set the school profile to YH Education (name, address, phone, email, slogans) via the admin UI. The admin then creates real classes/sections and teachers, and imports students.

## 7. Permissions, cache, nginx, HTTPS
```bash
sudo chown -R www-data:www-data storage bootstrap/cache public/uploads
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
nginx server block: `root /var/www/yheducation/public;`, `fastcgi_pass unix:/run/php/php8.4-fpm.sock;` (the repo's `nginx.conf` is a close template — change the fastcgi target from `app:9000` to the socket). Then point the **yhedu** domain's A record at `129.150.50.78` and:
```bash
sudo apt install -y certbot python3-certbot-nginx && sudo certbot --nginx -d <yhedu-domain>
```
HTTPS is required (`SESSION_SECURE_COOKIE=true` is set in the production env template).

## 8. Scheduler & backups
```bash
( crontab -l 2>/dev/null; echo "* * * * * cd /var/www/yheducation && php artisan schedule:run >> /dev/null 2>&1" ) | crontab -
( crontab -l 2>/dev/null; echo "15 2 * * * mysqldump -u yhedu -p'<STRONG_DB_PASSWORD>' yheducation | gzip > /var/backups/yhedu-\$(date +\%F).sql.gz" ) | crontab -
```
Also back up `public/uploads` and `storage/app` offsite. **Test a restore before go-live** (`gunzip < backup.sql.gz | mysql -u yhedu -p yheducation`).

## 9. Go-live security checklist
- `APP_ENV=production`, `APP_DEBUG=false`, fresh `APP_KEY`; `.env` never committed
- **Delete or rename `public/installer/`** and remove `storage/installed` (CLI install used instead)
- Seeded admin credentials rotated; demo staff accounts removed
- MySQL on localhost only; no phpMyAdmin; HTTPS enforced; `sudo apt install unattended-upgrades`
- Never run `php artisan db:seed` (plain) or the phpunit Feature suite on this server (both are destructive — see `KNOWN_LIMITATIONS.md` §7)

## 10. Post-deploy verification
Login over HTTPS as admin/teacher; mark one attendance entry; open a student record; re-time the fee/timetable pages with real data; send a password-reset email (SMTP); run the mobile-device check. Checklist: `YH_EDUCATION_FINAL_DELIVERY_CHECKLIST.md`.
