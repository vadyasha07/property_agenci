<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../partials/header.php';

require_login();
require_role(['admin']);
?>


<h2>Admin Panel</h2>

<div class="card">
  <div class="actions">
    <a class="btn" href="/property_agencie/admin/purchases.php">Заявки на покупку</a>
    <a class="btn btn--ghost" href="<?php echo BASE_URL; ?>/sales_list.php">Заявки на продаж</a>
    <a class="btn btn--ghost" href="<?php echo BASE_URL; ?>/deal_create.php">Створити угоду</a>
  </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
