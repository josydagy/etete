<?php
session_start();
require_once __DIR__ . '/../db.php';
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header('Location: ../login.php'); exit;
}
$productId = (int)($_GET['id'] ?? 0);
if ($productId < 1) { header('Location: products.php'); exit; }
$stmt = $conn->prepare('SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.id=? LIMIT 1');
$stmt->bind_param('i', $productId); $stmt->execute();
$product = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$product) { http_response_code(404); $notFound = true; } else { $notFound = false; }
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= $notFound ? 'Product not found' : htmlspecialchars($product['product_name']) ?> | FreshMart</title><link rel="stylesheet" href="../assets/css/modern.css"><style>.detail{max-width:980px;margin:35px auto;padding:0 18px}.detail-card{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:30px;background:#fff;border:1px solid #e3ebe6;border-radius:18px;padding:24px}.detail-img{width:100%;height:360px;object-fit:contain;background:#f5f8f6;border-radius:14px}.price{font-size:27px;font-weight:800;color:#13834b}.stock{color:#718078}@media(max-width:700px){.detail-card{grid-template-columns:1fr}.detail-img{height:260px}}</style></head><body><main class="detail"><header class="topbar"><div><a class="brand" href="home.php">Fresh<span>Mart</span></a><h1>Product details</h1></div><div class="actions"><a class="btn" href="products.php">All products</a><a class="btn" href="cart.php">My cart</a></div></header>
<?php if($notFound): ?><section class="panel"><h2>Product not found</h2><p class="muted">This product may have been removed.</p><a class="btn primary" href="products.php">Browse products</a></section><?php else: ?>
<section class="detail-card"><div><?php if(!empty($product['image'])): ?><img class="detail-img" src="../assets/images/<?=htmlspecialchars(basename($product['image']))?>" alt="<?=htmlspecialchars($product['product_name'])?>"><?php else: ?><div class="detail-img" style="display:grid;place-items:center;font-size:72px">🛒</div><?php endif; ?></div><div><p class="muted"><?=htmlspecialchars($product['category_name'] ?? 'Uncategorized')?></p><h2><?=htmlspecialchars($product['product_name'])?></h2><p class="price"><?=number_format((float)$product['selling_price'],2)?> ETB</p><p class="stock">Available quantity: <?= (int)$product['quantity'] ?></p><p><?=nl2br(htmlspecialchars($product['description'] ?? 'No description available.'))?></p><?php if((int)$product['quantity']>0): ?><form action="../actions/add_to_cart.php" method="post"><input type="hidden" name="product_id" value="<?=(int)$product['id']?>"><button class="btn primary" type="submit">Add to cart</button></form><?php else: ?><p class="badge warn">Out of stock</p><?php endif; ?><p style="margin-top:22px"><a href="products.php">← Back to products</a></p></div></section><?php endif; ?></main></body></html>
