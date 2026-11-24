<?php
// Optional reusable customer page header. Set $pageTitle before including.
$pageTitle = $pageTitle ?? 'Customer Shopping';
?>
<header class="topbar"><div><a class="brand" href="home.php">Fresh<span>Mart</span></a><h1><?=htmlspecialchars($pageTitle)?></h1></div><nav class="actions"><a class="btn" href="products.php">Products</a><a class="btn" href="cart.php">Cart</a><a class="btn" href="my_orders.php">My Orders</a><a class="btn" href="profile.php">My Account</a><a class="btn" href="../logout.php">Sign out</a></nav></header>
