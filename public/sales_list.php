<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../partials/header.php';

$city     = trim($_GET['city'] ?? '');
$district = trim($_GET['district'] ?? '');
$rooms    = trim($_GET['rooms'] ?? '');
$status   = trim($_GET['status'] ?? '');
$minp     = trim($_GET['min_price'] ?? '');
$maxp     = trim($_GET['max_price'] ?? '');

$where = [];
$params = [];
$types = '';

if ($city !== '')     { $where[] = "p.city LIKE ?";     $params[] = "%$city%"; $types .= 's'; }
if ($district !== '') { $where[] = "p.district LIKE ?"; $params[] = "%$district%"; $types .= 's'; }
if ($rooms !== '' && ctype_digit($rooms)) { $where[] = "p.rooms = ?"; $params[] = (int)$rooms; $types .= 'i'; }
if ($status !== '')   { $where[] = "sa.status = ?";     $params[] = $status; $types .= 's'; }
if ($minp !== '' && is_numeric($minp)) { $where[] = "sa.price >= ?"; $params[] = (float)$minp; $types .= 'd'; }
if ($maxp !== '' && is_numeric($maxp)) { $where[] = "sa.price <= ?"; $params[] = (float)$maxp; $types .= 'd'; }

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

$sql = "
SELECT 
  sa.app_s_id, sa.app_date, sa.price, sa.status,
  p.city, p.district, p.street, p.rooms, p.floor, p.total_floors, p.renovation_type,
  c.full_name AS owner_name,
  sa.author_id
FROM sales_applications sa
JOIN properties p ON p.property_id = sa.property_id
JOIN clients c ON c.client_id = p.owner_id
$whereSql
";

$stmt = db_prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function badge_class(string $s): string {
    return $s === 'new' ? 'badge--new' : ($s === 'in progress' ? 'badge--progress' : 'badge--closed');
}
?>

<h2>Заявки на продаж</h2>

<div class="card">
  <form method="get">
    <div class="form-row">
      <div>
        <label>Місто</label>
        <input name="city" value="<?php echo htmlspecialchars($city); ?>">
      </div>
      <div>
        <label>Район</label>
        <input name="district" value="<?php echo htmlspecialchars($district); ?>">
      </div>
    </div>

    <div class="form-row">
      <div>
        <label>Кімнати</label>
        <input name="rooms" value="<?php echo htmlspecialchars($rooms); ?>" placeholder="2">
      </div>
      <div>
        <label>Статус</label>
        <select name="status">
          <option value="">— будь-який —</option>
          <option value="new" <?php echo $status === 'new' ? 'selected' : ''; ?>>new</option>
          <option value="in progress" <?php echo $status === 'in progress' ? 'selected' : ''; ?>>in progress</option>
          <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>closed</option>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div>
        <label>Ціна від</label>
        <input name="min_price" value="<?php echo htmlspecialchars($minp); ?>" placeholder="50000">
      </div>
      <div>
        <label>Ціна до</label>
        <input name="max_price" value="<?php echo htmlspecialchars($maxp); ?>" placeholder="150000">
      </div>
    </div>
      <button class="btn" type="submit">Застосувати</button>
      <a class="btn btn--ghost" href="<?php echo BASE_URL; ?>/sales_list.php">Скинути</a>
      <?php if (is_logged_in()): ?>
        <a class="btn btn--ghost" href="<?php echo BASE_URL; ?>/sales_add.php">+ Додати продаж</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<br>

<table class="table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Об'єкт</th>
      <th>Ціна</th>
      <th>Дата</th>
      <th>Статус</th>
      <th>Дії</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td>
  <a href="<?php echo BASE_URL; ?>/sale_view.php?id=<?php echo (int)$r['app_s_id']; ?>" style="color:#cfe1ff; text-decoration:none;">
    <?php echo '#'.(int)$r['app_s_id']; ?>
  </a>
</td>

      <td>
  <a href="<?php echo BASE_URL; ?>/sale_view.php?id=<?php echo (int)$r['app_s_id']; ?>" style="color:inherit; text-decoration:none;">
    <div><b><?php echo htmlspecialchars($r['city']); ?></b>, <?php echo htmlspecialchars($r['district']); ?></div>
    <div class="muted" style="opacity:.8;">
      <?php echo htmlspecialchars($r['street']); ?> • <?php echo (int)$r['rooms']; ?> кімн.
      • <?php echo (int)$r['floor']; ?>/<?php echo (int)$r['total_floors']; ?>
    </div>
    <div class="muted" style="opacity:.8;">Власник: <?php echo htmlspecialchars($r['owner_name']); ?></div>
  </a>
</td>

      <td><?php echo number_format((float)$r['price'], 2, '.', ' '); ?> $</td>
      <td><?php echo htmlspecialchars($r['app_date']); ?></td>
      <td><span class="badge <?php echo badge_class($r['status']); ?>"><?php echo htmlspecialchars($r['status']); ?></span></td>
      <td>
        <?php if (can_edit_sales_app((int)$r['author_id'])): ?>
          <a class="btn btn--ghost" href="<?php echo BASE_URL; ?>/sales_edit.php?id=<?php echo (int)$r['app_s_id']; ?>">Edit</a>
        <?php endif; ?>
        
        <?php if (is_logged_in() && (current_user()['role'] === 'admin' || (current_user()['role'] === 'client' && current_user()['user_id'] === (int)$r['author_id']))): ?>
          <a class="btn btn--danger" href="<?php echo BASE_URL; ?>/sales_delete.php?id=<?php echo (int)$r['app_s_id']; ?>" onclick="return confirm('Точно видалити?')">Delete</a>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>

  <?php if (!$rows): ?>
    <tr><td colspan="6" class="muted">Нічого не знайдено.</td></tr>
  <?php endif; ?>
  </tbody>
</table>


<?php require_once __DIR__ . '/../partials/footer.php'; ?>
