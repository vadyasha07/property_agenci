<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../partials/header.php';

require_login();
require_role(['admin']);

$sql = "
SELECT 
  pa.app_p_id, pa.app_date, pa.max_price, pa.status, 
  pa.notes, pa.city_req, pa.street_req, pa.district_req,
  c.full_name AS client_name
FROM purchase_applications pa
JOIN clients c ON c.client_id = pa.author_id
ORDER BY pa.app_date DESC
";

$stmt = db_prepare($sql);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function badge_class(string $s): string {
    return $s === 'new' ? 'badge--new' : ($s === 'in progress' ? 'badge--progress' : 'badge--closed');
}
?>

<h2>Заявки на покупку</h2>

<table class="table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Об'єкт</th>
      <th>Макс. ціна</th>
      <th>Дата</th>
      <th>Статус</th>
      <th>Нотатки</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td>
        <a href="<?php echo BASE_URL; ?>/sale_view.php?id=<?php echo (int)$r['app_p_id']; ?>" style="color:#cfe1ff; text-decoration:none;">
          <?php echo '#'.(int)$r['app_p_id']; ?>
        </a>
      </td>
      <td>
        <div><b><?php echo htmlspecialchars($r['city_req']); ?></b>, <?php echo htmlspecialchars($r['district_req']); ?></div>
        <div class="muted" style="opacity:.8;">Клієнт: <?php echo htmlspecialchars($r['client_name']); ?></div>
      </td>
      <td><?php echo number_format((float)$r['max_price'], 2, '.', ' '); ?> $</td>
      <td><?php echo htmlspecialchars($r['app_date']); ?></td>
      <td><span class="badge <?php echo badge_class($r['status']); ?>"><?php echo htmlspecialchars($r['status']); ?></span></td>
      <td><?php echo htmlspecialchars($r['notes'] ?? ''); ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?>
    <tr><td colspan="7" class="muted">Немає заявок.</td></tr>
  <?php endif; ?>
  </tbody>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>