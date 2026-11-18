<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

if (current_user()) {
    redirect('units.php');
}

$errors = [];
$old = ['full_name' => '', 'email' => '', 'admission_no' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $fullName  = trim((string)($_POST['full_name'] ?? ''));
        $email     = strtolower(trim((string)($_POST['email'] ?? '')));
        $admission = strtoupper(trim((string)($_POST['admission_no'] ?? '')));
        $password  = (string)($_POST['password'] ?? '');
        $confirm   = (string)($_POST['confirm'] ?? '');
        $photo     = (string)($_POST['avatar_data'] ?? '');
        $old = ['full_name' => $fullName, 'email' => $email, 'admission_no' => $admission];

        // ---- Server-side validation (never trust the browser) ----
        if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
            $errors[] = 'Please enter your full name (2–100 characters).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (!preg_match('#^[A-Z0-9/\-]{3,20}$#', $admission)) {
            $errors[] = 'Admission number may contain letters, digits, "-" and "/" (3–20 characters).';
        }
        if (strlen($password) < 10 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            $errors[] = 'Password must be at least 10 characters and include a letter and a number.';
        }
        if ($password !== $confirm) {
            $errors[] = 'The two passwords do not match.';
        }

        if (!$errors) {
            try {
                $pdo = db();
                $avatar = $photo !== '' ? save_avatar($photo) : null;

                $stmt = $pdo->prepare(
                    'INSERT INTO users (full_name, email, admission_no, password_hash, role, avatar_path)
                     VALUES (?, ?, ?, ?, \'student\', ?)'
                );
                // password_hash() salts automatically; PASSWORD_DEFAULT = bcrypt today, upgraded by PHP later
                $stmt->execute([$fullName, $email, $admission, password_hash($password, PASSWORD_DEFAULT), $avatar]);

                flash('success', 'Account created! You can log in now. 🎉');
                redirect('login.php');
            } catch (PDOException $ex) {
                if ($ex->getCode() === '23000') {   // UNIQUE constraint (email or admission number)
                    $errors[] = 'That email or admission number is already registered.';
                } else {
                    error_log('Register error: ' . $ex->getMessage());
                    $errors[] = 'Something went wrong. Please try again.';
                }
            } catch (Throwable $ex) {
                error_log('Register error: ' . $ex->getMessage());
                $errors[] = 'Something went wrong. Please try again.';
            }
        }
    }
}

$pageTitle = 'Register';
require __DIR__ . '/../includes/header.php';
?>

<section class="form-page">
  <div class="form-card c2">
    <h1>Create your account 🎓</h1>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" autocomplete="off" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="avatar_data" value="" data-avatar-field>

      <label>Full name
        <input type="text" name="full_name" value="<?= e($old['full_name']) ?>" required maxlength="100">
      </label>
      <label>Email
        <input type="email" name="email" value="<?= e($old['email']) ?>" required maxlength="190">
      </label>
      <label>Admission number
        <input type="text" name="admission_no" value="<?= e($old['admission_no']) ?>" required maxlength="20" placeholder="e.g. 189630">
      </label>
      <label>Password
        <input type="password" name="password" required minlength="10" autocomplete="new-password" data-password>
        <meter min="0" max="4" value="0" data-strength></meter>
        <small data-strength-text>At least 10 characters, with a letter and a number.</small>
      </label>
      <label>Confirm password
        <input type="password" name="confirm" required autocomplete="new-password">
      </label>

      <!-- LIVE WEBCAM PHOTO BOOTH (optional) -->
      <div class="booth" data-webcam>
        <p class="booth-title">📸 Add a profile photo <small>(optional)</small></p>
        <div class="booth-stage">
          <video autoplay playsinline muted hidden data-video></video>
          <canvas width="320" height="240" hidden data-canvas></canvas>
          <img alt="Your photo preview" hidden data-preview>
          <div class="booth-empty" data-empty>No photo yet</div>
        </div>
        <div class="booth-buttons">
          <button type="button" class="btn btn-small" data-start>Start camera</button>
          <button type="button" class="btn btn-small btn-pink" data-snap hidden>Take photo</button>
          <button type="button" class="btn btn-small btn-ghost" data-retake hidden>Retake</button>
        </div>
        <small class="booth-msg" data-msg></small>
      </div>

      <button class="btn btn-big btn-full" type="submit">Register</button>
      <p class="form-foot">Already registered? <a href="<?= e(url('login.php')) ?>">Log in</a></p>
    </form>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
