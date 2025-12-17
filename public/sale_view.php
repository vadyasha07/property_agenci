<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../partials/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo "<div class='alert alert--error'>Невірний ID</div>";
    require_once __DIR__ . '/../partials/footer.php';
    exit;
}

$stmt = db_prepare("
SELECT
  sa.app_s_id, sa.app_date, sa.price, sa.status, sa.notes AS sale_notes,
  sa.author_id,
  p.property_id, p.city, p.district, p.street, p.floor, p.total_floors, p.rooms,
  p.renovation_type, p.notes_prop,
  c.full_name AS owner_name, c.phone AS owner_phone, c.email AS owner_email
FROM sales_applications sa
JOIN properties p ON p.property_id = sa.property_id
JOIN clients c ON c.client_id = p.owner_id
WHERE sa.app_s_id = ?
LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    echo "<div class='alert alert--error'>Заявку не знайдено</div>";
    require_once __DIR__ . '/../partials/footer.php';
    exit;
}

function badge_class(string $s): string {
    return $s === 'new' ? 'badge--new' : ($s === 'in progress' ? 'badge--progress' : 'badge--closed');
}

$seed = (int)$row['property_id'];
$photo1 = "https://loremflickr.com/1100/520/apartment,interior?lock=" . ($seed*10 + 1);
$photo2 = "https://loremflickr.com/1000/520/house,interior?lock=" . ($seed*10 + 2);
$photo3 = "https://loremflickr.com/900/520/house,interior?lock=" . ($seed*10 + 3);

$fallback = "/property_agencie/assets/img/placeholder.jpg";
?>

<h2>Квартира / заявка #<?php echo (int)$row['app_s_id']; ?></h2>

<div class="card" style="padding:0; overflow:hidden;">
  <img
    src="<?php echo htmlspecialchars($photo1); ?>"
    alt="photo"
    style="width:100%; display:block; max-height:420px; object-fit:cover;"
    onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($fallback); ?>';"
  >
</div>

<div class="grid" style="margin-top:14px;">
  <div class="card">
    <h3 style="margin-top:0;">Основна інформація</h3>

    <p style="margin:0 0 8px;">
      <b><?php echo htmlspecialchars($row['city']); ?></b>, <?php echo htmlspecialchars($row['district']); ?><br>
      <span class="muted" style="opacity:.85;"><?php echo htmlspecialchars($row['street']); ?></span>
    </p>

    <div style="display:flex; gap:10px; flex-wrap:wrap; margin:10px 0;">
      <span class="badge">Кімнат: <?php echo (int)$row['rooms']; ?></span>
      <span class="badge">Поверх: <?php echo (int)$row['floor']; ?>/<?php echo (int)$row['total_floors']; ?></span>
      <?php if (!empty($row['renovation_type'])): ?>
        <span class="badge"><?php echo htmlspecialchars($row['renovation_type']); ?></span>
      <?php endif; ?>
      <span class="badge <?php echo badge_class($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span>
    </div>

    <h3>Ціна</h3>
    <div style="font-size:22px; font-weight:800; margin-bottom:10px;">
      <?php echo number_format((float)$row['price'], 2, '.', ' '); ?> $
    </div>

    <div class="muted" style="opacity:.85;">
      Дата заявки: <?php echo htmlspecialchars($row['app_date']); ?>
    </div>
  </div>

  <div class="card">
    <h3 style="margin-top:0;">Опис</h3>

    <?php if (!empty($row['notes_prop'])): ?>
      <div class="alert">
        <b>Нотатки по об’єкту:</b><br>
        <?php echo nl2br(htmlspecialchars($row['notes_prop'])); ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($row['sale_notes'])): ?>
      <div class="alert">
        <b>Нотатки до заявки:</b><br>
        <?php echo nl2br(htmlspecialchars($row['sale_notes'])); ?>
      </div>
    <?php endif; ?>

    <?php if (empty($row['notes_prop']) && empty($row['sale_notes'])): ?>
      <div class="muted" style="opacity:.85;">Опису поки немає.</div>
    <?php endif; ?>

    <h3 style="margin-top:16px;">Власник</h3>
    <div class="card" style="background:rgba(255,255,255,.03);">
      <div><b><?php echo htmlspecialchars($row['owner_name']); ?></b></div>
      <div class="muted" style="opacity:.85;">Тел: <?php echo htmlspecialchars($row['owner_phone']); ?></div>
      <?php if (!empty($row['owner_email'])): ?>
        <div class="muted" style="opacity:.85;">Email: <?php echo htmlspecialchars($row['owner_email']); ?></div>
      <?php endif; ?>
    </div>

    <div class="actions" style="margin-top:12px;">
      <a class="btn btn--ghost" href="<?php echo BASE_URL; ?>/sales_list.php">← Назад до списку</a>
      <?php if (function_exists('can_edit_sales_app') && can_edit_sales_app((int)$row['author_id'])): ?>
        <a class="btn" href="<?php echo BASE_URL; ?>/sales_edit.php?id=<?php echo (int)$row['app_s_id']; ?>">Редагувати</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="grid" style="margin-top:14px;">
  <div class="card" style="padding:0; overflow:hidden;">
    <img
      src="<?php echo htmlspecialchars($photo2); ?>"
      alt="photo2"
      style="width:100%; display:block; max-height:300px; object-fit:cover;"
      onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($fallback); ?>';"
    >
  </div>
  <div class="card" style="padding:0; overflow:hidden;">
    <img
      src="<?php echo htmlspecialchars($photo3); ?>"
      alt="photo3"
      style="width:100%; display:block; max-height:300px; object-fit:cover;"
      onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($fallback); ?>';"
    >
  </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
