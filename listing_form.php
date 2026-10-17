<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
$user = current_user();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$item = ['title'=>'','description'=>'','brand'=>'','category'=>'Tops','size_label'=>'M','condition_label'=>'Good','image_path'=>null,'estimated_value'=>0,'location'=>''];
if ($id) {
    $stmt = db()->prepare('SELECT * FROM listings WHERE id=? AND user_id=?');
    $stmt->execute([$id, $user['id']]);
    $existing = $stmt->fetch();
    if (!$existing) { set_flash('error','Listing not found or you do not have permission to edit it.'); redirect('dashboard.php'); }
    $item = $existing;
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $item['title'] = trim((string)($_POST['title'] ?? ''));
    $item['description'] = trim((string)($_POST['description'] ?? ''));
    $item['brand'] = trim((string)($_POST['brand'] ?? ''));
    $item['category'] = (string)($_POST['category'] ?? '');
    $item['size_label'] = (string)($_POST['size_label'] ?? '');
    $item['condition_label'] = (string)($_POST['condition_label'] ?? '');
    $item['location'] = trim((string)($_POST['location'] ?? ''));
    $item['estimated_value'] = estimate_swap_value($item['category'], $item['condition_label'], $item['brand']);
    if (mb_strlen($item['location']) > 120) $errors[] = 'Location must be 120 characters or fewer.';
    if (mb_strlen($item['title']) < 3 || mb_strlen($item['title']) > 120) $errors[] = 'Title must be between 3 and 120 characters.';
    if (mb_strlen($item['description']) < 10 || mb_strlen($item['description']) > 2000) $errors[] = 'Description must be between 10 and 2,000 characters.';
    if (!in_array($item['category'], ['Tops','Bottoms','Dresses','Outerwear','Shoes','Accessories','Other'], true)) $errors[] = 'Choose a valid category.';
    if (!in_array($item['size_label'], ['XS','S','M','L','XL','XXL','One size'], true)) $errors[] = 'Choose a valid size.';
    if (!in_array($item['condition_label'], ['Like new','Very good','Good','Fair'], true)) $errors[] = 'Choose a valid condition.';
    if (!$errors) {
        try {
            $image = upload_image($_FILES['image'] ?? []);
            $imagePath = $image ?: $item['image_path'];
            if ($id) {
                $stmt = db()->prepare('UPDATE listings SET title=?,description=?,brand=?,category=?,size_label=?,condition_label=?,image_path=?,estimated_value=?,location=? WHERE id=? AND user_id=?');
                $stmt->execute([$item['title'],$item['description'],$item['brand'],$item['category'],$item['size_label'],$item['condition_label'],$imagePath,$item['estimated_value'],$item['location'],$id,$user['id']]);
                set_flash('success','Your listing has been updated.');
            } else {
                $stmt = db()->prepare("INSERT INTO listings (user_id,title,description,brand,category,size_label,condition_label,image_path,estimated_value,location,status) VALUES (?,?,?,?,?,?,?,?,?,?, 'available')");
                $stmt->execute([$user['id'],$item['title'],$item['description'],$item['brand'],$item['category'],$item['size_label'],$item['condition_label'],$imagePath,$item['estimated_value'],$item['location']]);
                set_flash('success','Your clothing item is now listed.');
            }
            redirect('dashboard.php');
        } catch (Throwable $ex) {
            $errors[] = $ex instanceof RuntimeException ? $ex->getMessage() : 'Could not save the listing. Check your database configuration.';
        }
    }
}
$pageTitle = ($id ? 'Edit listing' : 'List an item') . ' — ReWear';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero container"><span class="eyebrow">GIVE IT ANOTHER STORY</span><h1><?= $id ? 'Edit your piece' : 'List a piece' ?>.</h1><p>Share clear details so someone can imagine giving your item a new home.</p></section>
<section class="container form-page"><div class="form-panel">
<?php if ($errors): ?><div class="form-errors"><?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="form-stack"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<label>Item title<input name="title" required maxlength="120" placeholder="e.g. Linen summer shirt" value="<?= e($item['title']) ?>"></label>
<div class="form-row"><label>Category<select name="category" required><?php foreach (['Tops','Bottoms','Dresses','Outerwear','Shoes','Accessories','Other'] as $v): ?><option <?= $item['category']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></label><label>Size<select name="size_label" required><?php foreach (['XS','S','M','L','XL','XXL','One size'] as $v): ?><option <?= $item['size_label']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></label></div>
<div class="form-row"><label>Condition<select name="condition_label" required><?php foreach (['Like new','Very good','Good','Fair'] as $v): ?><option <?= $item['condition_label']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></label><label>Brand (optional)<input name="brand" maxlength="100" value="<?= e($item['brand']) ?>" placeholder="Brand name"></label></div>
<label>City / area<input name="location" maxlength="120" value="<?= e($item['location'] ?? '') ?>" placeholder="e.g. Puducherry"><small>Only share a city or general area, not your home address.</small></label><label>Description<textarea name="description" rows="5" minlength="10" maxlength="2000" required placeholder="Describe colour, fit, fabric, and any wear..."><?= e($item['description']) ?></textarea></label>
<div class="value-note">Estimated swap value: <strong>₹<?= number_format((float)($item['estimated_value'] ?: estimate_swap_value($item['category'],$item['condition_label'],$item['brand'])), 0) ?></strong> · Guide only; agree on fairness together.</div><label>Item photo (JPG, PNG, WEBP · max 4 MB)<input type="file" name="image" accept="image/jpeg,image/png,image/webp"><?php if ($item['image_path']): ?><small>Current photo: <?= e(basename($item['image_path'])) ?></small><?php endif; ?></label>
<div class="form-actions"><button class="btn" type="submit"><?= $id ? 'Save changes' : 'Publish listing' ?> ↗</button><a class="btn btn-outline" href="dashboard.php">Cancel</a></div></form></div><aside class="form-tip"><span class="tip-icon">✳</span><h3>A little detail goes a long way.</h3><p>Use natural light for photos, describe the fit honestly, and mention any small flaws. Good swaps start with clear expectations.</p></aside></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
