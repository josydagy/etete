
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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../customer/products.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];
$product_id = (int) ($_POST["product_id"] ?? 0);

if ($product_id <= 0) {
    die("Invalid product ID.");
}

// Check product
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, quantity FROM products WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);

$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$product) {
    die("Product not found.");
}

if ((int)$product["quantity"] < 1) {
    die("This product is out of stock.");
}

// Create cart or get existing cart ID
$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO cart (user_id)
     VALUES (?)
     ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)"
);

if (!$stmt) {
    die("Cart SQL Error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $user_id);

if (!mysqli_stmt_execute($stmt)) {
    die("Cart Creation Error: " . mysqli_stmt_error($stmt));
}

$cart_id = mysqli_insert_id($conn);

// Add product to cart
$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO cart_items (cart_id, product_id, quantity)
     VALUES (?, ?, 1)
     ON DUPLICATE KEY UPDATE quantity = quantity + 1"
);

if (!$stmt) {
    die("Cart Items SQL Error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "ii", $cart_id, $product_id);

if (!mysqli_stmt_execute($stmt)) {
    die("Add Product Error: " . mysqli_stmt_error($stmt));
}

// Success
header("Location: ../customer/cart.php?added=1");
exit();
?>
