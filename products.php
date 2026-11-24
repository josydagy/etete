
<?php
session_start();
require_once __DIR__ . "/../db.php";

if (!isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "customer") {
    header("Location: ../login.php");
    exit();
}

// Selected category
$selected_category = $_GET["category"] ?? "All";

// Search
$search = trim($_GET["search"] ?? "");

// Get categories
$categories = mysqli_query(
    $conn,
    "SELECT * FROM categories ORDER BY category_name ASC"
);

// Get products
if ($selected_category === "All") {

    $sql = "SELECT p.*, c.category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.product_name LIKE ?
            ORDER BY p.id DESC";

    $stmt = mysqli_prepare($conn, $sql);
    $search_term = "%" . $search . "%";

    mysqli_stmt_bind_param($stmt, "s", $search_term);

} else {

    $sql = "SELECT p.*, c.category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE c.category_name = ?
            AND p.product_name LIKE ?
            ORDER BY p.id DESC";

    $stmt = mysqli_prepare($conn, $sql);
    $search_term = "%" . $search . "%";

    mysqli_stmt_bind_param(
        $stmt,
        "ss",
        $selected_category,
        $search_term
    );
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Products | EteteSuperMarket</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background: #f4f7fb;
    color: #172b4d;
}

/* HEADER */
header {
    background: #102a43;
    color: white;
    padding: 18px 7%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.logo {
    font-size: 24px;
    font-weight: bold;
}

nav {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

nav a {
    color: white;
    text-decoration: none;
    font-size: 14px;
    transition: 0.3s;
}

nav a:hover {
    color: #7dd3fc;
}

/* MAIN CONTAINER */
.container {
    width: 86%;
    max-width: 1400px;
    margin: 40px auto;
}

.page-title {
    font-size: 30px;
    margin-bottom: 8px;
}

.subtitle {
    color: #778398;
    margin-bottom: 25px;
}

/* SEARCH */
.search-box {
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
    max-width: 550px;
}

.search-box input {
    flex: 1;
    padding: 13px 16px;
    border: 1px solid #dce3ec;
    border-radius: 8px;
    outline: none;
    font-size: 14px;
}

.search-box input:focus {
    border-color: #1769aa;
}

.search-box button {
    padding: 13px 22px;
    border: none;
    border-radius: 8px;
    background: #1769aa;
    color: white;
    cursor: pointer;
}

/* CATEGORY BUTTONS */
.category-title {
    margin-bottom: 15px;
    font-size: 19px;
}

.category-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 35px;
}

.category-buttons a {
    padding: 11px 22px;
    background: white;
    color: #1769aa;
    border: 1px solid #1769aa;
    border-radius: 25px;
    text-decoration: none;
    font-size: 14px;
    font-weight: bold;
    transition: 0.3s;
}

.category-buttons a:hover,
.category-buttons a.active {
    background: #1769aa;
    color: white;
}

/* PRODUCT GRID */
.products {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 24px;
}

/* PRODUCT CARD */
.product-card {
    background: white;
    border-radius: 14px;
    padding: 18px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.05);
    transition: 0.3s;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.10);
}

/* PRODUCT IMAGE */
.product-image {
    width: 100%;
    height: 180px;
    object-fit: contain;
    background: #f8fafc;
    border-radius: 10px;
    margin-bottom: 17px;
    padding: 10px;
}

