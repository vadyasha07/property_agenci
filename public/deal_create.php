<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../partials/header.php';

require_login();
require_role(['admin', 'realtor']);

$err = '';
$ok  = '';

$sales = db()->query("
  SELECT sa.app_s_id, sa.price, sa.status, p.city, p.district, p.street
  FROM sales_applications sa
  JOIN properties p ON p.property_id = sa.property_id
  ORDER BY sa.app_date DESC
  LIMIT 200
")->fetch_all(MYSQLI_ASSOC);

$purchase = db()->query("
  SELECT app_p_id, city_req, district_req, max_price, status
  FROM purchase_applications
  ORDER BY app_date DESC
  LIMIT 200
")->fetch_all(MYSQLI_ASSOC);

$realtors = db()->query("
  SELECT realtor_id, full_name, role
  FROM realtors
  ORDER BY role DESC, full_name
")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $realtor_id = (int)($_POST['realtor_id'] ?? 0);
    $sales_id   = (int)($_POST['sales_app_id'] ?? 0);
    $pur_id     = (int)($_POST['purchase_app_id'] ?? 0);
    $final      = trim($_POST['final_price'] ?? '');

    if ($realtor_id <= 0 || $sales_id <= 0 || $pur_id <= 0 || !is_numeric($final)) {
        $err = 'Заповни всі поля коректно.';
    } else {
        try {
            $stmt = db_prepare("INSERT INTO deals(final_price, realtor_id, sales_app_id, purchase_app_id)
                                VALUES(?, ?, ?, ?)");
            $final_f = (float)$final;
            $stmt->bind_param("diii", $final_f, $realtor_id, $sales_id, $pur_id);
            $stmt->execute();
            $ok = 'Угоду створено! (deals)';
        } catch (mysqli_sql_exception $e) {
            $err = 'Помилка БД: ' . $e->getMessage();
        }
    }
}
?>

<h2>Створити угоду</h2>

<?php if ($err): ?><div class="alert alert--error"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
<?php if ($ok): ?><div class="alert alert--ok"><?php echo htmlspecialchars($ok); ?></div><?php endif; ?>

<div class="card">
  <form method="post">
    <label>Ріелтор</label>
    <select name="realtor_id" required>
      <option value="">— вибери —</option>
      <?php foreach ($realtors as $r): ?>
        <option value="<?php echo (int)$r['realtor_id']; ?>">
          <?php echo htmlspecialchars($r['full_name']); ?> (<?php echo htmlspecialchars($r['role']); ?>)
        </option>
      <?php endforeach; ?>
    </select>

    <label>Заявка на продаж</label>
    <select name="sales_app_id" required>
      <option value="">— вибери —</option>
      <?php foreach ($sales as $s): ?>
        <option value="<?php echo (int)$s['app_s_id']; ?>">
          <?php echo '#'.(int)$s['app_s_id']; ?> — <?php echo htmlspecialchars($s['city']); ?>, <?php echo htmlspecialchars($s['district']); ?> — <?php echo number_format((float)$s['price'], 0, '.', ' '); ?>$ (<?php echo htmlspecialchars($s['status']); ?>)
        </option>
      <?php endforeach; ?>
    </select>

    <label>Заявка на покупку</label>
    <select name="purchase_app_id" required>
      <option value="">— вибери —</option>
      <?php foreach ($purchase as $p): ?>
        <option value="<?php echo (int)$p['app_p_id']; ?>">
          <?php echo '#'.(int)$p['app_p_id']; ?> — <?php echo htmlspecialchars($p['city_req']); ?>, <?php echo htmlspecialchars($p['district_req']); ?> — max <?php echo number_format((float)$p['max_price'], 0, '.', ' '); ?>$ (<?php echo htmlspecialchars($p['status']); ?>)
        </option>
      <?php endforeach; ?>
    </select>

    <label>Фінальна ціна ($)</label>
    <input name="final_price" required placeholder="118000">

    <div class="actions">
      <button class="btn" type="submit">Створити</button>
      <a class="btn btn--ghost" href="<?php echo BASE_URL; ?>/index.php">На головну</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
