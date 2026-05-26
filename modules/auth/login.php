<?php
if (session_status() === PHP_SESSION_NONE) session_start();
define('BASE_URL', '/vtasis/');
require_once '../../config/db.php';
require_once '../../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    redirect(BASE_URL . 'modules/dashboard/index.php');
}

$error = '';

if (isPost()) {
    $username = post('username');
    $password = post('password');

    if (!$username || !$password) {
        $error = 'Username and password are required.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, password, role, full_name FROM users WHERE username = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            redirect(BASE_URL . 'modules/dashboard/index.php');
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — VTA SIS</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body class="login-page">
<div class="login-wrapper">
  <div class="login-card">
    <div class="login-logo">
      <div class="logo-icon"><img src="<?= BASE_URL ?>assets/logo.png" alt="VTA Logo" style="width:48px;height:48px;object-fit:contain;"></div>
      <h1>VTA Student Information System</h1>
      <p>Vocational Training Authority — Ambegoda</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error">❌ <?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" value="<?= h(post('username')) ?>" placeholder="Enter your username" required autofocus>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Enter your password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block" style="margin-top:.5rem;padding:.75rem;">
        Sign In
      </button>
    </form>

    <p style="text-align:center;margin-top:1.5rem;font-size:.8rem;color:var(--text-muted);">
      © <?= date('Y') ?> Vocational Training Authority
    </p>
  </div>
</div>
</body>
</html>
