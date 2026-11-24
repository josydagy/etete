<?php
session_start();
require_once __DIR__ . "/../db.php";

// Customer protection
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "customer") {
    header("Location: ../login.php");
    exit();
}

$userId = (int) $_SESSION["user_id"];
$customerName = $_SESSION["full_name"] ?? "Customer";

// Count products
$productCount = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM products");
if ($result) {
    $productCount = (int) mysqli_fetch_assoc($result)["total"];
}

// Count categories
$categoryCount = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM categories");
if ($result) {
    $categoryCount = (int) mysqli_fetch_assoc($result)["total"];
}

// Count cart items
$cartCount = 0;
$stmt = mysqli_prepare(
    $conn,
    "SELECT COALESCE(SUM(ci.quantity), 0) AS total
     FROM cart c
     LEFT JOIN cart_items ci ON ci.cart_id = c.id
     WHERE c.user_id = ?"
);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $cartResult = mysqli_stmt_get_result($stmt);
    $cartCount = (int) mysqli_fetch_assoc($cartResult)["total"];
    mysqli_stmt_close($stmt);
}

// Latest products
$products = mysqli_query(
    $conn,
    "SELECT p.id, p.product_name, p.selling_price, p.image,
            c.category_name
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     ORDER BY p.id DESC
     LIMIT 8"
);

// Categories
$categories = mysqli_query(
    $conn,
    "SELECT id, category_name, image
     FROM categories
     ORDER BY category_name ASC
     LIMIT 8"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EteteSuperMarket | Customer Home</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root {
    --green: #16834a;
    --dark: #103d29;
    --text: #24352c;
    --muted: #77867d;
    --background: #f6f9f5;
    --white: #ffffff;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "DM Sans", Arial, sans-serif;
}

body {
    background: var(--background);
    color: var(--text);
}

a {
    text-decoration: none;
    color: inherit;
}

/* NAVBAR */
.navbar {
    background: white;
    min-height: 75px;
    padding: 15px 6%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    box-shadow: 0 3px 18px rgba(0,0,0,.05);
    position: sticky;
    top: 0;
    z-index: 100;
}

.logo {
    color: var(--green);
    font-size: 27px;
    font-weight: 800;
}

.logo span {
    color: var(--dark);
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 23px;
    font-size: 14px;
    font-weight: 600;
}

.nav-links a {
    transition: .2s;
}

.nav-links a:hover {
    color: var(--green);
}

.cart-link {
    background: #eaf5ed;
    color: var(--green);
    padding: 10px 15px;
    border-radius: 9px;
}

.cart-number {
    background: var(--green);
    color: white;
    border-radius: 20px;
    padding: 2px 7px;
    margin-left: 4px;
    font-size: 11px;
}

/* HERO */
.hero {
    width: 90%;
    min-height: 390px;
    margin: 30px auto;
    padding: 55px 6%;
    border-radius: 25px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 25px;
    overflow: hidden;
    color: white;
    background:
        linear-gradient(90deg, rgba(8,48,28,.94), rgba(12,87,48,.72)),
        url("https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1800&q=85");
    background-size: cover;
    background-position: center;
    box-shadow: 0 12px 30px rgba(16,61,41,.15);
}

.hero-content {
    max-width: 620px;
}

.hero-label {
    display: inline-block;
    background: rgba(255,255,255,.17);
    color: #e2ffe9;
    padding: 9px 16px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1px;
    margin-bottom: 22px;
}

.hero h1 {
    font-size: clamp(32px, 4vw, 52px);
    line-height: 1.15;
    margin-bottom: 17px;
}

.hero h1 span {
    color: #ffd166;
}

.hero p {
    color: #e1f1e6;
    line-height: 1.8;
    font-size: 16px;
    max-width: 550px;
    margin-bottom: 27px;
}

.shop-btn {
    display: inline-block;
    background: #ffd166;
    color: #173d2a;
    padding: 14px 24px;
    border-radius: 9px;
    font-weight: 800;
    transition: .25s;
}

.shop-btn:hover {
    background: white;
    transform: translateY(-3px);
}

.hero-art {
    font-size: 150px;
    filter: drop-shadow(0 10px 10px rgba(0,0,0,.15));
}

/* MAIN CONTAINER */
.container {
    width: 88%;
    max-width: 1400px;
    margin: 45px auto;
}

/* STATISTICS */
.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 45px;
}

.stat-card {
    background: white;
    padding: 24px;
    border-radius: 17px;
    border: 1px solid #edf1ed;
    display: flex;
    align-items: center;
    gap: 17px;
    box-shadow: 0 5px 18px rgba(0,0,0,.025);
    transition: .2s;
}

.stat-card:hover {
    transform: translateY(-4px);
}

.stat-icon {
    width: 55px;
    height: 55px;
    background: #e9f6ec;
    border-radius: 15px;
    display: grid;
    place-items: center;
    font-size: 27px;
}

