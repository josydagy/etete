
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

// Get customer information
$stmt = mysqli_prepare(
    $conn,
    "SELECT full_name, email FROM users WHERE id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$user) {
    die("Customer account not found.");
}

// Get cart products
$sql = "SELECT
            ci.quantity,
            p.id AS product_id,
            p.product_name,
            p.selling_price,
            p.quantity AS available_stock
        FROM cart c
        JOIN cart_items ci ON c.id = ci.cart_id
        JOIN products p ON ci.product_id = p.id
        WHERE c.user_id = ?";

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

if (count($items) === 0) {
    header("Location: cart.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout | SuperMarket</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Arial, sans-serif;
}

body {
    background: #f3f6fb;
    color: #1e293b;
}

header {
    background: linear-gradient(120deg, #102a43, #1769aa);
    padding: 20px 7%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    color: white;
}

.logo {
    font-size: 25px;
    font-weight: 800;
}

.logo span {
    color: #60d6c4;
}

nav {
    display: flex;
    gap: 22px;
    flex-wrap: wrap;
}

nav a {
    color: white;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
}

nav a:hover {
    color: #60d6c4;
}

.container {
    width: 90%;
    max-width: 1100px;
    margin: 45px auto 70px;
}

.page-title {
    font-size: 32px;
    color: #102a43;
    margin-bottom: 8px;
}

.subtitle {
    color: #718096;
    margin-bottom: 30px;
}

.checkout-layout {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 25px;
    align-items: start;
}

.checkout-box,
.summary-box {
    background: white;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 8px 30px rgba(15,23,42,.06);
}

.section-title {
    font-size: 20px;
    font-weight: 800;
    color: #102a43;
    margin-bottom: 22px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 9px;
    color: #334155;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 13px 14px;
    border: 1px solid #dce4ed;
    border-radius: 9px;
    outline: none;
    font-size: 14px;
    background: #fbfdff;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: #1769aa;
    box-shadow: 0 0 0 3px rgba(23,105,170,.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 110px;
}

.order-item {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 15px 0;
    border-bottom: 1px solid #edf1f6;
    font-size: 14px;
}

.order-item strong {
    color: #102a43;
}

.total-row {
    display: flex;
    justify-content: space-between;
    margin-top: 22px;
    font-size: 20px;
    font-weight: 800;
    color: #102a43;
}

.total-row span:last-child {
    color: #078b78;
}

.place-order-btn {
    width: 100%;
    border: none;
    background: linear-gradient(120deg, #1769aa, #078b78);
    color: white;
    padding: 16px;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 800;
    cursor: pointer;
    margin-top: 25px;
    transition: .3s;
}

.place-order-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 7px 18px rgba(23,105,170,.2);
}

.back-link {
    display: block;
    text-align: center;
    margin-top: 18px;
    color: #1769aa;
    text-decoration: none;
    font-weight: 700;
}

footer {
    background: #102a43;
    color: white;
    text-align: center;
    padding: 25px;
}

@media(max-width: 850px) {
    .checkout-layout {
        grid-template-columns: 1fr;
    }
}

@media(max-width: 600px) {
    header {
        padding: 18px 5%;
    }

    nav {
        gap: 13px;
    }

    .container {
        width: 92%;
        margin: 28px auto 50px;
    }

    .checkout-box,
    .summary-box {
        padding: 20px;
    }

    .page-title {
        font-size: 26px;
    }
}
</style>
</head>

<body>

<header>
    <div class="logo">🛒 Super<span>Market</span></div>

    <nav>
        <a href="home.php">Home</a>
        <a href="products.php">Products</a>
        <a href="cart.php">My Cart</a>
        <a href="my_orders.php">My Orders</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<div class="container">

    <h1 class="page-title">Checkout</h1>

    <p class="subtitle">
        Enter your delivery information to place your order.
    </p>

    <div class="checkout-layout">

        <!-- CUSTOMER INFORMATION -->
        <div class="checkout-box">

            <h2 class="section-title">Delivery Information</h2>

            <form action="../actions/place_order.php" method="POST">

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text"
                           name="full_name"
                           value="<?php echo htmlspecialchars($user["full_name"]); ?>"
                           required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email"
                           value="<?php echo htmlspecialchars($user["email"]); ?>"
                           readonly>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel"
                           name="phone"
                           placeholder="Enter your phone number"
                           required>
                </div>

                <div class="form-group">
                    <label>Delivery Address</label>
                    <textarea name="delivery_address"
                              placeholder="Enter your complete delivery address"
                              required></textarea>
                </div>

                <button type="submit" class="place-order-btn">
                    Place Order →
                </button>

            </form>

            <a href="cart.php" class="back-link">
                ← Back to Cart
            </a>

        </div>

        <!-- ORDER SUMMARY -->
        <div class="summary-box">

            <h2 class="section-title">Order Summary</h2>

            <?php foreach ($items as $item): ?>

            <div class="order-item">
                <div>
                    <strong>
                        <?php echo htmlspecialchars($item["product_name"]); ?>
                    </strong>
                    <p>
                        Quantity: <?php echo (int)$item["quantity"]; ?>
                    </p>
                </div>

                <strong>
                    <?php
                    echo number_format(
                        (float)$item["selling_price"] *
                        (int)$item["quantity"],
                        2
                    );
                    ?> ETB
                </strong>
            </div>

            <?php endforeach; ?>

            <div class="total-row">
                <span>Total</span>
                <span>
                    <?php echo number_format($total, 2); ?> ETB
                </span>
            </div>

        </div>

    </div>

</div>

<footer>
    © 2026 SuperMarket Management System. All Rights Reserved.
</footer>

</body>
</html>
