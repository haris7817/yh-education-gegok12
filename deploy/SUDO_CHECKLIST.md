# YH Education — Exact Commands Once Sudo Is Granted

_Everything below is already staged. Total expected time: ~15–25 minutes, mostly apt downloads. The release and scripts are in `/home/deploy/yhedu-release/` on the server._

## Client prerequisites (their side, once)
1. From the `ubuntu` admin account (or Oracle console):
   ```bash
   sudo usermod -aG sudo deploy
   echo 'deploy ALL=(ALL) NOPASSWD:ALL' | sudo tee /etc/sudoers.d/deploy
   sudo chmod 440 /etc/sudoers.d/deploy
   ```
2. Oracle web console → instance subnet → **Security List** → ingress rules: TCP **80** and **443** from `0.0.0.0/0`.

## Deployment (run as deploy@129.150.50.78)
```bash
cd ~/yhedu-release
sha256sum -c yh-education-delivery-v1.0.tar.gz.sha256      # integrity check
sudo bash deploy-yhedu.sh all                               # full deployment
```
`all` runs, in order: base packages → PHP 8.4/nginx/MySQL/Node 22 (arm64) → instance firewall 80/443 → MySQL hardening + `yheducation` DB (password generated on-server) → app extract + `composer install --no-dev` → `.env` + `APP_KEY` → migrations → **reference-only seeding** (refuses non-empty DBs; zero demo students) → YH Education profile (name/address 中英/phone/email/slogans) → **admin credential rotation** (login becomes `yh.eduhk@gmail.com`, random password) + demo-staff lockout → permissions → caches → scheduler cron → nginx site → smoke test → installer disabled → nightly backups (with a test run).

## Verify
```bash
curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1/login      # expect 200
curl -s http://127.0.0.1/login | grep -c 'YH Education'             # expect ≥1
```
Then from any browser: **http://129.150.50.78/login** (works only after the VCN rules — prerequisite 2).

## Hand over credentials, then clean up
```bash
cat /home/deploy/YHEDU_CREDENTIALS.txt    # DB password + admin login — deliver securely
shred -u /home/deploy/YHEDU_CREDENTIALS.txt
```

## When the domain is ready (DNS A record → 129.150.50.78)
```bash
sudo bash ~/yhedu-release/deploy-yhedu.sh https <the-yhedu-domain>
```
Installs certbot, issues the certificate, forces HTTPS, flips `APP_URL` + `SESSION_SECURE_COOKIE=true`.

## When SMTP credentials arrive
Edit `/var/www/yheducation/.env`: set `MAIL_MAILER=smtp`, the `MAIL_HOST/PORT/USERNAME/PASSWORD`, and `MAIL_STATUS=on`, then `php artisan config:cache`. ⚠️ Never set `MAIL_STATUS=on` without working SMTP — logins would 500.

## After go-live (security teardown)
```bash
sudo rm /etc/sudoers.d/deploy        # revoke temporary sudo (client runs this)
```
Joint UAT list (10–15 min): `docs/delivery/YH_EDUCATION_PENDING_CLIENT_ITEMS.md`.
