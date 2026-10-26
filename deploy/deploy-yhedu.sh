#!/usr/bin/env bash
# ============================================================================
# YH Education (GegoK12) — production deployment for Ubuntu 24.04 ARM64
# Target: Oracle Ampere A1, fresh Minimal image. Run as root:
#     sudo bash deploy-yhedu.sh all
# or one phase at a time:  base | stack | firewall | db | app | web | backup
# Later, when the domain's DNS points here:  sudo bash deploy-yhedu.sh https <domain>
#
# Expects the release at /home/deploy/yhedu-release/yh-education-delivery-v1.0.tar.gz
# Safe to re-run: phases skip work that is already done. Seeding REFUSES to run
# on a non-empty database. No demo students/teachers/parents are ever seeded.
# Secrets (DB password, APP_KEY, admin password) are generated ON this server.
# ============================================================================
set -euo pipefail

RELEASE_TAR="/home/deploy/yhedu-release/yh-education-delivery-v1.0.tar.gz"
APP_DIR="/var/www/yheducation"
SERVER_IP="129.150.50.78"
DB_NAME="yheducation"
DB_USER="yhedu"
CRED_FILE="/home/deploy/YHEDU_CREDENTIALS.txt"   # chmod 600 — hand to client securely, then delete

say(){ echo -e "\n==== $* ===="; }
need_root(){ [ "$(id -u)" = 0 ] || { echo "Run with sudo."; exit 1; }; }
rand(){ openssl rand -base64 48 | tr -dc 'A-Za-z0-9' | head -c "${1:-24}"; }

phase_base(){ need_root; say "BASE: packages + timezone"
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -y && apt-get upgrade -y
  timedatectl set-timezone Asia/Hong_Kong
  apt-get install -y software-properties-common curl git unzip cron openssl
}

phase_stack(){ need_root; say "STACK: PHP 8.4 / nginx / MySQL / Composer / Node 22 (arm64)"
  export DEBIAN_FRONTEND=noninteractive
  add-apt-repository -y ppa:ondrej/php && apt-get update -y
  apt-get install -y nginx mysql-server \
    php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl \
    php8.4-zip php8.4-gd php8.4-bcmath php8.4-intl php8.4-fileinfo
  command -v composer >/dev/null || { curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer; }
  command -v node >/dev/null || { curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && apt-get install -y nodejs; }
  php -v | head -1; node -v; composer --version | head -1
}

phase_firewall(){ need_root; say "FIREWALL: open 80/443 in instance iptables (VCN list is client-side)"
  iptables -C INPUT -p tcp --dport 80  -j ACCEPT 2>/dev/null || iptables -I INPUT 5 -p tcp --dport 80  -j ACCEPT
  iptables -C INPUT -p tcp --dport 443 -j ACCEPT 2>/dev/null || iptables -I INPUT 6 -p tcp --dport 443 -j ACCEPT
  echo iptables-persistent iptables-persistent/autosave_v4 boolean true | debconf-set-selections
  echo iptables-persistent iptables-persistent/autosave_v6 boolean true | debconf-set-selections
  DEBIAN_FRONTEND=noninteractive apt-get install -y iptables-persistent
  netfilter-persistent save
}

phase_db(){ need_root; say "DB: harden MySQL + create ${DB_NAME}"
  systemctl enable --now mysql
  # non-interactive hardening (root stays on auth_socket — no remote root, no password login)
  mysql -e "DELETE FROM mysql.user WHERE User=''; DROP DATABASE IF EXISTS test; DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%'; DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost','127.0.0.1','::1'); FLUSH PRIVILEGES;"
  if [ ! -f "${CRED_FILE}" ] || ! grep -q '^DB_PASSWORD=' "${CRED_FILE}" 2>/dev/null; then
    DB_PASS="$(rand 24)"
    install -m 600 -o deploy -g deploy /dev/null "${CRED_FILE}"
    { echo "# YH Education server credentials — deliver securely to the client, then DELETE this file."
      echo "DB_USER=${DB_USER}"; echo "DB_PASSWORD=${DB_PASS}"; } >> "${CRED_FILE}"
  fi
  DB_PASS="$(grep '^DB_PASSWORD=' "${CRED_FILE}" | cut -d= -f2)"
  mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}'; ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}'; GRANT ALL ON ${DB_NAME}.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"
}

