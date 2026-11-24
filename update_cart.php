
<?php
session_start();
require_once __DIR__ . "/../db.php";

error_reporting(E_ALL);
ini_set("display_errors", 1);

if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "customer"
) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../customer/cart.php");
    exit();
}

$user_id = (int)$_SESSION["user_id"];
$product_id = (int)($_POST["product_id"] ?? 0);
$action = $_POST["action"] ?? "";

if ($product_id <= 0) {
    die("Invalid Product ID: " . htmlspecialchars($_POST["product_id"] ?? "NOT SENT"));
}

// Get customer's cart
$stmt = mysqli_prepare(
    $conn,
    "SELECT id FROM cart WHERE user_id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$cart = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$cart) {
    die("Shopping cart not found.");
}

$cart_id = (int)$cart["id"];

// Check that product belongs to this cart
$stmt = mysqli_prepare(
    $conn,
    "SELECT id FROM cart_items WHERE cart_id = ? AND product_id = ?"
);
mysqli_stmt_bind_param($stmt, "ii", $cart_id, $product_id);
mysqli_stmt_execute($stmt);
$cart_item = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$cart_item) {
    die("This product is not in your cart.");
}

// REMOVE PRODUCT
if ($action === "remove") {

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?"
    );

    mysqli_stmt_bind_param($stmt, "ii", $cart_id, $product_id);
    mysqli_stmt_execute($stmt);

    header("Location: ../customer/cart.php?removed=1");
    exit();
}

// UPDATE QUANTITY
if ($action === "update") {

    $quantity = (int)($_POST["quantity"] ?? 0);

    if ($quantity < 1) {
        die("Quantity must be at least 1.");
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT quantity FROM products WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$product) {
        die("Product not found.");
    }

    if ($quantity > (int)$product["quantity"]) {
        die("Not enough stock available.");
    }

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE cart_items
         SET quantity = ?
         WHERE cart_id = ? AND product_id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt, "iii", $quantity, $cart_id, $product_id
    );

    if (!mysqli_stmt_execute($stmt)) {
        die("Update failed: " . mysqli_stmt_error($stmt));
    }

    header("Location: ../customer/cart.php?updated=1");
    exit();
}

die("Invalid action: " . htmlspecialchars($action));
?>