/* DEFAULT ICON WHEN IMAGE IS MISSING */
.product-icon {
    height: 180px;
    background: linear-gradient(135deg, #eaf3fb, #d9eafa);
    border-radius: 10px;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 65px;
    margin-bottom: 17px;
}

.product-card h3 {
    font-size: 18px;
    margin-bottom: 10px;
}

.category {
    color: #778398;
    font-size: 13px;
    margin-bottom: 12px;
}

.price {
    color: #1769aa;
    font-size: 21px;
    font-weight: bold;
    margin-bottom: 10px;
}

.stock {
    color: #667085;
    font-size: 13px;
    margin-bottom: 17px;
}

/* ADD TO CART */
.add-cart {
    display: block;
    width: 100%;
    padding: 13px;
    background: #1769aa;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
}

.add-cart:hover {
    background: #104c80;
}

.add-cart:disabled {
    background: #999;
    cursor: not-allowed;
}

/* EMPTY MESSAGE */
.empty {
    grid-column: 1 / -1;
    background: white;
    padding: 45px;
    border-radius: 12px;
    text-align: center;
    color: #667085;
}

/* FOOTER */
footer {
    background: #102a43;
    color: white;
    text-align: center;
    padding: 22px;
    margin-top: 60px;
}

/* RESPONSIVE */
@media(max-width: 768px) {

    header {
        padding: 18px 5%;
    }

    .container {
        width: 90%;
    }

    .page-title {
        font-size: 25px;
    }

    nav {
        gap: 12px;
    }

    .products {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    }
}

@media(max-width: 480px) {

    .search-box {
        flex-direction: column;
    }

    .search-box button {
        width: 100%;
    }

    .products {
        grid-template-columns: 1fr;
    }
}

</style>
</head>

<body>

<!-- HEADER -->
<header>

    <div class="logo">🛒 EteteSuperMarket</div>

    <nav>
        <a href="home.php">Home</a>
        <a href="products.php">Products</a>
        <a href="cart.php">My Cart</a>
        <a href="my_orders.php">My Orders</a>
        <a href="profile.php">Profile</a>
        <a href="../logout.php">Logout</a>
    </nav>

</header>

<!-- MAIN -->
<div class="container">

    <h1 class="page-title">Our Products</h1>

    <p class="subtitle">
        Discover quality products at affordable prices.
    </p>

    <!-- SEARCH -->
    <form method="GET" action="products.php" class="search-box">

        <input
            type="hidden"
            name="category"
            value="<?php echo htmlspecialchars($selected_category); ?>"
        >

        <input
            type="text"
            name="search"
            placeholder="Search products..."
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <button type="submit">Search</button>

    </form>

    <!-- CATEGORY FILTER -->
    <h3 class="category-title">Shop By Category</h3>

    <div class="category-buttons">

        <a
            href="products.php"
            class="<?php echo $selected_category === 'All' ? 'active' : ''; ?>"
        >
            All
        </a>

        <?php while ($cat = mysqli_fetch_assoc($categories)): ?>

            <a
                href="products.php?category=<?php echo urlencode($cat['category_name']); ?>"
                class="<?php echo $selected_category === $cat['category_name'] ? 'active' : ''; ?>"
            >
                <?php echo htmlspecialchars($cat['category_name']); ?>
            </a>

        <?php endwhile; ?>

    </div>

    <!-- PRODUCTS -->
    <div class="products">

        <?php if (mysqli_num_rows($result) > 0): ?>

            <?php while ($product = mysqli_fetch_assoc($result)): ?>

                <div class="product-card">

                    <!-- PRODUCT IMAGE -->
                    <?php if (!empty($product["image"])): ?>

                        <img
                            src="../assets/images/<?php echo htmlspecialchars(basename($product["image"])); ?>"
                            alt="<?php echo htmlspecialchars($product["product_name"]); ?>"
                            class="product-image"
                        >

                    <?php else: ?>

                        <div class="product-icon">🛒</div>

                    <?php endif; ?>

                    <!-- PRODUCT NAME -->
                    <h3>
                        <?php echo htmlspecialchars($product["product_name"]); ?>
                    </h3>

                    <!-- CATEGORY -->
                    <p class="category">
                        Category:
                        <?php echo htmlspecialchars($product["category_name"] ?? "Uncategorized"); ?>
                    </p>

                    <!-- PRICE -->
                    <p class="price">
                        <?php echo number_format((float)$product["selling_price"], 2); ?>
                        ETB
                    </p>

                    <!-- STOCK -->
                    <p class="stock">
                        Available:
                        <?php echo (int)$product["quantity"]; ?>
                    </p>

                    <!-- ADD TO CART -->
                    <form action="../actions/add_to_cart.php" method="POST">

                        <input
                            type="hidden"
                            name="product_id"
                            value="<?php echo (int)$product["id"]; ?>"
                        >

                        <button
                            type="submit"
                            class="add-cart"
                            <?php echo ((int)$product["quantity"] <= 0) ? "disabled" : ""; ?>
                        >
                            <?php echo ((int)$product["quantity"] <= 0)
                                ? "Out of Stock"
                                : "🛒 Add to Cart"; ?>
                        </button>

                    </form>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty">
                <h2>No Products Found</h2>
                <p>Try another category or search keyword.</p>
            </div>

        <?php endif; ?>

    </div>

</div>

<!-- FOOTER -->
<footer>
    © 2026 SuperMarket Management System. All Rights Reserved.
</footer>

</body>
</html>
