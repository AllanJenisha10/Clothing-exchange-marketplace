<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
$user = current_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $requestId = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT) ?: 0;
    if (in_array($action, ['accept','reject'], true)) {
        $pdo = db(); $newStatus = $action === 'accept' ? 'accepted' : 'rejected';
        try {
            $pdo->beginTransaction();
            $get = $pdo->prepare("SELECT listing_id,offered_listing_id,requester_id FROM swap_requests WHERE id=? AND owner_id=? AND status='pending' FOR UPDATE");
            $get->execute([$requestId,$user['id']]); $request = $get->fetch();
            if (!$request) throw new RuntimeException('This request is no longer pending.');
            if ($action === 'accept') {
                $reserve = $pdo->prepare("UPDATE listings SET status='reserved' WHERE id=? AND status='available'"); $reserve->execute([(int)$request['listing_id']]);
                if (!$reserve->rowCount()) throw new RuntimeException('The requested item is no longer available.');
                if (!empty($request['offered_listing_id'])) { $off = $pdo->prepare("UPDATE listings SET status='reserved' WHERE id=? AND status='available' AND user_id=?"); $off->execute([(int)$request['offered_listing_id'], (int)$request['requester_id']]); if (!$off->rowCount()) throw new RuntimeException('The offered item is no longer available.'); }
            }
            $upd=$pdo->prepare('UPDATE swap_requests SET status=? WHERE id=?'); $upd->execute([$newStatus,$requestId]); $pdo->commit();
            set_flash('success','Swap request '.$newStatus.'.'.($action==='accept'?' Items are reserved while you arrange the exchange.':''));
        } catch (Throwable $ex) { if($pdo->inTransaction()) $pdo->rollBack(); set_flash('error',$ex instanceof RuntimeException?$ex->getMessage():'Could not update the request.'); }
    } elseif ($action === 'complete') {
        $pdo=db(); $pdo->beginTransaction();
        $check=$pdo->prepare("SELECT id,listing_id,offered_listing_id,requester_id,owner_id FROM swap_requests WHERE id=? AND status='accepted' AND (requester_id=? OR owner_id=?) FOR UPDATE"); $check->execute([$requestId,$user['id'],$user['id']]); $r=$check->fetch();
        if($r){$pdo->prepare("UPDATE swap_requests SET status='completed' WHERE id=?")->execute([$requestId]);$pdo->prepare("UPDATE listings SET status='swapped' WHERE id IN (?,?)")->execute([(int)$r['listing_id'],(int)$r['offered_listing_id']]);$pdo->commit();set_flash('success','Swap marked completed.');}else{$pdo->rollBack();set_flash('error','Only participants in an accepted swap can complete it.');}
    } elseif ($action === 'delete_listing') {
        $listingId = filter_input(INPUT_POST, 'listing_id', FILTER_VALIDATE_INT) ?: 0;
        $stmt = db()->prepare("DELETE FROM listings WHERE id=? AND user_id=? AND status='available'");
        $stmt->execute([$listingId,$user['id']]);
        set_flash($stmt->rowCount() ? 'success' : 'error', $stmt->rowCount() ? 'Listing deleted.' : 'Listing could not be deleted; it may no longer be available.');
    }
    redirect('dashboard.php');
}
$stmt = db()->prepare('SELECT * FROM listings WHERE user_id=? ORDER BY created_at DESC');
$stmt->execute([$user['id']]); $myListings = $stmt->fetchAll();
$stmt = db()->prepare('SELECT sr.*,l.title,l.image_path,l.category,ol.title AS offered_title,ol.estimated_value AS offered_value,u.name AS requester_name FROM swap_requests sr JOIN listings l ON l.id=sr.listing_id LEFT JOIN listings ol ON ol.id=sr.offered_listing_id JOIN users u ON u.id=sr.requester_id WHERE sr.owner_id=? ORDER BY sr.created_at DESC');
$stmt->execute([$user['id']]); $incoming = $stmt->fetchAll();
$stmt = db()->prepare('SELECT sr.*,l.title,l.image_path,l.category,u.name AS owner_name FROM swap_requests sr JOIN listings l ON l.id=sr.listing_id JOIN users u ON u.id=sr.owner_id WHERE sr.requester_id=? ORDER BY sr.created_at DESC');
$stmt->execute([$user['id']]); $outgoing = $stmt->fetchAll();
$pageTitle = 'My dashboard — ReWear';
require __DIR__ . '/includes/header.php';
?>
<section class="dashboard-hero"><div class="container dashboard-welcome"><div><span class="eyebrow">YOUR REWEAR SPACE</span><h1>Hello, <?= e(explode(' ', $user['name'])[0]) ?> <span>✳</span></h1><p>Keep track of your pieces and the swaps taking shape.</p></div><a class="btn" href="listing_form.php">+ List a new item</a></div></section>
<section class="container dashboard-content">
  <div class="stats-grid"><div class="stat-card"><span>MY LISTINGS</span><strong><?= count($myListings) ?></strong><small>pieces shared</small></div><div class="stat-card"><span>INCOMING REQUESTS</span><strong><?= count(array_filter($incoming,fn($r)=>$r['status']==='pending')) ?></strong><small>waiting for you</small></div><div class="stat-card"><span>MY SWAP REQUESTS</span><strong><?= count($outgoing) ?></strong><small>requests sent</small></div></div>
  <section class="dashboard-section"><div class="section-heading compact"><div><span class="eyebrow">YOUR CLOSET, REIMAGINED</span><h2>My listings</h2></div><a class="text-link" href="listing_form.php">+ Add piece</a></div>
  <?php if (!$myListings): ?><div class="empty-inline"><p>You haven't listed anything yet.</p><a href="listing_form.php">Share your first piece ↗</a></div><?php else: ?><div class="table-list"><?php foreach ($myListings as $item): ?><div class="table-row"><div class="mini-product"><?php if ($item['image_path']): ?><img src="<?= e($item['image_path']) ?>" alt=""><?php else: ?><span>✳</span><?php endif; ?></div><div class="table-main"><strong><?= e($item['title']) ?></strong><small><?= e($item['category']) ?> · <?= e($item['size_label']) ?></small></div><span class="status status-<?= e($item['status']) ?>"><?= e(ucfirst($item['status'])) ?></span><div class="row-actions"><a href="listing.php?id=<?= (int)$item['id'] ?>">View</a><?php if ($item['status']==='available'): ?><a href="listing_form.php?id=<?= (int)$item['id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this listing?')"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_listing"><input type="hidden" name="listing_id" value="<?= (int)$item['id'] ?>"><button type="submit">Delete</button></form><?php endif; ?></div></div><?php endforeach; ?></div><?php endif; ?>
  </section>
  <section class="dashboard-section"><div class="section-heading compact"><div><span class="eyebrow">MAKE A GOOD MATCH</span><h2>Incoming swap requests</h2></div></div>
  <?php if (!$incoming): ?><div class="empty-inline"><p>No one has requested a swap yet. Your next good match may be around the corner.</p></div><?php else: ?><div class="request-grid"><?php foreach ($incoming as $r): ?><article class="request-card"><div class="request-card-top"><div class="mini-product"><?php if ($r['image_path']): ?><img src="<?= e($r['image_path']) ?>" alt=""><?php else: ?><span>✳</span><?php endif; ?></div><div><strong><?= e($r['title']) ?></strong><small>From <?= e($r['requester_name']) ?></small></div><span class="status status-<?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></div><?php if ($r['offered_title']): ?><p class="request-message">Offering: <strong><?= e($r['offered_title']) ?></strong> · ₹<?= number_format((float)$r['offered_value'],0) ?></p><?php endif; ?><?php if ($r['message']): ?><p class="request-message">“<?= e($r['message']) ?>”</p><?php endif; ?><a class="text-link" href="chat.php?request_id=<?= (int)$r['id'] ?>">Open conversation ↗</a><?php if ($r['status']==='accepted'): ?><form method="post" data-confirm="Confirm that this swap has been completed?"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="complete"><button class="btn btn-small" type="submit">Mark completed</button></form><?php endif; ?><?php if ($r['status']==='pending'): ?><div class="request-actions"><?php foreach (['accept'=>'Accept','reject'=>'Decline'] as $act=>$label): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="<?= e($act) ?>"><button class="btn <?= $act==='reject'?'btn-outline':'' ?> btn-small" type="submit"><?= e($label) ?></button></form><?php endforeach; ?></div><?php endif; ?></article><?php endforeach; ?></div><?php endif; ?>
  </section>
  <section class="dashboard-section"><div class="section-heading compact"><div><span class="eyebrow">YOUR OUTGOING REQUESTS</span><h2>Swaps I've requested</h2></div></div>
  <?php if (!$outgoing): ?><div class="empty-inline"><p>You haven't requested any swaps yet.</p><a href="index.php">Explore the marketplace ↗</a></div><?php else: ?><div class="table-list"><?php foreach ($outgoing as $r): ?><div class="table-row"><div class="mini-product"><?php if ($r['image_path']): ?><img src="<?= e($r['image_path']) ?>" alt=""><?php else: ?><span>✳</span><?php endif; ?></div><div class="table-main"><strong><?= e($r['title']) ?></strong><small>Owner: <?= e($r['owner_name']) ?></small></div><span class="status status-<?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span><a href="listing.php?id=<?= (int)$r['listing_id'] ?>">View item ↗</a></div><?php endforeach; ?></div><?php endif; ?>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
