<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../partials/header.php';

start_session();

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($login === '' || $pass === '') {
        $err = 'Введи логін і пароль.';
    } else {
        if ($login === ADMIN_LOGIN) {
            if ($pass === ADMIN_PASS) {
                $_SESSION['user_id'] = 1;
                $_SESSION['user_type'] = 'staff';
                $_SESSION['role'] = 'admin';
                $_SESSION['name'] = 'Admin';
                header('Location: /property_agencie/admin/index.php');
                exit;
            } else {
                $err = 'Невірний пароль (admin).';
            }
        }
        elseif ($login === REALTOR_LOGIN) {
            if ($pass === REALTOR_PASS) {
                $_SESSION['user_id'] = 2;
                $_SESSION['user_type'] = 'staff';
                $_SESSION['role'] = 'realtor';
                $_SESSION['name'] = 'Realtor';
                header('Location: /property_agencie/realtor/index.php');
                exit;
            } else {
                $err = 'Невірний пароль (realtor).';
            }
        }
        else {
            $stmt = db_prepare("SELECT client_id, full_name, password_hash
                                FROM clients
                                WHERE email = ? OR phone = ?
                                LIMIT 1");
            $stmt->bind_param("ss", $login, $login);
            $stmt->execute();
            $cl = $stmt->get_result()->fetch_assoc();

            if (!$cl) {
                $err = 'Користувача не знайдено.';
            } elseif (empty($cl['password_hash'])) {
                $err = 'У цього клієнта пароль не встановлений (password_hash = NULL). Зареєструйся заново.';
            } elseif (!password_verify($pass, $cl['password_hash'])) {
                $err = 'Невірний пароль.';
            } else {
                $_SESSION['user_id'] = (int)$cl['client_id'];
                $_SESSION['user_type'] = 'client';
                $_SESSION['role'] = 'client';
                $_SESSION['name'] = $cl['full_name'];
                header('Location: ' . BASE_URL . '/index.php');
                exit;
            }
        }
    }
}
?>

<h2>Вхід</h2>
<?php if ($err): ?><div class="alert alert--error"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

<div class="card">
  <form method="post">
    <label>Логін</label>
    <input name="login" value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>"
           placeholder="телефон/email (клієнт) або admin/realtor">

    <label>Пароль</label>
    <input type="password" name="password">

    <div class="actions">
      <button class="btn" type="submit">Увійти</button>
      <a class="btn btn--ghost" href="<?php echo BASE_URL; ?>/register.php">Реєстрація клієнта</a>
    </div>

    <div class="alert" style="margin-top:12px;">
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
