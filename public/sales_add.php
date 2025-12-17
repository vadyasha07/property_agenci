<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../partials/header.php';

require_login();

$u = current_user();
$err = '';
$ok  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $city = trim($_POST['city'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $floor = trim($_POST['floor'] ?? '');
    $total_floors = trim($_POST['total_floors'] ?? '');
    $rooms = trim($_POST['rooms'] ?? '');
    $renovation_type = trim($_POST['renovation_type'] ?? '');
    $notes_prop = trim($_POST['notes_prop'] ?? '');

    $price = trim($_POST['price'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    $owner_id = 0;
    if ($u['role'] === 'client') {
        $owner_id = $u['user_id'];
    } else {
        $owner_id = (int)($_POST['owner_id'] ?? 0);
    }

    if ($city === '' || $district === '' || $street === '' || !is_numeric($price) || $owner_id <= 0) {
        $err = 'Заповни місто/район/вулицю, ціну і власника.';
    } else {
        db()->begin_transaction();
        try {
            $stmt = db_prepare("INSERT INTO properties(city,district,street,floor,total_floors,rooms,renovation_type,notes_prop,owner_id)
                                VALUES(?,?,?,?,?,?,?,?,?)");
            $floor_i = ($floor !== '' && ctype_digit($floor)) ? (int)$floor : 0;
            $tf_i    = ($total_floors !== '' && ctype_digit($total_floors)) ? (int)$total_floors : 0;
            $rooms_i = ($rooms !== '' && ctype_digit($rooms)) ? (int)$rooms : 0;

            $stmt->bind_param("sssiissii", $city, $district, $street, $floor_i, $tf_i, $rooms_i, $renovation_type, $notes_prop, $owner_id);
            $stmt->execute();
            $property_id = db()->insert_id;

            $stmt = db_prepare("INSERT INTO sales_applications(app_date, price, notes, author_id, property_id, status)
                                VALUES(CURDATE(), ?, NULLIF(?,''), ?, ?, 'new')");
            $price_f = (float)$price;

            $author_id = $owner_id;

            $stmt->bind_param("dsii", $price_f, $notes, $author_id, $property_id);
            $stmt->execute();

            db()->commit();
            $ok = 'Заявку на продаж додано!';
        } catch (mysqli_sql_exception $e) {
            db()->rollback();
            $err = 'Помилка БД: ' . $e->getMessage();
        }
    }
}

$clients = [];
if (is_logged_in() && (current_user()['role'] === 'admin' || current_user()['role'] === 'realtor')) {
    $res = db()->query("SELECT client_id, full_name, phone FROM clients ORDER BY full_name LIMIT 300");
    $clients = $res->fetch_all(MYSQLI_ASSOC);
}
?>

<h2>Додати заявку на продаж</h2>

<?php if ($err): ?><div class="alert alert--error"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
<?php if ($ok): ?><div class="alert alert--ok"><?php echo htmlspecialchars($ok); ?></div><?php endif; ?>

<div class="card">
  <form method="post">
    <?php if ($u['role'] !== 'client'): ?>
      <label>Власник (клієнт)</label>
      <select name="owner_id" required>
        <option value="">— вибери клієнта —</option>
        <?php foreach ($clients as $c): ?>
          <option value="<?php echo (int)$c['client_id']; ?>" <?php echo ((int)($_POST['owner_id'] ?? 0) === (int)$c['client_id']) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($c['full_name']); ?> (<?php echo htmlspecialchars($c['phone']); ?>)
          </option>
        <?php endforeach; ?>
      </select>
    <?php else: ?>
      <div class="alert">Ти додаєш продаж як клієнт: <b><?php echo htmlspecialchars($u['name']); ?></b></div>
    <?php endif; ?>

    <div class="form-row">
      <div>
        <label>Місто</label>
        <input name="city" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" required>
      </div>
      <div>
        <label>Район</label>
        <input name="district" value="<?php echo htmlspecialchars($_POST['district'] ?? ''); ?>" required>
      </div>
    </div>

    <label>Вулиця</label>
    <input name="street" value="<?php echo htmlspecialchars($_POST['street'] ?? ''); ?>" required>

    <div class="form-row">
      <div>
        <label>Поверх</label>
        <input name="floor" value="<?php echo htmlspecialchars($_POST['floor'] ?? ''); ?>" placeholder="5">
      </div>
      <div>
        <label>Поверхів всього</label>
        <input name="total_floors" value="<?php echo htmlspecialchars($_POST['total_floors'] ?? ''); ?>" placeholder="16">
      </div>
    </div>

    <div class="form-row">
      <div>
        <label>Кімнати</label>
        <input name="rooms" value="<?php echo htmlspecialchars($_POST['rooms'] ?? ''); ?>" placeholder="3">
      </div>
      <div>
        <label>Тип ремонту</label>
        <input name="renovation_type" value="<?php echo htmlspecialchars($_POST['renovation_type'] ?? ''); ?>" placeholder="Євроремонт">
      </div>
    </div>

    <label>Нотатки по об'єкту</label>
    <input name="notes_prop" value="<?php echo htmlspecialchars($_POST['notes_prop'] ?? ''); ?>">

    <div class="form-row">
      <div>
        <label>Ціна ($)</label>
        <input name="price" value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" required>
      </div>
      <div>
        <label>Нотатки до заявки</label>
        <input name="notes" value="<?php echo htmlspecialchars($_POST['notes'] ?? ''); ?>" placeholder="Торг можливий">
      </div>
    </div>

    <div class="actions">
      <button class="btn" type="submit">Зберегти</button>
      <a class="btn btn--ghost" href="<?php echo BASE_URL; ?>/sales_list.php">Назад</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
