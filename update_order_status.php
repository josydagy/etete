<?php
session_start();
require_once __DIR__ . "/../db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin/orders.php");
    exit();
}

$order_id = (int)($_POST["order_id"] ?? 0);
$new_status = $_POST["status"] ?? "";

$allowed_statuses = ["Pending", "Processing", "Completed", "Cancelled"];

if ($order_id <= 0 || !in_array($new_status, $allowed_statuses, true)) {
    die("Invalid order information.");
}

// 1. Update orders table status
$sql = "UPDATE orders SET status = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// 2. Update payments table status
$payment_status = ($new_status === "Completed") ? "Completed" : $new_status;

$payment_sql = "UPDATE payments SET status = ? WHERE order_id = ?";
$payment_stmt = mysqli_prepare($conn, $payment_sql);
if ($payment_stmt) {
    mysqli_stmt_bind_param($payment_stmt, "si", $payment_status, $order_id);
    mysqli_stmt_execute($payment_stmt);
    mysqli_stmt_close($payment_stmt);
}

header("Location: ../admin/orders.php?updated=1");
exit();
?>