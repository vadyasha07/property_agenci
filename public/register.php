<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../partials/header.php';

start_session();

$err = '';
$ok  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $pass1     = $_POST['password'] ?? '';
    $pass2     = $_POST['password2'] ?? '';

    if ($full_name === '' || $phone === '' || $pass1 === '') {
        $err = 'Заповни ім’я, телефон і пароль.';
    } elseif ($pass1 !== $pass2) {
        $err = 'Паролі не співпадають.';
    } elseif (mb_strlen($pass1) < 6) {
        $err = 'Пароль має бути мінімум 6 символів.';
    } else {
        $hash = password_hash($pass1, PASSWORD_DEFAULT);

        try {
            $stmt = db_prepare("INSERT INTO clients(full_name, reg_date, phone, email, notes_client, password_hash)
                               VALUES(?, CURDATE(), ?, NULLIF(?, ''), NULL, ?)");
            $stmt->bind_param("ssss", $full_name, $phone, $email, $hash);
            $stmt->execute();

            $ok = 'Акаунт створено! Тепер увійди.';
        } catch (mysqli_sql_exception $e) {
            if (str_contains($e->getMessage(), 'Duplicate')) {
                $err = 'Телефон або email вже зайняті.';
            } else {
                $err = 'Помилка БД: ' . $e->getMessage();
            }
        }
    }
}
?>

<h2>Реєстрація (Клієнт)</h2>

<?php if ($err): ?><div class="alert alert--error"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
<?php if ($ok): ?><div class="alert alert--ok"><?php echo htmlspecialchars($ok); ?></div><?php endif; ?>

<div class="card">
  <form method="post">
    <div class="form-row">
      <div>
        <label>ПІБ</label>
        <input name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" placeholder="Напр. Іван Петров">
      </div>
      <div>
        <label>Телефон</label>
        <input name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" placeholder="+380...">
      </div>
    </div>

    <label>Email (необов’язково)</label>
    <input name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="name@example.com">

    <div class="form-row">
      <div>
        <label>Пароль</label>
        <input type="password" name="password" placeholder="мінімум 6 символів">
      </div>
      <div>
        <label>Повтори пароль</label>
        <input type="password" name="password2">
      </div>
    </div>

    <div class="actions">
      <button class="btn" type="submit">Зареєструватись</button>
      <a class="btn btn--ghost" href="<?php echo BASE_URL; ?>/login.php">Вже є акаунт</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
