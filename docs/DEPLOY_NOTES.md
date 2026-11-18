# Notes for Member 1 – deploying on the Azure VM

1. `cd /var/www && git clone <repo-url> student-evaluation`
2. **Apache** (port 8080, bound to 127.0.0.1): `DocumentRoot /var/www/student-evaluation/public`
   and `<Directory ...> AllowOverride All  Require all granted </Directory>` (so `public/uploads/.htaccess` works).
   Needs `php`, `php-mysql`, `php-gd`, `php-mbstring` (`sudo apt install php php-mysql php-gd php-mbstring libapache2-mod-php`).
3. **Database**: `mysql -u root -p < sql/schema.sql` (safe to re-run, never drops data). Run `sql/seed.sql` only once, and only if sample data is wanted.
   Create the app user (never use root):
   ```sql
   CREATE USER 'eval_app'@'localhost' IDENTIFIED BY '<strong password>';
   GRANT SELECT, INSERT, UPDATE, DELETE ON student_evaluation.* TO 'eval_app'@'localhost';
   ```
4. **Settings** – either a `.env` file in the project root (chmod 640, owner root, group www-data) or Apache `SetEnv`:
   `DB_HOST=127.0.0.1  DB_NAME=student_evaluation  DB_USER=eval_app  DB_PASSWORD=…`
   and `APP_URL=https://evaluate.<domain>` (this address is what the QR codes encode – no trailing slash).
5. **Permissions**: `sudo chown -R www-data:www-data public/uploads/avatars` (profile photos are written here).
6. **Nginx** proxy block must pass the headers the app reads:
   ```
   proxy_set_header Host $host;
   proxy_set_header X-Real-IP $remote_addr;                         # used by login throttling
   proxy_set_header X-Forwarded-Proto $scheme;                      # lets the app mark cookies Secure
   ```
   Keep Apache listening only on 127.0.0.1:8080 so `X-Real-IP` cannot be spoofed from outside.
7. **HTTPS is required for the webcam** on the live site (browsers block the camera on plain HTTP). Also add an
   HTTP→HTTPS redirect on port 80.
8. **Admin account**: `php scripts/create_admin.php "Name" email` on the VM (it asks for a password).
9. **Updating**: `git pull` – no build step. Schema changes arrive as new `sql/migration-*.sql` files.
10. The Content-Security-Policy in `includes/bootstrap.php` allows scripts only from this site and cdnjs.cloudflare.com, images from this site and picsum.photos. If you add other CDNs, edit that header.
