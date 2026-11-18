# Member 2 – Local setup, step by step (Windows + XAMPP)

## What to download / have ready

| # | Item | Why | Where |
|---|------|-----|-------|
| 1 | **XAMPP** (PHP 8.1+ with MariaDB) | Runs PHP + MariaDB on your laptop | apachefriends.org → pick the PHP 8.x version |
| 2 | **VS Code** | Editor | code.visualstudio.com |
| 3 | **Git for Windows** | Push to GitHub | git-scm.com |
| 4 | A **GitHub account** + a private repo | Share code with Member 1 | github.com |
| 5 | A modern browser (Chrome/Edge) + a webcam | Test the photo booth | – |
| 6 | **Internet connection** while testing | Live photos, fonts, QR + chart libraries load from CDNs | – |

No Composer, no npm and no frameworks are needed.

## Step 1 – Install XAMPP and start MariaDB
1. Install XAMPP. Open the **XAMPP Control Panel**.
2. Click **Start** next to **MySQL** (this is MariaDB). You do **not** need to start Apache.
3. Add PHP to your PATH so you can run `php` in a terminal:
   Windows search → "Environment Variables" → Path → New → `C:\xampp\php`.
4. Open a **new** terminal and check: `php -v`.

## Step 2 – Enable the GD extension (needed for photo uploads)
1. Open `C:\xampp\php\php.ini`.
2. Find the line `;extension=gd` and remove the `;` so it reads `extension=gd`. Save.
   (In many XAMPP versions it is already enabled.) Check with `php -m` – you should see `gd`.

## Step 3 – Put the project in place
Copy the `student-evaluation` folder somewhere easy, e.g. `C:\Projects\student-evaluation`, and open it in VS Code.

## Step 4 – Create the database and a database user
1. Open the terminal in the project folder and run:
   ```
   C:\xampp\mysql\bin\mysql -u root < sql\schema.sql
   C:\xampp\mysql\bin\mysql -u root < sql\seed.sql
   ```
   (XAMPP's `root` has no password by default. `schema.sql` creates the database **student_evaluation**.)
2. Create a limited user the app will use (better than using root). Open the MySQL shell:
   ```
   C:\xampp\mysql\bin\mysql -u root
   ```
   then paste (choose your own password):
   ```sql
   CREATE USER 'eval_app'@'localhost' IDENTIFIED BY 'ChooseALongPassword123';
   GRANT SELECT, INSERT, UPDATE, DELETE ON student_evaluation.* TO 'eval_app'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ```
   The app only needs those four permissions – it can never drop tables.

## Step 5 – Configure the app
1. Copy `.env.example` to `.env` (same folder).
2. Edit `.env`: set `DB_PASSWORD` to the password from Step 4. Add `APP_DEBUG=1` while developing.

## Step 6 – Create your admin account
```
php scripts\create_admin.php "Your Name" you@example.com
```
Type a password when asked (10+ characters, letter + number).

## Step 7 – Run the site
```
php -S localhost:8000 -t public
```
Open **http://localhost:8000**. (Browsers allow the webcam on `localhost`.)

## Step 8 – Test checklist
- [ ] Home page loads; the **LIVE** photo changes every ~5 s; numbers show (not “–”).
- [ ] Register a student (try the camera). Log in as that student.
- [ ] Open **Evaluate a Unit** → rate → submit → confetti 🎉.
- [ ] Try submitting the same unit again → refused (duplicate prevention).
- [ ] Log in as the admin → **Dashboard**: charts and comments update on their own.
- [ ] In the dashboard click **QR** for a unit, scan it with your phone (phone must reach your laptop's address, or just compare the printed URL).
- [ ] Try a wrong password 6 times → temporary block.
- [ ] (Start **Apache** in XAMPP too for this one.) In phpMyAdmin (`http://localhost/phpmyadmin`) open `users` → the password column holds a `$2y$…` hash, not your password.
- [ ] `evaluations` has **no** user column – that's the anonymity proof for your lecturer.

## Step 9 – Push to GitHub
```
git init
git add .
git commit -m "Initial Banana University evaluation app"
git branch -M main
git remote add origin https://github.com/<you>/<repo>.git
git push -u origin main
```
Check first with `git status` that **.env** and uploaded photos are *not* listed (the `.gitignore` handles this).
For visible teamwork use a branch per feature (`git checkout -b feature/dashboard`) and open Pull Requests that Member 1 reviews.

## Step 10 – Hand over to Member 1
Give them `docs/DEPLOY_NOTES.md`. They clone the repo, import `sql/schema.sql`, set the four `DB_*` values (+ `APP_URL`) and point Apache at `public/`.

## Troubleshooting
| Symptom | Fix |
|---|---|
| "Database not connected yet" | Is MySQL started in XAMPP? Is `.env` present with the right password? Set `APP_DEBUG=1` and re-check. |
| `php` not recognised | Redo the PATH step, open a *new* terminal. |
| Photo not saved | GD extension disabled (Step 2). |
| Live photos blank | No internet, or a network blocks picsum.photos. The page shows a message instead of breaking. |
| Camera button does nothing | Use `localhost` (or HTTPS), allow the camera permission in the browser. |
| QR/Chart missing | CDN blocked – check your internet. |
