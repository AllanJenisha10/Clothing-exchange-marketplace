<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (current_user()) redirect('dashboard.php');
$errors = [];
$name = '';
$email = '';
$location = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string)($_POST['name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $location = trim((string)($_POST['location'] ?? ''));
    if (mb_strlen($location) > 120) $errors[] = 'Location must be 120 characters or fewer.';
    if (mb_strlen($name) < 2 || mb_strlen($name) > 80) $errors[] = 'Name must be between 2 and 80 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 8) $errors[] = 'Password must contain at least 8 characters.';
    if (!$errors) {
        $check = db()->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) $errors[] = 'An account with this email already exists.';
        else {
            $stmt = db()->prepare('INSERT INTO users (name,email,password_hash,location) VALUES (?,?,?,?)');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $location]);
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => (int)db()->lastInsertId(), 'name' => $name, 'email' => $email, 'role' => 'user', 'location' => $location];
            set_flash('success', 'Welcome to ReWear! Your account is ready.');
            redirect('dashboard.php');
        }
    }
}
$pageTitle = 'Create account — ReWear';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-section container"><div class="auth-aside"><span class="eyebrow">JOIN THE MOVEMENT</span><h1>Your next favourite find is out there.</h1><p>Build a more thoughtful wardrobe, one good exchange at a time.</p><div class="auth-decoration">R<span>✳</span></div></div><div class="auth-card"><span class="eyebrow">CREATE YOUR ACCOUNT</span><h2>Welcome to ReWear</h2><p class="muted">A few details and you're in.</p>
<?php if ($errors): ?><div class="form-errors"><?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div><?php endif; ?>
<form method="post" class="form-stack"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Full name<input name="name" required minlength="2" maxlength="80" value="<?= e($name) ?>" autocomplete="name"></label><label>Email address<input type="email" name="email" required value="<?= e($email) ?>" autocomplete="email"></label><label>City / area<input name="location" maxlength="120" value="<?= e($location) ?>" placeholder="e.g. Puducherry" autocomplete="address-level2"><small>Used to help you discover nearby swaps.</small></label><label>Password<input type="password" name="password" required minlength="8" autocomplete="new-password"><small>Use at least 8 characters.</small></label><button class="btn btn-full" type="submit">Create account ↗</button></form><p class="auth-switch">Already a member? <a href="login.php">Log in</a></p></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