phase_app(){ need_root; say "APP: extract release, install, configure, migrate, reference-only seed"
  [ -f "${RELEASE_TAR}" ] || { echo "Release tar not found: ${RELEASE_TAR}"; exit 1; }
  mkdir -p "${APP_DIR}"
  if [ ! -f "${APP_DIR}/artisan" ]; then tar -xzf "${RELEASE_TAR}" -C "${APP_DIR}"; fi
  cd "${APP_DIR}"
  export COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_PROCESS_TIMEOUT=0
  composer install --no-dev --optimize-autoloader --no-interaction
  # ---- .env ----
  if [ ! -f .env ]; then
    cp deploy/env.production.server .env
    DB_PASS="$(grep '^DB_PASSWORD=' "${CRED_FILE}" | cut -d= -f2)"
    sed -i "s|__DB_PASSWORD__|${DB_PASS}|" .env
    php artisan key:generate --force
  fi
  php artisan storage:link || true
  php artisan migrate --force
  # ---- seeding: ONLY on an empty database, ONLY reference data ----
  USERS=$(mysql -N -e "SELECT COUNT(*) FROM ${DB_NAME}.users;" 2>/dev/null || echo 0)
  if [ "${USERS}" = "0" ]; then
    for S in UsergroupTableSeeder CountriesTableSeeder StatesTableSeeder CitiesTableSeeder \
      PlansTableSeeder MailTemplatesSeeder SettingsTableSeeder SmsTemplatesTableSeeder \
      KeywordsTableSeeder QualificationTableSeeder AbsentReasonsTableSeeder RolesTableSeeder \
      SchoolsTableSeeder SchoolDetailsTableSeeder AcademicYearsTableSeeder SectionsTableSeeder \
      StandardsTableSeeder SubjectsTableSeeder UsersSchoolAdminTableSeeder SubscriptionsTableSeeder \
      BooksCategoryTableSeeder BooksTableSeeder ScholasticGradesTableSeeder NonScholasticGradesTableSeeder \
      LeaveTypesTableSeeder RoleUsersTableSeeder PageCategoryTableSeeder PayCategoryTableSeeder \
      PayrollItemTableSeeder TransactionTypeTableSeeder TrasactionAccountTableSeeder TagSeeder; do
      php artisan db:seed --class="$S" --force
    done
    phase_yhconfig
  else
    echo "users table not empty (${USERS}) — seeding skipped (never seed a populated DB)."
  fi
  # ---- permissions / caches ----
  chown -R www-data:www-data storage bootstrap/cache public/uploads 2>/dev/null || chown -R www-data:www-data storage bootstrap/cache
  find storage bootstrap/cache -type d -exec chmod 775 {} \;
  php artisan config:cache && php artisan route:cache && php artisan view:cache
  # ---- scheduler ----
  ( { crontab -l 2>/dev/null | grep -v 'schedule:run'; } || true; echo "* * * * * cd ${APP_DIR} && sudo -u www-data php artisan schedule:run >> /dev/null 2>&1" ) | crontab -
}

