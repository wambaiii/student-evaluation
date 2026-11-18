<?php
declare(strict_types=1);
$extraScripts = $extraScripts ?? [];
?>
</main>

<footer class="site-footer">
  <p>🍌 <?= e(APP_NAME) ?> · Anonymous lecturer &amp; unit evaluation · <span data-live-clock></span></p>
</footer>

<?php foreach ($extraScripts as $src): ?>
  <script src="<?= e($src) ?>"></script>
<?php endforeach; ?>
<script src="<?= e(url('assets/js/app.js')) ?>" data-base="<?= e(base_path()) ?>"></script>
</body>
</html>
