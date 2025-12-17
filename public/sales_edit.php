<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../partials/header.php';

require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { echo "Невірний ID"; exit; }

$stmt = db_prepare("
SELECT sa.app_s_id, sa.price, sa.notes, sa.status, sa.author_id,
       p.property_id, p.city, p.district, p.street, p.floor, p.total_floors, p.rooms, p.renovation_type, p.notes_prop
FROM sales_applications sa
JOIN properties p ON p.property_id = sa.property_id
WHERE sa.app_s_id = ?
LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) { echo "Не знайдено"; exit; }
if (!can_edit_sales_app((int)$data['author_id'])) { http_response_code(403); echo "403: немає прав"; exit; }

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
    $status = trim($_POST['status'] ?? 'new');

    if ($city === '' || $district === '' || $street === '' || !is_numeric($price)) {
        $err = 'Заповни місто/район/вулицю і ціну.';
    } else {
        db()->begin_transaction();
        try {
            $floor_i = ($floor !== '' && ctype_digit($floor)) ? (int)$floor : 0;
            $tf_i    = ($total_floors !== '' && ctype_digit($total_floors)) ? (int)$total_floors : 0;
            $rooms_i = ($rooms !== '' && ctype_digit($rooms)) ? (int)$rooms : 0;

            $stmt = db_prepare("UPDATE properties
                                SET city=?, district=?, street=?, floor=?, total_floors=?, rooms=?, renovation_type=?, notes_prop=?
                                WHERE property_id=?");
            $stmt->bind_param("sssiiissi", $city, $district, $street, $floor_i, $tf_i, $rooms_i, $renovation_type, $notes_prop, $data['property_id']);
            $stmt->execute();

            $stmt = db_prepare("UPDATE sales_applications
                                SET price=?, notes=NULLIF(?,''), status=?
                                WHERE app_s_id=?");
            $price_f = (float)$price;
            $stmt->bind_param("dssi", $price_f, $notes, $status, $id);
            $stmt->execute();

            db()->commit();
            $ok = 'Збережено.';
        } catch (mysqli_sql_exception $e) {
            db()->rollback();
            $err = 'Помилка БД: ' . $e->getMessage();
      }
    }
}

?>

<h2>Редагувати заявку #<?php echo (int)$id; ?></h2>



<?php if ($err): ?><div class="alert alert--error"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
<?php if ($ok): ?><div class="alert alert--ok"><?php echo htmlspecialchars($ok); ?></div><?php endif; ?>

<div class="card">
  <form method="post">
    <div class="form-row">
      <div>
        <label>Місто</label>
        <input name="city" value="<?php echo htmlspecialchars($_POST['city'] ?? $data['city']); ?>" required>
      </div>
      <div>
        <label>Район</label>
        <input name="district" value="<?php echo htmlspecialchars($_POST['district'] ?? $data['district']); ?>" required>
      </div>
    </div>

    <label>Вулиця</label>
    <input name="street" value="<?php echo htmlspecialchars($_POST['street'] ?? $data['street']); ?>" required>

    <div class="form-row">
      <div>
        <label>Поверх</label>
        <input name="floor" value="<?php echo htmlspecialchars($_POST['floor'] ?? $data['floor']); ?>">
      </div>
      <div>
        <label>Поверхів всього</label>
        <input name="total_floors" value="<?php echo htmlspecialchars($_POST['total_floors'] ?? $data['total_floors']); ?>">
      </div>
    </div>

    <div class="form-row">
      <div>
        <label>Кімнати</label>
        <input name="rooms" value="<?php echo htmlspecialchars($_POST['rooms'] ?? $data['rooms']); ?>">
      </div>
      <div>
        <label>Тип ремонту</label>
        <input name="renovation_type" value="<?php echo htmlspecialchars($_POST['renovation_type'] ?? $data['renovation_type']); ?>">
      </div>
    </div>

    <label>Нотатки по об'єкту</label>
    <input name="notes_prop" value="<?php echo htmlspecialchars($_POST['notes_prop'] ?? $data['notes_prop']); ?>">

    <div class="form-row">
      <div>
        <label>Ціна ($)</label>
        <input name="price" value="<?php echo htmlspecialchars($_POST['price'] ?? $data['price']); ?>" required>
      </div>
      <div>
        <label>Статус</label>
        <select name="status">
          <?php $cur = $_POST['status'] ?? $data['status']; ?>
          <option value="new" <?php echo $cur === 'new' ? 'selected' : ''; ?>>new</option>
          <option value="in progress" <?php echo $cur === 'in progress' ? 'selected' : ''; ?>>in progress</option>
          <option value="closed" <?php echo $cur === 'closed' ? 'selected' : ''; ?>>closed</option>
        </select>
      </div>
    </div>

    <label>Нотатки до заявки</label>
    <textarea name="notes"><?php echo htmlspecialchars($_POST['notes'] ?? $data['notes'] ?? ''); ?></textarea>

    <div class="actions">
      <button class="btn" type="submit">Зберегти</button>
      <a class="btn btn--ghost" href="<?php echo BASE_URL; ?>/sales_list.php">Назад</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
