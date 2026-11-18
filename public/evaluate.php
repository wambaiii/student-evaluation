<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

// Students arriving from the QR code are sent to log in first, then returned here.
$user = require_login();

$unitId = filter_input(INPUT_GET, 'unit', FILTER_VALIDATE_INT) ?: 0;

$pdo = db();
$stmt = $pdo->prepare(
    'SELECT u.id, u.code, u.title, l.full_name AS lecturer
     FROM units u JOIN lecturers l ON l.id = u.lecturer_id WHERE u.id = ?'
);
$stmt->execute([$unitId]);
$unit = $stmt->fetch();

if (!$unit) {
    http_response_code(404);
    $pageTitle = 'Unit not found';
    require __DIR__ . '/../includes/header.php';
    echo '<h1>Unit not found 🤔</h1><p><a href="' . e(url('units.php')) . '">Back to units</a></p>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

// The five rating questions: field name => label shown to the student
const QUESTIONS = [
    'teaching_quality' => 'Quality of teaching',
    'clarity'          => 'Clarity of explanations',
    'engagement'       => 'Keeps the class engaged',
    'punctuality'      => 'Punctuality & time management',
    'materials'        => 'Notes & learning materials',
];

// Has this student already evaluated this unit?
$dup = $pdo->prepare('SELECT 1 FROM submission_records WHERE user_id = ? AND unit_id = ?');
$dup->execute([$user['id'], $unit['id']]);
$alreadyDone = (bool)$dup->fetchColumn();

$errors = [];
$submitted = false;
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyDone) {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $ratings = [];
        foreach (QUESTIONS as $field => $label) {
            $v = filter_var($_POST[$field] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
            if ($v === false || $v === null) {
                $errors[] = 'Please rate: ' . $label . '.';
            } else {
                $ratings[$field] = $v;
                $old[$field] = $v;
            }
        }
        $comment = trim((string)($_POST['comment'] ?? ''));
        $old['comment'] = $comment;
        if (mb_strlen($comment) > 1000) {
            $errors[] = 'Comment must be 1000 characters or fewer.';
        }

        if (!$errors) {
            try {
                $pdo->beginTransaction();

                // 1) Record that this student has evaluated this unit.
                //    The UNIQUE(user_id, unit_id) key makes a double submission impossible,
                //    even if the button is double-clicked or two tabs submit at once.
                $pdo->prepare('INSERT INTO submission_records (user_id, unit_id, submitted_on) VALUES (?, ?, CURDATE())')
                    ->execute([$user['id'], $unit['id']]);

                // 2) Store the feedback itself - with NO link to the student.
                $pdo->prepare(
                    'INSERT INTO evaluations
                       (unit_id, teaching_quality, clarity, engagement, punctuality, materials, comment, submitted_on)
                     VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())'
                )->execute([
                    $unit['id'], $ratings['teaching_quality'], $ratings['clarity'],
                    $ratings['engagement'], $ratings['punctuality'], $ratings['materials'],
                    $comment !== '' ? $comment : null,
                ]);

                $pdo->commit();
                $submitted = true;
                $alreadyDone = true;
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($ex->getCode() === '23000') {
                    $alreadyDone = true;
                    $errors[] = 'You have already evaluated this unit.';
                } else {
                    error_log('Evaluate error: ' . $ex->getMessage());
                    $errors[] = 'Something went wrong. Please try again.';
                }
            }
        }
    }
}

$pageTitle = 'Evaluate ' . $unit['code'];
$bodyAttrs = $submitted ? 'data-confetti="1"' : '';
require __DIR__ . '/../includes/header.php';
?>

<section class="form-page wide">
  <div class="form-card c1">
    <p class="code-pill"><?= e($unit['code']) ?></p>
    <h1><?= e($unit['title']) ?></h1>
    <p class="lead">Lecturer: <strong><?= e($unit['lecturer']) ?></strong></p>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <?php if ($submitted): ?>
      <div class="thanks">
        <div class="thanks-emoji">🎉</div>
        <h2>Thank you!</h2>
        <p>Your anonymous feedback has been recorded.</p>
        <a class="btn" href="<?= e(url('units.php')) ?>">Evaluate another unit</a>
      </div>
    <?php elseif ($alreadyDone): ?>
      <div class="thanks">
        <div class="thanks-emoji">✅</div>
        <h2>Already evaluated</h2>
        <p>You have already given feedback for this unit. Thank you!</p>
        <a class="btn" href="<?= e(url('units.php')) ?>">Back to units</a>
      </div>
    <?php else: ?>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <?php foreach (QUESTIONS as $field => $label): ?>
          <fieldset class="rating-q">
            <legend><?= e($label) ?></legend>
            <div class="stars" data-stars>
              <?php for ($n = 5; $n >= 1; $n--): ?>
                <input type="radio" id="<?= e($field . $n) ?>" name="<?= e($field) ?>" value="<?= $n ?>"
                  <?= ((int)($old[$field] ?? 0) === $n) ? 'checked' : '' ?>>
                <label for="<?= e($field . $n) ?>" title="<?= $n ?> star<?= $n > 1 ? 's' : '' ?>">★</label>
              <?php endfor; ?>
              <span class="star-word" data-star-word></span>
            </div>
          </fieldset>
        <?php endforeach; ?>

        <label class="comment-label">Anything else you'd like to say? <small>(optional, anonymous)</small>
          <textarea name="comment" rows="4" maxlength="1000" data-comment><?= e($old['comment'] ?? '') ?></textarea>
          <small class="counter"><span data-count>0</span>/1000</small>
        </label>

        <p class="privacy-note">🔒 Your name is <strong>not</strong> stored with your answers.</p>
        <button class="btn btn-big btn-full" type="submit">Submit feedback 🚀</button>
      </form>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
