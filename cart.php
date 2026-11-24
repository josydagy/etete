
<?php
session_start();
require_once __DIR__ . "/../db.php";

if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "customer"
) {
    header("Location: ../login.php");
    exit();
}

$user_id = (int)$_SESSION["user_id"];

$sql = "SELECT
            ci.id AS cart_item_id,
            ci.quantity,
            p.id AS product_id,
            p.product_name,
            p.selling_price,
            p.quantity AS available_stock
        FROM cart c
        JOIN cart_items ci ON c.id = ci.cart_id
        JOIN products p ON ci.product_id = p.id
        WHERE c.user_id = ?
        ORDER BY ci.id DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$items = [];
$total = 0;

while ($item = mysqli_fetch_assoc($result)) {
    $items[] = $item;
    $total += (float)$item["selling_price"] * (int)$item["quantity"];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Cart | EteteSuperMarket</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Arial, sans-serif;
}

body {
    background: #f4f7fc;
    color: #1e293b;
}

/* HEADER */
header {
    background: linear-gradient(120deg, #0f2747, #1769aa);
    color: white;
    padding: 20px 7%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 18px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.12);
}

.logo {
    font-size: 26px;
    font-weight: 800;
}

.logo span {
    color: #60d6c4;
}

nav {
    display: flex;
    align-items: center;
    gap: 25px;
    flex-wrap: wrap;
}

nav a {
    color: white;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    transition: 0.3s;
}

nav a:hover,
nav a.active {
    color: #60d6c4;
}

/* MAIN */
.container {
    width: 90%;
    max-width: 1200px;
    margin: 45px auto 70px;
}

.page-title {
    font-size: 32px;
    font-weight: 800;
    color: #102a43;
    margin-bottom: 10px;
}

.subtitle {
    color: #718096;
    font-size: 15px;
    margin-bottom: 30px;
}

/* CART LAYOUT */
.cart-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 25px;
    align-items: start;
}

.cart-box,
.summary-box {
    background: white;
    border-radius: 18px;
    padding: 28px;
    box-shadow: 0 8px 30px rgba(15, 23, 42, 0.06);
}

.cart-heading {
    font-size: 20px;
    font-weight: 800;
    margin-bottom: 10px;
    color: #102a43;
}

.cart-description {
    color: #94a3b8;
    font-size: 13px;
    margin-bottom: 15px;
}

/* PRODUCT */
.cart-item {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto auto;
    align-items: center;
    gap: 20px;
    padding: 25px 0;
    border-bottom: 1px solid #edf1f6;
}

.product-info h3 {
    font-size: 17px;
    color: #102a43;
    margin-bottom: 12px;
}

.product-info p {
    color: #718096;
    font-size: 13px;
    margin-top: 7px;
}

.price {
    color: #087e70;
    font-weight: 800;
}

.stock {
    color: #64748b;
    font-size: 12px;
}

/* FORMS */
.quantity-form {
    display: flex;
    align-items: center;
    gap: 8px;
}

.quantity-form input {
    width: 70px;
    padding: 11px 8px;
    border: 1px solid #dce4ed;
    border-radius: 8px;
    text-align: center;
    font-size: 14px;
    outline: none;
}

.quantity-form input:focus {
    border-color: #1769aa;
    box-shadow: 0 0 0 3px rgba(23,105,170,.1);
}

button {
    border: none;
    cursor: pointer;
    font-weight: 700;
    transition: 0.25s;
    font-size: 13px;
}

.update-btn {
    background: #1769aa;
    color: white;
    padding: 12px 16px;
    border-radius: 8px;
}

.update-btn:hover {
    background: #0d4f85;
    transform: translateY(-2px);
}

.remove-btn {
    background: #fff1f2;
    color: #e11d48;
    padding: 12px 14px;
    border-radius: 8px;
}

.remove-btn:hover {
    background: #e11d48;
    color: white;
}

/* SUMMARY */
.summary-box {
    position: sticky;
    top: 25px;
}

.summary-title {
    font-size: 21px;
    font-weight: 800;
    color: #102a43;
    margin-bottom: 22px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 17px;
    color: #64748b;
    font-size: 14px;
}

.summary-total {
    border-top: 1px solid #e5eaf0;
    padding-top: 20px;
    margin-top: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 17px;
    font-weight: 800;
    color: #102a43;
}

.total-price {
    color: #087e70;
    font-size: 22px;
}

/* CHECKOUT BUTTON */
.checkout-btn {
    display: block;
    width: 100%;
    margin-top: 25px;
    padding: 15px;
    text-align: center;
    border-radius: 10px;
    background: linear-gradient(120deg, #1769aa, #078b78);
    color: white;
    text-decoration: none;
    font-size: 15px;
    font-weight: 800;
    transition: 0.3s;
}

.checkout-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 18px rgba(23,105,170,.2);
}

.continue-btn {
    display: block;
    text-align: center;
    margin-top: 18px;
    color: #1769aa;
    text-decoration: none;
    font-weight: 700;
    font-size: 14px;
}

.continue-btn:hover {
    color: #087e70;
}

/* EMPTY CART */
.empty {
    text-align: center;
    padding: 70px 20px;
}

.empty-icon {
    font-size: 75px;
    margin-bottom: 20px;
}