.stat-card p {
    color: var(--muted);
    font-size: 13px;
}

.stat-card h3 {
    color: var(--dark);
    font-size: 25px;
    margin-top: 4px;
}

/* QUICK LINKS */
.quick-links {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 50px;
}

.quick-card {
    background: white;
    border: 1px solid #edf1ed;
    border-radius: 15px;
    padding: 21px;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: .25s;
}

.quick-card:hover {
    transform: translateY(-5px);
    border-color: var(--green);
    box-shadow: 0 9px 22px rgba(0,0,0,.05);
}

.quick-card .icon {
    width: 48px;
    height: 48px;
    background: #eaf5ed;
    border-radius: 13px;
    display: grid;
    place-items: center;
    font-size: 25px;
}

.quick-card h3 {
    font-size: 15px;
    color: var(--dark);
}

.quick-card p {
    font-size: 12px;
    color: var(--muted);
    margin-top: 5px;
}

/* SECTION HEADINGS */
.section {
    margin-bottom: 50px;
}

.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 23px;
}

.heading-label {
    color: var(--green);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin-bottom: 6px;
}

.section-heading h2 {
    font-size: 25px;
    color: var(--dark);
}

.section-heading > a {
    color: var(--green);
    font-size: 13px;
    font-weight: 700;
}

/* CATEGORY CARDS */
.category-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.category-card {
    background: white;
    border: 1px solid #edf1ed;
    border-radius: 17px;
    padding: 18px;
    text-align: center;
    transition: .25s;
}

.category-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 25px rgba(0,0,0,.07);
    border-color: #cce8d2;
}

.category-card img {
    width: 100%;
    height: 120px;
    object-fit: contain;
    margin-bottom: 12px;
}

.category-placeholder {
    height: 120px;
    display: grid;
    place-items: center;
    font-size: 50px;
}

.category-card h3 {
    font-size: 14px;
    color: var(--dark);
}

/* PRODUCTS */
.product-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 21px;
}

.product-card {
    background: white;
    border: 1px solid #edf1ed;
    border-radius: 17px;
    overflow: hidden;
    transition: .25s;
}

.product-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 25px rgba(0,0,0,.07);
}

.product-image {
    height: 195px;
    background: #f5f8f4;
    display: grid;
    place-items: center;
    padding: 18px;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.product-placeholder {
    font-size: 70px;
}

.product-info {
    padding: 18px;
}

.product-category {
    color: var(--green);
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
}

.product-info h3 {
    font-size: 15px;
    color: var(--dark);
    margin: 9px 0 14px;
    min-height: 22px;
}

.product-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
}

.price {
    color: var(--green);
    font-size: 16px;
    font-weight: 800;
}

.view-btn {
    background: var(--green);
    color: white;
    padding: 9px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    transition: .2s;
}

.view-btn:hover {
    background: var(--dark);
}

.empty {
    grid-column: 1 / -1;
    background: white;
    border-radius: 15px;
    padding: 35px;
    text-align: center;
    color: var(--muted);
}

/* FOOTER */
.footer {
    background: #103d29;
    color: white;
    text-align: center;
    padding: 32px 15px;
    margin-top: 65px;
}

.footer strong {
    color: #ffd166;
}

.footer p {
    color: #d4e9da;
    font-size: 13px;
}

/* RESPONSIVE */
@media(max-width: 1000px) {
    .product-grid,
    .category-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .hero-art {
        font-size: 110px;
    }
}

@media(max-width: 700px) {
    .navbar {
        flex-direction: column;
        padding: 16px 5%;
    }

    .nav-links {
        justify-content: center;
        flex-wrap: wrap;
        gap: 13px;
        font-size: 12px;
    }

    .hero {
        width: 92%;
        padding: 35px 25px;
        min-height: 330px;
    }

    .hero-art {
        display: none;
    }

    .container {
        width: 92%;
    }

    .stats,
    .quick-links {
        grid-template-columns: 1fr;
    }

    .section-heading h2 {
        font-size: 20px;
    }
}

