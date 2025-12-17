<?php
require_once __DIR__ . '/../auth.php';

$u = current_user();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="utf-8">
  <title><?php echo htmlspecialchars(APP_NAME); ?></title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/../assets/style.css">
</head>

<body>
<header class="topbar">
  <div class="container topbar__inner">
    <a class="brand" href="<?php echo BASE_URL; ?>/index.php">
      <span class="brand__name"><?php echo htmlspecialchars(APP_NAME); ?></span>
    </a>

    <nav class="nav">
      <a href="<?php echo BASE_URL; ?>/sales_list.php">Заявки на продаж</a>

      <?php if ($u): ?>
        <a class="btn btn--ghost" href="<?php echo BASE_URL; ?>/sales_add.php">+ Додати продаж</a>
        <?php if ($u['role'] === 'admin' || $u['role'] === 'realtor'): ?>
          <a class="btn btn--ghost" href="<?php echo BASE_URL; ?>/deal_create.php">Створити угоду</a>
        <?php endif; ?>

        <span class="userpill">
          <?php echo htmlspecialchars($u['name']); ?>
          <small>(<?php echo htmlspecialchars($u['role']); ?>)</small>
        </span>
        <a class="btn btn--danger" href="<?php echo BASE_URL; ?>/logout.php">Вийти</a>
      <?php else: ?>
        <a class="btn" href="<?php echo BASE_URL; ?>/login.php">Увійти</a>
        <a class="btn btn--ghost" href="<?php echo BASE_URL; ?>/register.php">Реєстрація</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main class="container">
