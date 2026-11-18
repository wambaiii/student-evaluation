<?php
declare(strict_types=1);
/** Usage: $pageTitle = '...'; $extraScripts = [...]; require header.php */
$pageTitle    = $pageTitle ?? 'Home';
$extraScripts = $extraScripts ?? [];
$bodyAttrs    = $bodyAttrs ?? '';
$user         = current_user();
$here         = basename($_SERVER['SCRIPT_NAME'] ?? '');
function nav_link(string $file, string $label, string $here): string
{
    $active = $here === basename($file) ? ' class="active"' : '';
    return '<a' . $active . ' href="' . e(url($file)) . '">' . e($label) . '</a>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;700;800&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body <?= $bodyAttrs ?>>
<div class="floaties" aria-hidden="true">
  <span>🍌</span><span>⭐</span><span>🍌</span><span>🎓</span><span>🍌</span><span>✨</span><span>🍌</span>
</div>

<header class="site-header">
  <a class="brand" href="<?= e(url('index.php')) ?>"><span class="brand-logo">🍌</span> <?= e(APP_NAME) ?></a>
  <button class="nav-toggle" type="button" aria-label="Menu" data-nav-toggle>☰</button>
  <nav class="nav" data-nav>
    <?= nav_link('index.php', 'Home', $here) ?>
    <?php if ($user): ?>
      <?= nav_link('units.php', 'Evaluate a Unit', $here) ?>
      <?php if ($user['role'] === 'admin'): ?>
        <a href="<?= e(url('admin/dashboard.php')) ?>">Dashboard</a>
      <?php endif; ?>
      <span class="nav-user">
        <?php if (!empty($user['avatar'])): ?>
          <img class="avatar-sm" src="<?= e(url($user['avatar'])) ?>" alt="">
        <?php endif; ?>
        <?= e($user['name']) ?>
      </span>
      <form method="post" action="<?= e(url('logout.php')) ?>" class="inline-form">
        <?= csrf_field() ?>
        <button class="btn btn-small btn-ghost" type="submit">Log out</button>
      </form>
    <?php else: ?>
      <?= nav_link('login.php', 'Log in', $here) ?>
      <a class="btn btn-small" href="<?= e(url('register.php')) ?>">Register</a>
    <?php endif; ?>
  </nav>
</header>

<main class="container">
<?php foreach (take_flashes() as $f): ?>
  <div class="alert alert-<?= e($f['type']) ?>" role="alert"><?= e($f['message']) ?></div>
<?php endforeach; ?>
