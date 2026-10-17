<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (current_user()) redirect('dashboard.php');
$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $stmt = db()->prepare('SELECT id,name,email,password_hash,role,location FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role'] ?? 'user', 'location' => $user['location'] ?? ''];
        set_flash('success', 'You are signed in.');
        redirect('dashboard.php');
    }
    $error = 'Email or password is incorrect.';
}
$pageTitle = 'Log in — ReWear';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-section container"><div class="auth-aside"><span class="eyebrow">GOOD CLOTHES, GOOD STORIES</span><h1>Welcome back to the rail.</h1><p>Find a piece you love. Pass on a piece someone else will.</p><div class="auth-decoration">R<span>✳</span></div></div><div class="auth-card"><span class="eyebrow">MEMBER SIGN IN</span><h2>Good to see you</h2><p class="muted">Sign in to manage your listings and swaps.</p>
<?php if ($error): ?><div class="form-errors"><p><?= e($error) ?></p></div><?php endif; ?>
<form method="post" class="form-stack"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Email address<input type="email" name="email" required value="<?= e($email) ?>" autocomplete="email"></label><label>Password<input type="password" name="password" required autocomplete="current-password"></label><button class="btn btn-full" type="submit">Log in ↗</button></form><p class="auth-switch">New to ReWear? <a href="register.php">Create an account</a></p></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
