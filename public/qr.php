<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

/**
 * Printable QR "poster" for one unit. The lecturer shows this in class;
 * students scan it and land on evaluate.php for that unit.
 */
$unitId = filter_input(INPUT_GET, 'unit', FILTER_VALIDATE_INT) ?: 0;

$stmt = db()->prepare(
    'SELECT u.id, u.code, u.title, l.full_name AS lecturer
     FROM units u JOIN lecturers l ON l.id = u.lecturer_id WHERE u.id = ?'
);
$stmt->execute([$unitId]);
$unit = $stmt->fetch();

if (!$unit) {
    http_response_code(404);
    exit('Unit not found.');
}

// The address encoded in the QR code. On Azure, Member 1 sets APP_URL in the server's
// settings (e.g. https://evaluate.yourdomain.com); locally it is built from the request.
$appUrl = env('APP_URL');
if ($appUrl === null) {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!preg_match('/^[A-Za-z0-9.\-]+(:\d{1,5})?$/', $host)) {   // reject odd Host headers
        $host = 'localhost';
    }
    $appUrl = (is_https() ? 'https://' : 'http://') . $host . base_path();
}
$target = rtrim($appUrl, '/') . '/evaluate.php?unit=' . (int)$unit['id'];

$pageTitle = 'QR for ' . $unit['code'];
$extraScripts = ['https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js'];
require __DIR__ . '/../includes/header.php';
?>

<section class="qr-page">
  <div class="qr-card">
    <p class="code-pill"><?= e($unit['code']) ?></p>
    <h1><?= e($unit['title']) ?></h1>
    <p class="lead"><?= e($unit['lecturer']) ?></p>

    <div class="qr-box" data-qr data-qr-text="<?= e($target) ?>" aria-label="QR code for evaluating <?= e($unit['title']) ?>"></div>

    <p class="qr-cta">📱 Scan to evaluate this unit — it takes 1 minute!</p>
    <p class="qr-url"><?= e($target) ?></p>

    <div class="no-print qr-actions">
      <button class="btn" type="button" data-print>🖨 Print poster</button>
      <button class="btn btn-ghost" type="button" data-qr-download>⬇ Download PNG</button>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
