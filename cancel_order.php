
<?php
session_start();
require_once __DIR__ . "/../db.php";

// Customer access only
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "customer"
) {
    header("Location: ../login.php");
    exit();
}

// POST request only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../customer/my_orders.php");
    exit();
}

$user_id = (int)$_SESSION["user_id"];
$order_id = (int)($_POST["order_id"] ?? 0);

if ($order_id <= 0) {
    die("Invalid order ID.");
}

mysqli_begin_transaction($conn);

try {

    // Find customer's order
    $sql = "SELECT id, status
            FROM orders
            WHERE id = ? AND user_id = ?
            FOR UPDATE";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
    mysqli_stmt_execute($stmt);

    $order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$order) {
        throw new Exception("Order not found.");
    }

    // Allow cancellation only for Pending or Processing
    if (!in_array($order["status"], ["Pending", "Processing"], true)) {
        throw new Exception("This order cannot be cancelled.");
    }

    // Check payment status
    $sql = "SELECT status
            FROM payments
            WHERE order_id = ?
            FOR UPDATE";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);

    $payment = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    // If payment is verified, show a message to customer
    if ($payment && $payment["status"] === "Verified") {

        mysqli_rollback($conn);

        $_SESSION["cancel_error"] =
            "Payment has already been verified by the administrator. You cannot cancel this order. Please contact the administrator for assistance.";

        header("Location: ../customer/my_orders.php");
        exit();
    }

    // Get ordered products
    $sql = "SELECT product_id, SUM(quantity) AS quantity
            FROM order_items
            WHERE order_id = ?
            GROUP BY product_id";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);

    $items = mysqli_stmt_get_result($stmt);

    // Restore product stock
    while ($item = mysqli_fetch_assoc($items)) {

        $product_id = (int)$item["product_id"];
        $quantity = (int)$item["quantity"];

        $update = mysqli_prepare(
            $conn,
            "UPDATE products
             SET quantity = quantity + ?
             WHERE id = ?"
        );

        mysqli_stmt_bind_param($update, "ii", $quantity, $product_id);

        if (!mysqli_stmt_execute($update)) {
            throw new Exception("Unable to restore product stock.");
        }

        mysqli_stmt_close($update);
    }

    mysqli_stmt_close($stmt);

    // Cancel order
    $sql = "UPDATE orders
            SET status = 'Cancelled'
            WHERE id = ? AND user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);

    if (
        !mysqli_stmt_execute($stmt) ||
        mysqli_stmt_affected_rows($stmt) !== 1
    ) {
        throw new Exception("Unable to cancel order.");
    }

    mysqli_stmt_close($stmt);

    // Cancel payment only if it is Pending or Rejected
    if ($payment) {

        $sql = "UPDATE payments
                SET status = 'Cancelled'
                WHERE order_id = ?
                AND status IN ('Pending', 'Rejected')";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $order_id);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Unable to update payment status.");
        }

        mysqli_stmt_close($stmt);
    }

    mysqli_commit($conn);

    header("Location: ../customer/my_orders.php?cancelled=1");
    exit();

} catch (Throwable $e) {

    mysqli_rollback($conn);

    die(
        "Cancellation failed: " .
        htmlspecialchars($e->getMessage())
    );
}
?>
