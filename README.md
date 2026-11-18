# 🍌 Banana University – Anonymous Lecturer & Unit Evaluation


**Features:** student registration · login with brute-force throttling ·
QR code per unit · 5-question star evaluation · anonymous feedback with duplicate-submission prevention ·
live photo carousel · live counters · admin dashboard with live charts and QR posters.

```
config/database.php   PDO connection (settings come from .env / environment)
includes/             bootstrap (security + helpers), header, footer, env loader
public/               the web root (Apache DocumentRoot points here)
  api/                JSON feeds for live counters and the admin dashboard
  admin/dashboard.php admin-only page
  assets/             css + js
sql/schema.sql        tables   |  sql/seed.sql  sample data
scripts/create_admin.php  create an administrator from the command line
docs/SETUP.md         step-by-step local setup   |  docs/DEPLOY_NOTES.md  for Member 1
```

Start with **docs/SETUP.md**.

## Security design (for the demo)
| Threat | Defence |
|---|---|
| Stolen database | Passwords stored only as `password_hash()` (bcrypt) |
| SQL injection | PDO prepared statements, real (non-emulated) |
| XSS | Every output escaped with `e()`; strict Content-Security-Policy, no inline scripts |
| CSRF | Token on every POST form, including logout |
| Brute force | `auth_attempts` table, 5 failures / 15 min per email or IP |
| Session theft/fixation | HttpOnly + SameSite cookie, Secure over HTTPS, ID regenerated at login |
| Account enumeration | Same error message and equal timing for unknown email / wrong password |
| Double evaluation | `UNIQUE(user_id, unit_id)` on `submission_records` inside a transaction |
| Linking feedback to a student | `evaluations` has no user column and stores only the date |
| Malicious uploads | Image re-encoded with GD, random file name, PHP execution disabled in uploads/ |
| Open redirect | `safe_next()` only allows paths on this site |
