<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { echo "Невірний ID"; exit; }

$stmt = db_prepare("SELECT author_id, property_id FROM sales_applications WHERE app_s_id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) { echo "Не знайдено"; exit; }

$u = current_user();
$can = false;

if ($u['role'] === 'admin') $can = true;

if ($u['role'] === 'client' && $u['user_id'] === (int)$row['author_id']) $can = true;

if (!$can) { http_response_code(403); echo "403: немає прав"; exit; }

db()->begin_transaction();
try {
    $stmt = db_prepare("DELETE FROM sales_applications WHERE app_s_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $pid = (int)$row['property_id'];
    $stmt = db_prepare("DELETE FROM properties WHERE property_id = ?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();

    db()->commit();
} catch (mysqli_sql_exception $e) {
    db()->rollback();
    echo "Помилка БД: " . $e->getMessage();
    exit;
}

header('Location: ' . BASE_URL . '/sales_list.php');
exit;
