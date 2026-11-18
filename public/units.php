<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

$user = require_login();

$units = [];
try {
    $stmt = db()->prepare(
        'SELECT u.id, u.code, u.title, l.full_name AS lecturer, c.title AS course,
                (SELECT COUNT(*) FROM submission_records s WHERE s.unit_id = u.id AND s.user_id = ?) AS done
         FROM units u
         JOIN lecturers l ON l.id = u.lecturer_id
         JOIN courses   c ON c.id = u.course_id
         ORDER BY c.title, u.code'
    );
    $stmt->execute([$user['id']]);
    $units = $stmt->fetchAll();
} catch (Throwable $ex) {
    error_log('Units error: ' . $ex->getMessage());
    flash('error', 'Could not load units right now.');
}

$pageTitle = 'Evaluate a Unit';
require __DIR__ . '/../includes/header.php';
?>

<h1>Pick a unit to evaluate 📝</h1>
<p class="lead">Already evaluated units show a ✅. You can evaluate each unit once.</p>

<div class="grid">
  <?php foreach ($units as $i => $u): ?>
    <div class="card unit-card c<?= ($i % 6) + 1 ?>">
      <span class="code"><?= e($u['code']) ?></span>
      <h3><?= e($u['title']) ?></h3>
      <p><?= e($u['lecturer']) ?><br><small><?= e($u['course']) ?></small></p>
      <?php if ((int)$u['done'] > 0): ?>
        <span class="done">✅ Evaluated – thank you!</span>
      <?php else: ?>
        <a class="btn btn-small" href="<?= e(url('evaluate.php?unit=' . (int)$u['id'])) ?>">Evaluate →</a>
      <?php endif; ?>
      <a class="qr-link" href="<?= e(url('qr.php?unit=' . (int)$u['id'])) ?>">Show QR</a>
    </div>
  <?php endforeach; ?>
  <?php if (!$units): ?>
    <p>No units found. Ask an administrator to add some (or import <code>sql/seed.sql</code>).</p>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
