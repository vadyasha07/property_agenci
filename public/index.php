<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../partials/header.php';

$u = current_user();

if ($u && ($u['role'] === 'admin' || $u['role'] === 'realtor') && (($u['user_type'] ?? '') === 'staff')) {
    if ($u['role'] === 'admin') { header('Location: /property_agencie/admin/index.php'); exit; }
    if ($u['role'] === 'realtor') { header('Location: /property_agencie/realtor/index.php'); exit; }
}

$stmt = db_prepare("
    SELECT pa.app_p_id, pa.city_req, pa.district_req, pa.street_req,
           pa.app_date, pa.max_price, pa.notes, pa.status,
           c.full_name AS author_name
    FROM purchase_applications pa
    JOIN clients c ON c.client_id = pa.author_id
    ORDER BY pa.app_date DESC
    LIMIT 3
");
$stmt->execute();
$purchases = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function badge_class(string $s): string {
    return $s === 'new' ? 'badge--new' : ($s === 'in progress' ? 'badge--progress' : 'badge--closed');
}
?>

<h2 style="margin-top:0;">Останні заявки на покупку</h2>

<?php if (!$purchases): ?>
  <div class="card">
    <div class="muted" style="opacity:.85;">Поки немає заявок на покупку.</div>
  </div>
<?php else: ?>

  <div class="cards">
    <?php foreach ($purchases as $p): ?>
      <?php
        $seed = (int)$p['app_p_id'];

        $photo = "https://loremflickr.com/820/520/apartment,interior,house?lock=" . $seed;

        $fallback = "/property_agencie/assets/img/placeholder.jpg";
      ?>

      <div class="card card--listing">
        <div class="card--listing__img">
          <img
            src="<?php echo htmlspecialchars($photo); ?>"
            alt="photo"
            onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($fallback); ?>';"
          >
          <span class="badge <?php echo badge_class($p['status']); ?> card--listing__badge">
            <?php echo htmlspecialchars($p['status']); ?>
          </span>
        </div>

        <div class="card--listing__body">
          <div class="card--listing__title">
            <b><?php echo htmlspecialchars($p['city_req']); ?></b>, <?php echo htmlspecialchars($p['district_req']); ?>
          </div>

          <div class="muted" style="opacity:.85;">
            <?php echo $p['street_req'] ? htmlspecialchars($p['street_req']) : 'Вулиця не важлива'; ?>
          </div>

          <div style="margin:10px 0; display:flex; gap:10px; flex-wrap:wrap;">
            <span class="badge">Клієнт: <?php echo htmlspecialchars($p['author_name']); ?></span>
            <span class="badge">Дата: <?php echo htmlspecialchars($p['app_date']); ?></span>
          </div>

          <div class="card--listing__price">
            <?php echo $p['max_price'] !== null ? number_format((float)$p['max_price'], 2, '.', ' ') . ' $' : 'Без бюджету'; ?>
          </div>

          <?php if (!empty($p['notes'])): ?>
            <div class="muted" style="opacity:.85; margin-top:8px;">
              <?php echo htmlspecialchars(mb_strimwidth($p['notes'], 0, 90, '...')); ?>
            </div>
          <?php endif; ?>

          <div class="actions" style="margin-top:12px;">
            <a class="btn btn--ghost" href="<?php echo BASE_URL; ?>/sales_list.php">Дивитись продажі</a>
            <?php if (is_logged_in()): ?>
              <a class="btn" href="<?php echo BASE_URL; ?>/sales_add.php">+ Додати продаж</a>
            <?php else: ?>
              <a class="btn" href="<?php echo BASE_URL; ?>/login.php">Увійти</a>
            <?php endif; ?>

          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

<?php endif; ?>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
