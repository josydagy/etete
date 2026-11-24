<?php
session_start();
require_once __DIR__ . "/../db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "customer") {
    header("Location: ../login.php");
    exit();
}

$userId = (int) $_SESSION["user_id"];
$orderId = (int) ($_GET["order_id"] ?? $_POST["order_id"] ?? 0);

$message = "";
$error = "";

// Find this customer's order
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, total_amount, status
     FROM orders
     WHERE id = ? AND user_id = ?"
);

mysqli_stmt_bind_param($stmt, "ii", $orderId, $userId);
mysqli_stmt_execute($stmt);
$orderResult = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($orderResult);
mysqli_stmt_close($stmt);

if (!$order) {
    die("Order not found or you do not have permission to access it.");
}

// Check whether payment was already submitted
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, status FROM payments WHERE order_id = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $orderId);
mysqli_stmt_execute($stmt);
$existingResult = mysqli_stmt_get_result($stmt);
$existingPayment = mysqli_fetch_assoc($existingResult);
mysqli_stmt_close($stmt);

if (isset($_POST["submit_payment"])) {

    if ($existingPayment) {
        $error = "Payment has already been submitted for this order.";
    } else {

        $method = $_POST["method"] ?? "";
        $reference = trim($_POST["transaction_reference"] ?? "");
        $amount = (float) $order["total_amount"];

        if (!in_array($method, ["CBE", "Telebirr", "Cash"], true)) {
            $error = "Please select a valid payment method.";
        } elseif ($method !== "Cash" && $reference === "") {
            $error = "Please enter your transaction reference.";
        } else {

            $receiptFile = null;
            $uploadFolder = __DIR__ . "/../uploads/receipts/";

            if (!is_dir($uploadFolder)) {
                mkdir($uploadFolder, 0755, true);
            }

            try {

                // Receipt is required for CBE and Telebirr
                if ($method !== "Cash") {

                    if (
                        !isset($_FILES["receipt"]) ||
                        $_FILES["receipt"]["error"] === UPLOAD_ERR_NO_FILE
                    ) {
                        throw new Exception("Please upload your payment receipt.");
                    }

                    if ($_FILES["receipt"]["error"] !== UPLOAD_ERR_OK) {
                        throw new Exception("Receipt upload failed.");
                    }

                    if ($_FILES["receipt"]["size"] > 5 * 1024 * 1024) {
                        throw new Exception("Receipt must be less than 5 MB.");
                    }

                    $imageInfo = getimagesize($_FILES["receipt"]["tmp_name"]);
                    $extension = "";

                    $allowedTypes = [
                        "image/jpeg" => "jpg",
                        "image/png" => "png",
                        "image/webp" => "webp"
                    ];

                    if ($imageInfo !== false && isset($allowedTypes[$imageInfo["mime"]])) {
                        $extension = $allowedTypes[$imageInfo["mime"]];
                    } else {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime = finfo_file($finfo, $_FILES["receipt"]["tmp_name"]);
                        finfo_close($finfo);

                        if ($mime === "application/pdf") {
                            $extension = "pdf";
                        } else {
                            throw new Exception("Upload JPG, PNG, WEBP or PDF receipt only.");
                        }
                    }

                    $receiptFile = "receipt_" . bin2hex(random_bytes(12)) . "." . $extension;

                    if (!move_uploaded_file(
                        $_FILES["receipt"]["tmp_name"],
                        $uploadFolder . $receiptFile
                    )) {
                        throw new Exception("Could not save the receipt.");
                    }
                }

                $paymentStatus = ($method === "Cash")
                    ? "Cash on delivery"
                    : "Pending";

                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO payments
                     (order_id, method, transaction_reference, amount, status, receipt_file)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "issdss",
                    $orderId,
                    $method,
                    $reference,
                    $amount,
                    $paymentStatus,
                    $receiptFile
                );

                if (mysqli_stmt_execute($stmt)) {
                    $message = "Payment information submitted successfully. Admin will review your payment.";
                    $existingPayment = [
                        "id" => mysqli_insert_id($conn),
                        "status" => $paymentStatus
                    ];
                } else {
                    if ($receiptFile && is_file($uploadFolder . $receiptFile)) {
                        unlink($uploadFolder . $receiptFile);
                    }
                    $error = "Could not submit payment. Please try again.";
                }

                mysqli_stmt_close($stmt);

            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Payment</title>

<style>
* {
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    margin: 0;
    background: #f1f5f9;
    color: #1e293b;
}

.container {
    max-width: 650px;
    margin: 40px auto;
    padding: 20px;
}

.card {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,.06);
}

h1 {
    margin-top: 0;
    font-size: 26px;
}

.order-info {
    background: #eff6ff;
    padding: 18px;
    border-radius: 10px;
    margin: 20px 0;
}

.order-info p {
    margin: 8px 0;
}

label {
    display: block;
    font-weight: bold;
    margin: 16px 0 8px;
}

input, select {
    width: 100%;
    padding: 13px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 15px;
}

button {
    width: 100%;
    padding: 14px;
    margin-top: 20px;
    border: none;
    border-radius: 8px;
    background: #2563eb;
    color: white;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
}

button:hover {
    background: #1d4ed8;
}

.message {
    padding: 13px;
    border-radius: 8px;
    background: #dcfce7;
    color: #166534;
    margin: 15px 0;
}

.error {
    padding: 13px;
    border-radius: 8px;
    background: #fee2e2;
    color: #991b1b;
    margin: 15px 0;
}

.status {
    display: inline-block;
    padding: 7px 12px;
    border-radius: 20px;
    background: #fef3c7;
    color: #92400e;
    font-weight: bold;
}

.back {
    display: inline-block;
    margin-top: 20px;
    color: #2563eb;
    text-decoration: none;
}

#receiptGroup {
    display: none;
}
</style>
</head>

<body>

<div class="container">
<div class="card">

    <h1>Complete Your Payment</h1>
    <p>Submit your payment information for Admin verification.</p>

    <div class="order-info">
        <p><strong>Order ID:</strong> #<?php echo (int)$order["id"]; ?></p>
        <p><strong>Total Amount:</strong>
            <?php echo number_format((float)$order["total_amount"], 2); ?> ETB
        </p>
    </div>

    <?php if ($message): ?>
        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($existingPayment): ?>

        <h3>Payment Submitted</h3>
        <p>Your payment is being processed.</p>
        <p>Status: <span class="status">
            <?php echo htmlspecialchars($existingPayment["status"]); ?>
        </span></p>

    <?php else: ?>

        <form method="POST" enctype="multipart/form-data">

            <input type="hidden" name="order_id"
                   value="<?php echo (int)$orderId; ?>">

            <label>Payment Method</label>
            <select name="method" id="method" required onchange="toggleReceipt()">
                <option value="">Select Payment Method</option>
                <option value="CBE">CBE</option>
                <option value="Telebirr">Telebirr</option>
                <option value="Cash">Cash on Delivery</option>
            </select>

            <div id="referenceGroup">
                <label>Transaction Reference</label>
                <input type="text"
                       name="transaction_reference"
                       placeholder="Enter transaction reference">
            </div>

            <div id="receiptGroup">
                <label>Upload Payment Receipt</label>
                <input type="file"
                       name="receipt"
                       accept=".jpg,.jpeg,.png,.webp,.pdf">
                <small>JPG, PNG, WEBP or PDF. Maximum 5 MB.</small>
            </div>

            <button type="submit" name="submit_payment">
                Submit Payment
            </button>

        </form>

    <?php endif; ?>

    <a href="my_orders.php" class="back">← Back to My Orders</a>

</div>
</div>

<script>
function toggleReceipt() {
    const method = document.getElementById("method").value;
    const receiptGroup = document.getElementById("receiptGroup");
    const referenceGroup = document.getElementById("referenceGroup");

    if (method === "Cash") {
        receiptGroup.style.display = "none";
        referenceGroup.style.display = "none";
    } else if (method === "CBE" || method === "Telebirr") {
        receiptGroup.style.display = "block";
        referenceGroup.style.display = "block";
    } else {
        receiptGroup.style.display = "none";
        referenceGroup.style.display = "none";
    }
}
</script>

</body>
</html>
