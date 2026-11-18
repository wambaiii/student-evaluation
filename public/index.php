<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'Home';

// Featured units for the home page. If the database is not set up yet we still show the page.
$units = [];
$dbError = false;
try {
    $units = db()->query(
        'SELECT u.id, u.code, u.title, l.full_name AS lecturer
         FROM units u JOIN lecturers l ON l.id = u.lecturer_id
         ORDER BY u.code LIMIT 6'
    )->fetchAll();
} catch (Throwable $ex) {
    $dbError = true;
}

require __DIR__ . '/../includes/header.php';
?>

<?php if ($dbError): ?>
  <div class="alert alert-error">Database not connected yet. Check your <code>.env</code> settings and that <code>sql/schema.sql</code> has been imported.</div>
<?php endif; ?>

<section class="hero">
  <div class="hero-text">
    <p class="pill">🎉 Your voice. Anonymous. Always.</p>
    <h1>Rate your lecturers.<br><span class="gradient-text">Shape your learning.</span></h1>
    <p class="lead">Scan a QR code in class, tell us honestly how a unit is going, and help <?= e(APP_NAME) ?> get better. Your name is never attached to your feedback.</p>
    <div class="hero-actions">
      <?php if (current_user()): ?>
        <a class="btn btn-big" href="<?= e(url('units.php')) ?>">Evaluate a unit →</a>
      <?php else: ?>
        <a class="btn btn-big" href="<?= e(url('register.php')) ?>">Join now →</a>
        <a class="btn btn-big btn-ghost" href="<?= e(url('login.php')) ?>">I have an account</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- LIVE PHOTO CAROUSEL: real campus photos from your public/img folder -->
  <div class="live-frame" data-carousel data-interval="5000">
    <span class="live-badge"><i></i> LIVE</span>
    <img class="live-img live-img-a" alt="Campus view" src="/img/campus1.jpg" data-slide>
    <img class="live-img live-img-b" alt="" src="/img/campus2.jpg" data-slide>
    <div class="live-caption" data-caption>Real campus views</div>
    <div class="live-controls">
      <button type="button" class="btn btn-small btn-ghost" data-carousel-pause>⏸ Pause</button>
      <button type="button" class="btn btn-small" data-carousel-next>Next 📷</button>
    </div>
  </div>
</section>

<!-- LIVE NUMBERS: refreshed from the server every 10 seconds -->
<section class="stats" data-live-stats data-url="<?= e(url('api/stats.php')) ?>">
  <div class="stat s1"><b data-stat="students">–</b><span>Students</span></div>
  <div class="stat s2"><b data-stat="evaluations">–</b><span>Evaluations</span></div>
  <div class="stat s3"><b data-stat="units">–</b><span>Units</span></div>
  <div class="stat s4"><b data-stat="average">–</b><span>Average rating ⭐</span></div>
  <p class="live-note"><i class="dot"></i> Updating live · <span data-stat="updated">…</span></p>
</section>

<section class="how">
  <h2>How it works</h2>
  <div class="steps">
    <div class="step c1"><span>1</span><h3>Register</h3><p>Create an account with your admission number. Add a quick webcam photo if you like.</p></div>
    <div class="step c2"><span>2</span><h3>Scan the QR</h3><p>Your lecturer shows a QR code for the unit. Scan it with your phone.</p></div>
    <div class="step c3"><span>3</span><h3>Rate &amp; comment</h3><p>Give 1–5 stars on five questions and add an optional comment.</p></div>
    <div class="step c4"><span>4</span><h3>Stay anonymous</h3><p>We only record <em>that</em> you evaluated, never <em>what</em> you wrote.</p></div>
  </div>
</section>

<?php if ($units): ?>
<section>
  <h2>Units open for evaluation</h2>
  <div class="grid">
    <?php foreach ($units as $i => $u): ?>
      <a class="card unit-card c<?= ($i % 6) + 1 ?>" href="<?= e(url('evaluate.php?unit=' . (int)$u['id'])) ?>">
        <span class="code"><?= e($u['code']) ?></span>
        <h3><?= e($u['title']) ?></h3>
        <p><?= e($u['lecturer']) ?></p>
        <span class="go">Evaluate →</span>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>