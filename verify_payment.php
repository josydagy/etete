<?php
session_start();
require_once __DIR__ . "/../db.php";

// Only admin can verify payments
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: ../login.php");
    exit();
}

// Only POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin/payments.php");
    exit();
}

$payment_id = (int)($_POST["payment_id"] ?? 0);
$action = $_POST["action"] ?? "";

if ($payment_id <= 0 || !in_array($action, ["verify", "reject"], true)) {
    die("Invalid payment information.");
}

mysqli_begin_transaction($conn);

try {

    // Find payment and lock its row
    $sql = "SELECT id, order_id, status
            FROM payments
            WHERE id = ?
            FOR UPDATE";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $payment_id);
    mysqli_stmt_execute($stmt);

    $payment = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    if (!$payment) {
        throw new Exception("Payment not found.");
    }

    if ($payment["status"] !== "Pending") {
        throw new Exception("This payment has already been processed.");
    }

    $order_id = (int)$payment["order_id"];

    if ($action === "verify") {

        // Verify payment
        $sql = "UPDATE payments
                SET status = 'Verified',
                    verified_by = ?
                WHERE id = ? AND status = 'Pending'";

        $stmt = mysqli_prepare($conn, $sql);
        $admin_id = (int)$_SESSION["user_id"];

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $admin_id,
            $payment_id
        );

        if (!mysqli_stmt_execute($stmt) ||
            mysqli_stmt_affected_rows($stmt) !== 1) {
            throw new Exception("Unable to verify payment.");
        }

        // Change order from Pending to Processing
        $sql = "UPDATE orders
                SET status = 'Processing'
                WHERE id = ? AND status = 'Pending'";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $order_id);

        if (!mysqli_stmt_execute($stmt) ||
            mysqli_stmt_affected_rows($stmt) !== 1) {
            throw new Exception("Unable to update order status.");
        }

        $message = "Payment verified and order moved to Processing.";

    } else {

        // Reject payment
        $sql = "UPDATE payments
                SET status = 'Rejected',
                    verified_by = ?
                WHERE id = ? AND status = 'Pending'";

        $stmt = mysqli_prepare($conn, $sql);
        $admin_id = (int)$_SESSION["user_id"];

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $admin_id,
            $payment_id
        );

        if (!mysqli_stmt_execute($stmt) ||
            mysqli_stmt_affected_rows($stmt) !== 1) {
            throw new Exception("Unable to reject payment.");
        }

        // Keep order Pending so customer can contact admin
        $message = "Payment rejected. Order remains Pending.";
    }

    mysqli_commit($conn);

    header(
        "Location: ../admin/payments.php?message=" .
        urlencode($message)
    );
    exit();

} catch (Throwable $e) {

    mysqli_rollback($conn);

    die(
        "Payment processing failed: " .
        htmlspecialchars($e->getMessage())
    );
}
?>
