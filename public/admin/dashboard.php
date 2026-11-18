<?php
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';

require_admin();

$pageTitle = 'Admin Dashboard';
$extraScripts = ['https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js'];
require __DIR__ . '/../../includes/header.php';
?>

<div data-dashboard data-url="<?= e(url('api/admin_stats.php')) ?>" data-qr-base="<?= e(url('qr.php')) ?>">
  <h1>Admin dashboard 📊</h1>
  <p class="live-note"><i class="dot"></i> Live — refreshes every 8 seconds · last update <span data-d="updated">…</span></p>

  <section class="stats">
    <div class="stat s1"><b data-d="students">–</b><span>Students</span></div>
    <div class="stat s2"><b data-d="evaluations">–</b><span>Evaluations</span></div>
    <div class="stat s3"><b data-d="units">–</b><span>Units</span></div>
    <div class="stat s4"><b data-d="average">–</b><span>Average ⭐</span></div>
  </section>

  <section class="charts">
    <div class="chart-card c1"><h3>Average rating per unit</h3><div class="chart-wrap"><canvas data-chart="units"></canvas></div></div>
    <div class="chart-card c2"><h3>Overall star distribution</h3><div class="chart-wrap"><canvas data-chart="dist"></canvas></div></div>
    <div class="chart-card c3"><h3>Average per question</h3><div class="chart-wrap"><canvas data-chart="questions"></canvas></div></div>
  </section>

  <section>
    <h2>Units &amp; QR codes</h2>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Code</th><th>Unit</th><th>Lecturer</th><th>Responses</th><th>Average</th><th>QR</th></tr></thead>
        <tbody data-d-units><tr><td colspan="6">Loading…</td></tr></tbody>
      </table>
    </div>
  </section>

  <section>
    <h2>Latest anonymous comments 💬</h2>
    <ul class="comments" data-d-comments><li>Loading…</li></ul>
  </section>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
