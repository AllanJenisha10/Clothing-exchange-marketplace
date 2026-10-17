<?php
require_once __DIR__ . '/includes/bootstrap.php';
$search = trim((string)($_GET['q'] ?? ''));
$category = trim((string)($_GET['category'] ?? ''));
$location = trim((string)($_GET['location'] ?? ''));
$sql = "SELECT l.*, u.name AS owner_name FROM listings l JOIN users u ON u.id=l.user_id WHERE l.status='available'";
$params = [];
if ($search !== '') {
    $sql .= " AND (l.title LIKE ? OR l.description LIKE ? OR l.brand LIKE ?)";
    $term = '%' . $search . '%';
    array_push($params, $term, $term, $term);
}
if ($category !== '' && in_array($category, ['Tops','Bottoms','Dresses','Outerwear','Shoes','Accessories','Other'], true)) {
    $sql .= " AND l.category = ?";
    $params[] = $category;
}
if ($location !== '') { $sql .= ' AND l.location LIKE ?'; $params[] = '%' . $location . '%'; }
$sql .= " ORDER BY l.created_at DESC LIMIT 60";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();
$pageTitle = 'ReWear — Clothing exchange';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="container hero-grid">
    <div class="hero-copy">
      <span class="eyebrow"><span class="eyebrow-dot"></span> A more thoughtful wardrobe</span>
      <h1>Good clothes deserve <em>another story.</em></h1>
      <p>Find your next favourite piece, pass on what you no longer wear, and make fashion feel a little lighter on the planet.</p>
      <div class="hero-actions">
        <a class="btn" href="#browse">Explore the marketplace <span aria-hidden="true">↗</span></a>
        <?php if (current_user()): ?><a class="btn btn-outline" href="listing_form.php">List your clothes</a><?php else: ?><a class="btn btn-outline" href="register.php">Join ReWear</a><?php endif; ?>
      </div>
      <div class="hero-proof"><span class="avatar-stack"><b>A</b><b>M</b><b>J</b></span><span>Small swaps. Meaningful impact.</span></div>
    </div>
    <div class="hero-art">
      <div class="art-circle"></div>
      <div class="art-label art-label-top">PRE-LOVED, RE-LOVED</div>
      <div class="art-card art-card-main"><div class="art-shirt">✳</div><span>Wear it again.</span></div>
      <div class="art-sticker">SWAP<br>GOOD<br>THINGS ↗</div>
      <div class="art-leaf">✦</div>
    </div>
  </div>
</section>
<section class="section container" id="browse">
  <div class="section-heading">
    <div><span class="eyebrow">THE COMMUNITY RAIL</span><h2>Pieces looking for a new home</h2><p>Discover available pieces shared by the community.</p></div>
    <?php if (current_user()): ?><a class="text-link" href="listing_form.php">+ List an item</a><?php endif; ?>
  </div>
  <form class="filter-bar" method="get" action="index.php">
    <label class="search-field"><span aria-hidden="true">⌕</span><input type="search" name="q" placeholder="Search clothes, brands..." value="<?= e($search) ?>"></label>
    <select name="category" aria-label="Filter by category">
      <option value="">All categories</option>
      <?php foreach (['Tops','Bottoms','Dresses','Outerwear','Shoes','Accessories','Other'] as $cat): ?>
        <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="search" name="location" placeholder="City / area" value="<?= e($location) ?>" aria-label="Filter by location"><button class="btn btn-dark" type="submit">Find pieces</button>
  </form>
  <?php if (!$listings): ?>
    <div class="empty-state"><div class="empty-icon">✳</div><h3>No pieces found just yet</h3><p>Try another search, or be the first to add something to the rail.</p><?php if (current_user()): ?><a class="btn" href="listing_form.php">List an item</a><?php endif; ?></div>
  <?php else: ?>
    <div class="listing-grid">
      <?php foreach ($listings as $item): ?>
        <article class="listing-card">
          <a class="listing-image" href="listing.php?id=<?= (int)$item['id'] ?>">
            <?php if ($item['image_path']): ?><img src="<?= e($item['image_path']) ?>" alt="<?= e($item['title']) ?>" loading="lazy"><?php else: ?><div class="image-placeholder"><span>✳</span><small>PRE-LOVED FIND</small></div><?php endif; ?>
            <span class="category-pill"><?= e($item['category']) ?></span>
          </a>
          <div class="listing-info"><div class="listing-meta"><span><?= e($item['condition_label']) ?></span><span>·</span><span><?= e($item['size_label']) ?></span></div><h3><a href="listing.php?id=<?= (int)$item['id'] ?>"><?= e($item['title']) ?></a></h3><p><?= e($item['brand'] ?: 'No brand specified') ?> · <?= e($item['location'] ?: 'Location not set') ?></p><p class="value-note">Estimated swap value <strong>₹<?= number_format((float)$item['estimated_value'],0) ?></strong></p><div class="card-bottom"><span class="owner">Shared by <?= e($item['owner_name']) ?></span><a class="arrow-link" href="listing.php?id=<?= (int)$item['id'] ?>" aria-label="View <?= e($item['title']) ?>">↗</a></div></div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<section class="impact-band"><div class="container impact-inner"><div><span class="eyebrow">BETTER STYLE, LESS WASTE</span><h2>Refresh your wardrobe.<br><em>Not the planet.</em></h2></div><p>Every exchange is a chance to keep quality clothing in use for longer. Start with one piece and pass the good on.</p><a class="btn btn-light" href="<?= current_user() ? 'listing_form.php' : 'register.php' ?>">Make your first swap ↗</a></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
