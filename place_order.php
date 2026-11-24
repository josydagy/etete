
<?php
session_start();
require_once __DIR__ . "/../db.php";

// Check customer login
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "customer"
) {
    header("Location: ../login.php");
    exit();
}

// Only accept POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../customer/checkout.php");
    exit();
}

$user_id = (int)$_SESSION["user_id"];

$full_name = trim($_POST["full_name"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$address = trim($_POST["delivery_address"] ?? "");

// Validate customer information
if ($full_name === "" || $phone === "" || $address === "") {
    die("Please complete all delivery information.");
}

if (strlen($phone) < 7 || strlen($phone) > 30) {
    die("Please enter a valid phone number.");
}

// Start database transaction
mysqli_begin_transaction($conn);

try {

    // Get customer's cart products
    $sql = "SELECT
                ci.product_id,
                ci.quantity,
                p.product_name,
                p.selling_price,
                p.quantity AS stock
            FROM cart c
            JOIN cart_items ci ON c.id = ci.cart_id
            JOIN products p ON ci.product_id = p.id
            WHERE c.user_id = ?
            FOR UPDATE";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        throw new Exception("Unable to read shopping cart.");
    }

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $items = [];
    $total = 0;

    while ($item = mysqli_fetch_assoc($result)) {

        $quantity = (int)$item["quantity"];
        $stock = (int)$item["stock"];

        // Check available stock
        if ($quantity < 1) {
            throw new Exception("Invalid product quantity.");
        }

        if ($quantity > $stock) {
            throw new Exception(
                "Not enough stock for " . $item["product_name"]
            );
        }

        $price = (float)$item["selling_price"];

        $subtotal = $price * $quantity;

        $item["subtotal"] = $subtotal;

        $total += $subtotal;

        $items[] = $item;
    }

    if (count($items) === 0) {
        throw new Exception("Your shopping cart is empty.");
    }

    // Create order
    $sql = "INSERT INTO orders
            (
                user_id,
                full_name,
                phone,
                total_amount,
                delivery_address
            )
            VALUES (?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        throw new Exception("Unable to prepare order.");
    }

    mysqli_stmt_bind_param(
        $stmt,
        "issds",
        $user_id,
        $full_name,
        $phone,
        $total,
        $address
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(
            "Order creation failed: " . mysqli_stmt_error($stmt)
        );
    }

    // Get newly created order ID
    $order_id = mysqli_insert_id($conn);

    // Save every product in order_items
    foreach ($items as $item) {

        $product_id = (int)$item["product_id"];
        $product_name = $item["product_name"];
        $quantity = (int)$item["quantity"];
        $price = (float)$item["selling_price"];

        $sql = "INSERT INTO order_items
                (
                    order_id,
                    product_id,
                    product_name,
                    quantity,
                    unit_price
                )
                VALUES (?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            throw new Exception("Unable to prepare order items.");
        }

        mysqli_stmt_bind_param(
            $stmt,
            "iisid",
            $order_id,
            $product_id,
            $product_name,
            $quantity,
            $price
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(
                "Unable to save order items: " .
                mysqli_stmt_error($stmt)
            );
        }

        // Reduce product stock
        $sql = "UPDATE products
                SET quantity = quantity - ?
                WHERE id = ? AND quantity >= ?";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            throw new Exception("Unable to prepare stock update.");
        }

        mysqli_stmt_bind_param(
            $stmt,
            "iii",
            $quantity,
            $product_id,
            $quantity
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Unable to update product stock.");
        }

        if (mysqli_stmt_affected_rows($stmt) !== 1) {
            throw new Exception(
                "Stock update failed for " . $product_name
            );
        }
    }

    // Clear customer's cart after successful order
    $sql = "DELETE ci
            FROM cart_items ci
            JOIN cart c ON ci.cart_id = c.id
            WHERE c.user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        throw new Exception("Unable to prepare cart cleanup.");
    }

    mysqli_stmt_bind_param($stmt, "i", $user_id);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Unable to clear shopping cart.");
    }

    // Save all changes
    mysqli_commit($conn);

    // Go to payment page
    header(
        "Location: ../customer/payment.php?order_id=" . $order_id
    );
    exit();

} catch (Throwable $e) {

    // Undo all changes if an error occurs
    mysqli_rollback($conn);

    die(
        "Order failed: " .
        htmlspecialchars($e->getMessage())
    );
}
?>
