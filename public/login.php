<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

if (current_user()) {
    redirect('units.php');
}

$errors = [];
$email = '';
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $email    = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $ip       = client_ip();

        try {
            $pdo = db();

            // ---- Brute-force protection: too many recent failures for this email OR this IP? ----
            $check = $pdo->prepare(
                'SELECT COUNT(*) FROM auth_attempts
                 WHERE success = 0
                   AND attempted_at > (NOW() - INTERVAL ' . LOGIN_WINDOW_MINUTES . ' MINUTE)
                   AND (email = ? OR ip_address = ?)'
            );
            $check->execute([$email, $ip]);

            if ((int)$check->fetchColumn() >= MAX_LOGIN_FAILURES) {
                $errors[] = 'Too many failed attempts. Please wait ' . LOGIN_WINDOW_MINUTES . ' minutes and try again.';
            } else {
                $stmt = $pdo->prepare('SELECT id, full_name, password_hash, role, avatar_path FROM users WHERE email = ?');
                $stmt->execute([$email]);
                $row = $stmt->fetch();

                // Always run password_verify (even for unknown emails) so response time doesn't reveal
                // whether an account exists.
                $dummy = '$2y$10$7/m4pTP7uEl735.E2ZYalOKnRxUabfeEvaBp9Uscg3WQ7lt1K0Ixm';
                $valid = password_verify($password, $row['password_hash'] ?? $dummy) && $row !== false;

                $log = $pdo->prepare('INSERT INTO auth_attempts (email, ip_address, success) VALUES (?, ?, ?)');
                $log->execute([$email, $ip, $valid ? 1 : 0]);

                if ($valid) {
                    session_regenerate_id(true);   // stops session-fixation attacks
                    $_SESSION['user'] = [
                        'id'     => (int)$row['id'],
                        'name'   => $row['full_name'],
                        'role'   => $row['role'],
                        'avatar' => $row['avatar_path'],
                    ];
                    if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
                        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                            ->execute([password_hash($password, PASSWORD_DEFAULT), $row['id']]);
                    }
                    if ($next !== '') {
                        redirect($next);
                    }
                    redirect($row['role'] === 'admin' ? 'admin/dashboard.php' : 'units.php');
                }
                // Same message for "no such email" and "wrong password"
                $errors[] = 'Incorrect email or password.';
            }
        } catch (Throwable $ex) {
            error_log('Login error: ' . $ex->getMessage());
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/../includes/header.php';
?>

<section class="form-page">
  <div class="form-card c3">
    <h1>Welcome back 👋</h1>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="next" value="<?= e($next !== '' ? '/' . $next : '') ?>">
      <label>Email
        <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="username">
      </label>
      <label>Password
        <input type="password" name="password" required autocomplete="current-password">
      </label>
      <button class="btn btn-big btn-full" type="submit">Log in</button>
      <p class="form-foot">New here? <a href="<?= e(url('register.php')) ?>">Create an account</a></p>
    </form>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
