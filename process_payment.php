
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
    header("Location: ../customer/home.php");
    exit();
}

$user_id = (int)$_SESSION["user_id"];

$order_id = (int)($_POST["order_id"] ?? 0);
$method = $_POST["method"] ?? "";
$reference = trim($_POST["transaction_reference"] ?? "");

$allowed_methods = ["CBE", "Telebirr", "Cash"];

if ($order_id <= 0 || !in_array($method, $allowed_methods, true)) {
    die("Invalid payment information.");
}

// CBE and Telebirr require transaction reference
if (
    ($method === "CBE" || $method === "Telebirr") &&
    $reference === ""
) {
    die("Please enter your transaction reference number.");
}

if (strlen($reference) > 150) {
    die("Transaction reference is too long.");
}

mysqli_begin_transaction($conn);

try {

    // Check order belongs to this customer
    $sql = "SELECT id, total_amount, status
            FROM orders
            WHERE id = ? AND user_id = ?
            FOR UPDATE";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
    mysqli_stmt_execute($stmt);

    $order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$order) {
        throw new Exception("Order not found.");
    }

    // Check whether payment already exists
    $sql = "SELECT id FROM payments WHERE order_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);

    $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($existing) {
        throw new Exception("Payment has already been submitted.");
    }

    $amount = (float)$order["total_amount"];

    // Determine payment status
    if ($method === "Cash") {
        $payment_status = "Cash on delivery";
    } else {
        $payment_status = "Pending";
    }

    // Save payment
    $sql = "INSERT INTO payments
            (
                order_id,
                method,
                transaction_reference,
                amount,
                status
            )
            VALUES (?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "issds",
        $order_id,
        $method,
        $reference,
        $amount,
        $payment_status
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(
            "Payment save failed: " . mysqli_stmt_error($stmt)
        );
    }

    // Update order status for cash payment
    if ($method === "Cash") {

        $sql = "UPDATE orders
                SET status = 'Processing'
                WHERE id = ? AND user_id = ?";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Unable to update order status.");
        }
    }

    mysqli_commit($conn);

    header("Location: ../customer/my_orders.php?payment=success");
    exit();

} catch (Throwable $e) {

    mysqli_rollback($conn);

    die(
        "Payment failed: " .
        htmlspecialchars($e->getMessage())
    );
}
?>