phase_yhconfig(){ say "YH CONFIG: school profile + credential rotation (no demo access remains)"
  cd "${APP_DIR}"
  mysql --default-character-set=utf8mb4 ${DB_NAME} <<'SQL'
UPDATE schools SET name='YH Education', slug='yh-education',
  address='111-112, Savannah, Chi Sin Street, Tseung Kwan O / 將軍澳至善街3號Savannah 111-112',
  phone='+852 54001530', email='yh.eduhk@gmail.com', updated_at=NOW() WHERE id=1;
DELETE FROM school_details WHERE school_id=1 AND meta_key='moto';
INSERT INTO school_details (school_id, meta_key, meta_value, created_at, updated_at)
VALUES (1,'moto','Nurturing Youthful Minds, Embracing Holistic Growth | 言傳身教 希冀成材',NOW(),NOW());
SQL
  ADMIN_PASS="$(rand 16)"
  php artisan tinker --execute "
    \$u = \App\Models\User::where('usergroup_id',3)->first();
    \$u->email='yh.eduhk@gmail.com'; \$u->password=bcrypt('${ADMIN_PASS}'); \$u->save();
    foreach (\App\Models\User::whereIn('usergroup_id',[8,10,11,12])->get() as \$d) { \$d->password=bcrypt(bin2hex(random_bytes(24))); \$d->save(); }
    echo 'admin + demo-staff credentials rotated';"
  { echo "ADMIN_LOGIN=yh.eduhk@gmail.com"; echo "ADMIN_PASSWORD=${ADMIN_PASS}"; } >> "${CRED_FILE}"
  echo "Admin credentials written to ${CRED_FILE} (600, owner deploy)."
}

phase_web(){ need_root; say "WEB: nginx site (IP-based HTTP first) + smoke test"
  install -m 644 "${APP_DIR}/deploy/nginx-yhedu.conf" /etc/nginx/sites-available/yhedu
  ln -sf /etc/nginx/sites-available/yhedu /etc/nginx/sites-enabled/yhedu
  rm -f /etc/nginx/sites-enabled/default
  nginx -t && systemctl enable --now nginx php8.4-fpm && systemctl reload nginx
  sleep 2
  CODE=$(curl -s -o /tmp/yhedu_smoke.html -w '%{http_code}' http://127.0.0.1/login || true)
  echo "smoke: /login -> HTTP ${CODE}; YH branding: $(grep -c 'YH Education' /tmp/yhedu_smoke.html || true)"
  # security: disable the web installer (CLI install was used)
  [ -d "${APP_DIR}/public/installer" ] && mv "${APP_DIR}/public/installer" "${APP_DIR}/installer.disabled" && echo "public/installer disabled"
  rm -f "${APP_DIR}/storage/installed"
}

phase_https(){ need_root; say "HTTPS: certbot for domain $1 + secure cookies"
  DOMAIN="${1:?usage: deploy-yhedu.sh https <domain>}"
  export DEBIAN_FRONTEND=noninteractive
  apt-get install -y certbot python3-certbot-nginx
  sed -i "s/server_name .*/server_name ${DOMAIN};/" /etc/nginx/sites-available/yhedu
  nginx -t && systemctl reload nginx
  certbot --nginx -d "${DOMAIN}" --non-interactive --agree-tos -m yh.eduhk@gmail.com --redirect
  cd "${APP_DIR}"
  sed -i "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" .env
  sed -i "s|^SESSION_SECURE_COOKIE=.*|SESSION_SECURE_COOKIE=true|" .env
  php artisan config:cache
  echo "HTTPS live at https://${DOMAIN}"
}

phase_backup(){ need_root; say "BACKUP: nightly DB + uploads, 14-day retention"
  install -m 750 "${APP_DIR}/deploy/backup-yhedu.sh" /usr/local/bin/backup-yhedu
  mkdir -p /var/backups/yhedu && chmod 700 /var/backups/yhedu
  ( { crontab -l 2>/dev/null | grep -v backup-yhedu; } || true; echo "15 2 * * * /usr/local/bin/backup-yhedu >> /var/log/yhedu-backup.log 2>&1" ) | crontab -
  /usr/local/bin/backup-yhedu && echo "backup test run OK: $(ls -t /var/backups/yhedu | head -2 | tr '\n' ' ')"
}

case "${1:-all}" in
  base) phase_base;; stack) phase_stack;; firewall) phase_firewall;; db) phase_db;;
  app) phase_app;; web) phase_web;; https) phase_https "${2:-}";; backup) phase_backup;;
  all) phase_base; phase_stack; phase_firewall; phase_db; phase_app; phase_web; phase_backup
       say "DONE — site on http://${SERVER_IP} ; run 'deploy-yhedu.sh https <domain>' when DNS is ready";;
  *) echo "usage: sudo bash deploy-yhedu.sh [all|base|stack|firewall|db|app|web|backup|https <domain>]"; exit 1;;
esac
