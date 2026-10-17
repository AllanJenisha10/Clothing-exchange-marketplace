<?php
require_once __DIR__ . '/includes/bootstrap.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$stmt = db()->prepare('SELECT l.*,u.name AS owner_name FROM listings l JOIN users u ON u.id=l.user_id WHERE l.id=?');
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) { http_response_code(404); $pageTitle='Listing not found — ReWear'; require __DIR__.'/includes/header.php'; echo '<section class="container empty-state"><h1>Listing not found</h1><a class="btn" href="index.php">Back to marketplace</a></section>'; require __DIR__.'/includes/footer.php'; exit; }
$user = current_user();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    verify_csrf();
    if ((int)$user['id'] === (int)$item['user_id']) $error = 'You cannot request a swap for your own item.';
    elseif ($item['status'] !== 'available') $error = 'This item is no longer available.';
    else {
        $message = trim((string)($_POST['message'] ?? ''));
        $offeredId = filter_input(INPUT_POST, 'offered_listing_id', FILTER_VALIDATE_INT) ?: 0;
        if (mb_strlen($message) > 500) $error = 'Message must be 500 characters or fewer.';
        elseif (!$offeredId) $error = 'Choose one of your available items to offer for this swap.';
        else {
            $offerCheck = db()->prepare("SELECT id FROM listings WHERE id=? AND user_id=? AND status='available'");
            $offerCheck->execute([$offeredId,$user['id']]);
            if (!$offerCheck->fetch()) $error = 'The offered item is not available in your closet.';
        }
        if (!$error) {
            try {
                $stmt = db()->prepare("INSERT INTO swap_requests (listing_id, requester_id, owner_id, offered_listing_id, message, status) VALUES (?,?,?,?,?, 'pending')");
                $stmt->execute([$id,$user['id'],$item['user_id'],$offeredId,$message]);
                set_flash('success','Swap request sent to the owner.');
                redirect('dashboard.php');
            } catch (PDOException $ex) {
                $error = 'You have already requested a swap for this item, or the request could not be saved.';
            }
        }
    }
}
$pageTitle = $item['title'] . ' — ReWear';
require __DIR__ . '/includes/header.php';
?>
<section class="container detail-layout">
  <div class="detail-image"><?php if ($item['image_path']): ?><img src="<?= e($item['image_path']) ?>" alt="<?= e($item['title']) ?>"><?php else: ?><div class="image-placeholder detail-placeholder"><span>✳</span><small>PRE-LOVED FIND</small></div><?php endif; ?></div>
  <div class="detail-copy"><a class="back-link" href="index.php">← Back to marketplace</a><span class="category-pill static-pill"><?= e($item['category']) ?></span><h1><?= e($item['title']) ?></h1><p class="detail-brand"><?= e($item['brand'] ?: 'Independent find') ?></p><p class="value-note">Estimated swap value: <strong>₹<?= number_format((float)($item['estimated_value'] ?? 0),0) ?></strong> · <?= e($item['location'] ?: 'Location not specified') ?></p><div class="detail-tags"><span><?= e($item['size_label']) ?></span><span><?= e($item['condition_label']) ?></span><span><?= e(ucfirst($item['status'])) ?></span></div><p class="detail-description"><?= nl2br(e($item['description'])) ?></p><div class="owner-box"><div class="owner-avatar"><?= e(strtoupper(substr($item['owner_name'],0,1))) ?></div><div><strong><?= e($item['owner_name']) ?></strong><small>Community member</small></div></div>
  <?php if ($error): ?><div class="form-errors"><p><?= e($error) ?></p></div><?php endif; ?>
  <?php if (!$user): ?><a class="btn btn-full" href="login.php">Log in to request a swap ↗</a>
  <?php elseif ((int)$user['id'] === (int)$item['user_id']): ?><a class="btn btn-full" href="listing_form.php?id=<?= (int)$item['id'] ?>">Edit your listing</a>
  <?php elseif ($item['status'] === 'available'): ?><form method="post" class="form-stack swap-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Choose an item to offer<select name="offered_listing_id" required><option value="">Select from your available listings</option><?php $offerStmt=db()->prepare("SELECT id,title,estimated_value FROM listings WHERE user_id=? AND status='available' ORDER BY created_at DESC"); $offerStmt->execute([$user['id']]); foreach($offerStmt->fetchAll() as $offer): ?><option value="<?= (int)$offer['id'] ?>"><?= e($offer['title']) ?> — ₹<?= number_format((float)$offer['estimated_value'],0) ?></option><?php endforeach; ?></select></label><label>Message to the owner (optional)<textarea name="message" rows="3" maxlength="500" placeholder="Tell them what you'd like to swap..."></textarea></label><?php if (empty($offerStmt) || !$offerStmt->rowCount()): ?><p class="value-note">You need an available listing of your own before you can request a swap.</p><?php endif; ?><button class="btn btn-full" type="submit">Request a swap ↗</button></form>
  <?php else: ?><button class="btn btn-full" disabled>Not currently available</button><?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