.empty h2 {
    color: #102a43;
    font-size: 26px;
    margin-bottom: 12px;
}

.empty p {
    color: #718096;
    font-size: 15px;
}

.empty a {
    display: inline-block;
    margin-top: 25px;
    padding: 14px 27px;
    background: #1769aa;
    color: white;
    text-decoration: none;
    border-radius: 9px;
    font-weight: 700;
}

/* FOOTER */
footer {
    background: #102a43;
    color: #cbd5e1;
    text-align: center;
    padding: 25px;
    font-size: 13px;
}

/* RESPONSIVE */
@media(max-width: 950px) {
    .cart-layout {
        grid-template-columns: 1fr;
    }

    .summary-box {
        position: static;
    }
}

@media(max-width: 700px) {
    header {
        padding: 18px 5%;
    }

    nav {
        gap: 15px;
    }

    .container {
        width: 92%;
        margin: 30px auto 50px;
    }

    .page-title {
        font-size: 26px;
    }

    .cart-box,
    .summary-box {
        padding: 20px;
    }

    .cart-item {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .quantity-form {
        flex-wrap: wrap;
    }

    .remove-btn {
        width: 100%;
    }
}

@media(max-width: 400px) {
    nav {
        gap: 11px;
    }

    nav a {
        font-size: 12px;
    }

    .quantity-form input {
        width: 60px;
    }
}
</style>
</head>

<body>

<header>
    <div class="logo">🛒 EteteSuper<span>Market</span></div>

    <nav>
        <a href="home.php">Home</a>
        <a href="products.php">Products</a>
        <a href="cart.php" class="active">My Cart</a>
        <a href="my_orders.php">My Orders</a>
        <a href="profile.php">Profile</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<div class="container">

    <h1 class="page-title">My Shopping Cart 🛒</h1>

    <p class="subtitle">
        Review your selected products and manage your shopping.
    </p>

    <?php if (count($items) > 0): ?>

    <div class="cart-layout">

        <!-- PRODUCTS -->
        <div class="cart-box">

            <h2 class="cart-heading">Your Selected Products</h2>

            <p class="cart-description">
                Manage product quantities or remove products from your cart.
            </p>

            <?php foreach ($items as $item): ?>

            <div class="cart-item">

                <div class="product-info">

                    <h3>
                        <?php echo htmlspecialchars($item["product_name"]); ?>
                    </h3>

                    <p>
                        Unit Price:
                        <span class="price">
                            <?php echo number_format((float)$item["selling_price"], 2); ?>
                            ETB
                        </span>
                    </p>

                    <p>
                        Subtotal:
                        <strong>
                            <?php
                            echo number_format(
                                (float)$item["selling_price"] *
                                (int)$item["quantity"],
                                2
                            );
                            ?> ETB
                        </strong>
                    </p>

                    <p class="stock">
                        Available Stock:
                        <?php echo (int)$item["available_stock"]; ?>
                    </p>

                </div>

                <!-- UPDATE FORM -->
                <form class="quantity-form"
                      action="../actions/update_cart.php"
                      method="POST">

                    <input type="hidden"
                           name="product_id"
                           value="<?php echo (int)$item["product_id"]; ?>">

                    <input type="hidden"
                           name="action"
                           value="update">

                    <input type="number"
                           name="quantity"
                           value="<?php echo (int)$item["quantity"]; ?>"
                           min="1"
                           max="<?php echo (int)$item["available_stock"]; ?>"
                           required>

                    <button class="update-btn" type="submit">
                        Update
                    </button>

                </form>

                <!-- REMOVE FORM -->
                <form action="../actions/update_cart.php"
                      method="POST">

                    <input type="hidden"
                           name="product_id"
                           value="<?php echo (int)$item["product_id"]; ?>">

                    <input type="hidden"
                           name="action"
                           value="remove">

                    <button class="remove-btn" type="submit">
                        🗑 Remove
                    </button>

                </form>

            </div>

            <?php endforeach; ?>

        </div>

        <!-- ORDER SUMMARY -->
        <div class="summary-box">

            <h2 class="summary-title">Order Summary</h2>

            <div class="summary-row">
                <span>Total Products</span>
                <strong><?php echo count($items); ?></strong>
            </div>

            <div class="summary-row">
                <span>Total Quantity</span>
                <strong>
                    <?php
                    $total_quantity = 0;
                    foreach ($items as $item) {
                        $total_quantity += (int)$item["quantity"];
                    }
                    echo $total_quantity;
                    ?>
                </strong>
            </div>

            <div class="summary-total">
                <span>Total Amount</span>
                <span class="total-price">
                    <?php echo number_format($total, 2); ?> ETB
                </span>
            </div>

            <a href="checkout.php" class="checkout-btn">
                Proceed to Checkout →
            </a>

            <a href="products.php" class="continue-btn">
                ← Continue Shopping
            </a>

        </div>

    </div>

    <?php else: ?>

    <div class="cart-box empty">

        <div class="empty-icon">🛒</div>

        <h2>Your Cart is Empty</h2>

        <p>
            You have not added any products yet.
            Start shopping and add your favorite products.
        </p>

        <a href="products.php">Start Shopping →</a>

    </div>

    <?php endif; ?>

</div>

<footer>
    © 2026 EteteSuperMarket Management System. All Rights Reserved.
</footer>

</body>
</html>
