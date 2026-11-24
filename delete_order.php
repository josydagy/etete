
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
    header("Location: ../customer/my_orders.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];
$order_id = (int) ($_POST["order_id"] ?? 0);

if ($order_id <= 0) {
    die("Invalid order ID.");
}

mysqli_begin_transaction($conn);

try {
    // Confirm that this cancelled order belongs to this customer
    $check = mysqli_prepare(
        $conn,
        "SELECT id FROM orders
         WHERE id = ? AND user_id = ? AND status = 'Cancelled'
         FOR UPDATE"
    );

    mysqli_stmt_bind_param($check, "ii", $order_id, $user_id);
    mysqli_stmt_execute($check);

    $result = mysqli_stmt_get_result($check);

    if (mysqli_num_rows($result) !== 1) {
        throw new Exception("Order not found or cannot be deleted.");
    }

    mysqli_stmt_close($check);

    // Delete payment records
    $payment = mysqli_prepare(
        $conn,
        "DELETE FROM payments WHERE order_id = ?"
    );
    mysqli_stmt_bind_param($payment, "i", $order_id);
    mysqli_stmt_execute($payment);
    mysqli_stmt_close($payment);

    // Delete order items
    $items = mysqli_prepare(
        $conn,
        "DELETE FROM order_items WHERE order_id = ?"
    );
    mysqli_stmt_bind_param($items, "i", $order_id);
    mysqli_stmt_execute($items);
    mysqli_stmt_close($items);

    // Delete the order itself
    $order = mysqli_prepare(
        $conn,
        "DELETE FROM orders WHERE id = ? AND user_id = ?"
    );
    mysqli_stmt_bind_param($order, "ii", $order_id, $user_id);
    mysqli_stmt_execute($order);

    if (mysqli_stmt_affected_rows($order) !== 1) {
        throw new Exception("Order could not be deleted.");
    }

    mysqli_stmt_close($order);

    mysqli_commit($conn);

    header("Location: ../customer/my_orders.php?deleted=1");
    exit();

} catch (Throwable $e) {
    mysqli_rollback($conn);
    die("Delete failed: " . htmlspecialchars($e->getMessage()));
}
?>