@media(max-width: 450px) {
    .product-grid,
    .category-grid {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .product-image {
        height: 135px;
    }

    .product-info {
        padding: 12px;
    }

    .product-info h3 {
        font-size: 13px;
    }

    .price {
        font-size: 13px;
    }

    .view-btn {
        padding: 8px;
        font-size: 10px;
    }

    .category-card {
        padding: 12px;
    }

    .category-card img,
    .category-placeholder {
        height: 90px;
    }
}
</style>
</head>

<body>

<!-- NAVIGATION -->
<nav class="navbar">
    <a href="home.php" class="logo">EteteSuper<span>Market.</span></a>

    <div class="nav-links">
        <a href="home.php">Home</a>
        <a href="products.php">Products</a>
        <a href="my_orders.php">My Orders</a>
        <a href="profile.php">My Profile</a>
        <a href="cart.php" class="cart-link">
            🛒 Cart <span class="cart-number"><?php echo $cartCount; ?></span>
        </a>
        <a href="../logout.php">Logout</a>
    </div>
</nav>

<!-- WELCOME HERO -->
<section class="hero">
    <div class="hero-content">
        <span class="hero-label">WELCOME TO ETETESUPERMART</span>

        <h1>
            Hello, <?php echo htmlspecialchars($customerName); ?>! 👋
            <br>Shop <span>Fresh.</span> Live Better.
        </h1>

        <p>
            Discover your everyday essentials in one place.
            Enjoy quality products, great prices and a simple
            online shopping experience.
        </p>

        <a href="products.php" class="shop-btn">Start Shopping →</a>
    </div>

    <div class="hero-art">🛒</div>
</section>

<main class="container">

    <!-- STATISTICS -->
    <section class="stats">

        <div class="stat-card">
            <div class="stat-icon">🛍️</div>
            <div>
                <p>Available Products</p>
                <h3><?php echo $productCount; ?></h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div>
                <p>Product Categories</p>
                <h3><?php echo $categoryCount; ?></h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🛒</div>
            <div>
                <p>Items in Your Cart</p>
                <h3><?php echo $cartCount; ?></h3>
            </div>
        </div>

    </section>

    <!-- QUICK LINKS -->
    <section class="quick-links">

        <a href="products.php" class="quick-card">
            <span class="icon">🛍️</span>
            <div>
                <h3>Browse Products</h3>
                <p>Explore supermarket items</p>
            </div>
        </a>

        <a href="cart.php" class="quick-card">
            <span class="icon">🛒</span>
            <div>
                <h3>My Shopping Cart</h3>
                <p>Review your selected items</p>
            </div>
        </a>

        <a href="my_orders.php" class="quick-card">
            <span class="icon">📋</span>
            <div>
                <h3>Track My Orders</h3>
                <p>Check your order history</p>
            </div>
        </a>

    </section>

    <!-- CATEGORIES -->
    <section class="section">

        <div class="section-heading">
            <div>
                <div class="heading-label">Explore our store</div>
                <h2>Shop by Category</h2>
            </div>
            <a href="products.php">View All →</a>
        </div>

        <div class="category-grid">

            <?php if ($categories && mysqli_num_rows($categories) > 0): ?>

                <?php while ($category = mysqli_fetch_assoc($categories)): ?>

                    <a href="products.php?category=<?php echo (int)$category["id"]; ?>"
                       class="category-card">

                        <?php if (!empty($category["image"])): ?>
                            <img
                                src="../assets/images/<?php echo htmlspecialchars(basename($category["image"])); ?>"
                                alt="<?php echo htmlspecialchars($category["category_name"]); ?>"
                            >
                        <?php else: ?>
                            <div class="category-placeholder">📦</div>
                        <?php endif; ?>

                        <h3><?php echo htmlspecialchars($category["category_name"]); ?></h3>
                    </a>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="empty">No categories available yet.</div>

            <?php endif; ?>

        </div>
    </section>

    <!-- LATEST PRODUCTS -->
    <section class="section">

        <div class="section-heading">
            <div>
                <div class="heading-label">Our latest collection</div>
                <h2>Latest Products</h2>
            </div>
            <a href="products.php">View All Products →</a>
        </div>

        <div class="product-grid">

            <?php if ($products && mysqli_num_rows($products) > 0): ?>

                <?php while ($product = mysqli_fetch_assoc($products)): ?>

                    <div class="product-card">

                        <div class="product-image">

                            <?php if (!empty($product["image"])): ?>
                                <img
                                    src="../assets/images/<?php echo htmlspecialchars(basename($product["image"])); ?>"
                                    alt="<?php echo htmlspecialchars($product["product_name"]); ?>"
                                >
                            <?php else: ?>
                                <div class="product-placeholder">🛒</div>
                            <?php endif; ?>

                        </div>

                        <div class="product-info">

                            <span class="product-category">
                                <?php echo htmlspecialchars($product["category_name"] ?? "General"); ?>
                            </span>

                            <h3><?php echo htmlspecialchars($product["product_name"]); ?></h3>

                            <div class="product-bottom">

                                <span class="price">
                                    <?php echo number_format((float)$product["selling_price"], 2); ?> ETB
                                </span>

                                <a class="view-btn"
                                   href="product_details.php?id=<?php echo (int)$product["id"]; ?>">
                                    View Product
                                </a>

                            </div>
                        </div>
                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="empty">
                    No products available yet. Please check again later.
                </div>

            <?php endif; ?>

        </div>
    </section>

</main>

<footer class="footer">
    <p>© <?php echo date("Y"); ?> <strong>EteteSuperMarket</strong>. All rights reserved.</p>
    <p style="margin-top:8px;">Fresh Shopping • Great Prices • Easy Living</p>
</footer>

</body>
</html>
