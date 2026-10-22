# Installation Guide — YH Education (GegoK12)

_For the YH Education technical contact. Covers the local/Docker setup that was built and verified. For production server setup, see `DEPLOYMENT.md`._

---

## 1. What this is
GegoK12 v1.1 (MIT-licensed core) — a Laravel 12 / PHP 8.4 / MySQL 8.4 school management system, branded and configured for **YH Education**.

## 2. Requirements (verified)
- PHP **8.4** with extensions: pdo_mysql, mbstring, xml, curl, zip, gd, bcmath, intl, fileinfo, openssl, tokenizer, exif
- MySQL **8.4**, Composer 2.x, Node **22** + npm (for building CSS/JS)
- For local dev: Docker Desktop + WSL2 (Windows) or Docker Engine (Linux)

## 3. Local setup with Docker (as delivered)
From the project root on the delivery branch:
```bash
git checkout yh-education-delivery
docker compose up -d --build          # starts app (php-fpm), nginx, MySQL 8.4, node:22, phpMyAdmin
docker compose exec app composer install
# create .env (local, disposable) with APP_URL=http://localhost:8090, DB host=db, db=school
docker compose exec app php artisan key:generate
docker compose exec -e COMPOSER_PROCESS_TIMEOUT=0 app php artisan migrate --force
docker compose exec -e COMPOSER_PROCESS_TIMEOUT=0 app php artisan db:seed --force   # synthetic/reference data
docker compose exec node npm install && docker compose exec node npm run production  # build assets
```
Then open **http://localhost:8090/** (login page). phpMyAdmin (local only): http://localhost:8092/.

### Environment notes captured during install (`docker-compose.override.yml`, untracked)
- MySQL host port mapped to **3307** (host 3306 was taken by WSL).
- phpMyAdmin pinned to **5.2.1**; the `node` service pinned to **node:22** (the node:24 image was corrupt on the build host).
- The `app` image's Dockerfile had its fragile NodeSource Node install removed (assets build in the `node` service).
- If building dependencies fails with TLS/certificate errors inside containers, disable HTTPS/SSL scanning in local antivirus (AVG "Web Shield → HTTPS scanning") — this was required on the build machine; it is a local-environment issue, not an app change.

## 4. Key configuration (`.env`)
| Setting | Value | Notes |
|---|---|---|
| `APP_NAME` | "YH Education" | shown as the login/page title |
| `TIMEZONE` | `Asia/Hong_Kong` | **the app reads `TIMEZONE`, not `APP_TIMEZONE`** |
| `CURRENCY_SYMBOL` | `HK$` | drives `config('app.currency_symbol')` |
| `WHATSAPP_COUNTRY_CODE` | `852` | WhatsApp button default dialling code |
| `MAIL_MAILER` / `MAIL_STATUS` | `log` / `off` locally | set real SMTP + `on` in production |
| DB | `db` / `school` / root (local) | production uses its own credentials |

## 5. Installer note
The repository contains a web installer at `public/installer` and a committed `storage/installed` marker. This delivery was installed via the **CLI** steps above (more reliable than the web installer, which shells out to Composer/NPM during an HTTP request and is unsuitable for most hosts). In production, use the CLI steps in `DEPLOYMENT.md` and **remove/rename `public/installer`** after setup.

## 6. Verified at install time
Login page renders; admin/teacher/student login works; migrations (108) and seeders run cleanly; CSS/JS assets serve. Full QA in `QA_REPORT.md`.
