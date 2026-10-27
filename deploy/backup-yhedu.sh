#!/usr/bin/env bash
# YH Education — nightly backup: database dump + uploads, 14-day retention.
# Installed to /usr/local/bin/backup-yhedu by deploy-yhedu.sh (root cron, 02:15).
# RESTORE (database):  gunzip < /var/backups/yhedu/db-YYYY-MM-DD.sql.gz | mysql yheducation
# RESTORE (uploads):   tar -xzf /var/backups/yhedu/uploads-YYYY-MM-DD.tar.gz -C /
# ROLLBACK (app code): keep the previous release tar; re-extract it over /var/www/yheducation,
#                      run `composer install --no-dev`, restore the matching DB dump, then
#                      `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
set -euo pipefail
APP_DIR="/var/www/yheducation"
OUT="/var/backups/yhedu"
STAMP="$(date +%F)"
DB_NAME="$(grep '^DB_DATABASE=' ${APP_DIR}/.env | cut -d= -f2)"
DB_USER="$(grep '^DB_USERNAME=' ${APP_DIR}/.env | cut -d= -f2)"
DB_PASS="$(grep '^DB_PASSWORD=' ${APP_DIR}/.env | cut -d= -f2)"

mkdir -p "${OUT}" && chmod 700 "${OUT}"
MYSQL_PWD="${DB_PASS}" mysqldump -u "${DB_USER}" --single-transaction --no-tablespaces --routines "${DB_NAME}" | gzip > "${OUT}/db-${STAMP}.sql.gz"
tar -czf "${OUT}/uploads-${STAMP}.tar.gz" -C / "var/www/yheducation/public/uploads" "var/www/yheducation/storage/app" 2>/dev/null || true
find "${OUT}" -type f -mtime +14 -delete
echo "$(date -Is) backup ok: db-${STAMP}.sql.gz $(du -h ${OUT}/db-${STAMP}.sql.gz | cut -f1)"
