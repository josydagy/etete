<?php
session_start();
require_once __DIR__ . "/../db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

$message = "";
$error = "";

// Verify or reject payment
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $payment_id = (int)($_POST["payment_id"] ?? 0);
    $new_status = $_POST["status"] ?? "";

    if ($payment_id <= 0 || !in_array($new_status, ["Verified", "Rejected"], true)) {
        $error = "Invalid payment information.";
    } else {

        $stmt = $conn->prepare(
            "UPDATE payments
             SET status = ?, verified_by = ?
             WHERE id = ? AND status = 'Pending'
             AND method IN ('CBE', 'Telebirr')"
        );

        $admin_id = (int)$_SESSION["user_id"];
        $stmt->bind_param("sii", $new_status, $admin_id, $payment_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Payment status updated successfully!";
        } else {
            $error = "Payment not updated. It may already be processed or may be a cash payment.";
        }

        $stmt->close();
    }
}

// Get payments
$sql = "
    SELECT
        p.id,
        p.order_id,
        p.method,
        p.transaction_reference,
        p.amount,
        p.status,
        p.created_at,
        u.full_name,
        u.username
    FROM payments p
    INNER JOIN orders o ON p.order_id = o.id
    INNER JOIN users u ON o.user_id = u.id
    ORDER BY p.id DESC
";

$payments = $conn->query($sql);

if (!$payments) {
    die("Database error: " . htmlspecialchars($conn->error));
}

$totalPayments = $conn->query(
    "SELECT COUNT(*) AS total FROM payments"
)->fetch_assoc()["total"];

$pendingPayments = $conn->query(
    "SELECT COUNT(*) AS total FROM payments WHERE status = 'Pending'"
)->fetch_assoc()["total"];

$verifiedPayments = $conn->query(
    "SELECT COUNT(*) AS total FROM payments WHERE status = 'Verified'"
)->fetch_assoc()["total"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payments Management</title>

<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: Arial, sans-serif;
    background: #f4f6fb;
    color: #263247;
}

header {
    background: #172554;
    color: white;
    padding: 20px 5%;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

header a {
    color: white;
    text-decoration: none;
}

.container {
    width: 94%;
    max-width: 1300px;
    margin: 30px auto;
}

.back {
    display: inline-block;
    margin-bottom: 20px;
    color: #2563eb;
    text-decoration: none;
}

.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 18px;
    margin: 20px 0;
}

.card, .panel {
    background: white;
    padding: 22px;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,.05);
}

.card p {
    color: #64748b;
    margin-bottom: 10px;
}

.card h2 {
    color: #172554;
}

.panel {
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 1000px;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    padding: 13px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

th {
    background: #f8fafc;
}

.badge {
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.pending {
    background: #fef3c7;
    color: #92400e;
}

.verified {
    background: #dcfce7;
    color: #166534;
}

.rejected {
    background: #fee2e2;
    color: #991b1b;
}

.cash {
    background: #dbeafe;
    color: #1e40af;
}

.actions {
    display: flex;
    gap: 7px;
}

button {
    border: none;
    padding: 9px 12px;
    border-radius: 6px;
    color: white;
    cursor: pointer;
}

.approve {
    background: #16a34a;
}

.reject {
    background: #dc2626;
}

.message {
    background: #dcfce7;
    color: #166534;
    padding: 13px;
    border-radius: 7px;
    margin: 15px 0;
}

.error {
    background: #fee2e2;
    color: #991b1b;
    padding: 13px;
    border-radius: 7px;
    margin: 15px 0;
}
</style>
</head>

<body>

<header>
    <h2>Etetemarket Admin</h2>
    <a href="../logout.php">Logout</a>
</header>

<div class="container">

<a href="dashboard.php" class="back">← Back to Dashboard</a>

<h2>Payments Management</h2>

<?php if ($message): ?>
<div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="cards">

    <div class="card">
        <p>Total Payments</p>
        <h2><?= (int)$totalPayments ?></h2>
    </div>

    <div class="card">
        <p>Pending Payments</p>
        <h2><?= (int)$pendingPayments ?></h2>
    </div>

    <div class="card">
        <p>Verified Payments</p>
        <h2><?= (int)$verifiedPayments ?></h2>
    </div>

</div>

<div class="panel">

<h3>All Customer Payments</h3>

<table>
<thead>
<tr>
    <th>Payment ID</th>
    <th>Order ID</th>
    <th>Customer</th>
    <th>Method</th>
    <th>Transaction Reference</th>
    <th>Amount</th>
    <th>Status</th>
    <th>Date</th>
    <th>Action</th>
</tr>
</thead>

<tbody>

<?php if ($payments->num_rows > 0): ?>

<?php while ($payment = $payments->fetch_assoc()): ?>

<tr>
    <td><?= (int)$payment["id"] ?></td>

    <td>#<?= (int)$payment["order_id"] ?></td>

    <td>
        <?= htmlspecialchars($payment["full_name"]) ?>
        <br>
        <small><?= htmlspecialchars($payment["username"]) ?></small>
    </td>

    <td><?= htmlspecialchars($payment["method"]) ?></td>

    <td>
        <?= htmlspecialchars($payment["transaction_reference"] ?? "N/A") ?>
    </td>

    <td><?= number_format((float)$payment["amount"], 2) ?></td>

    <td>
        <?php
        $statusClass = "pending";

        if ($payment["status"] === "Verified") {
            $statusClass = "verified";
        } elseif ($payment["status"] === "Rejected") {
            $statusClass = "rejected";
        } elseif ($payment["status"] === "Cash on delivery") {
            $statusClass = "cash";
        }
        ?>
        <span class="badge <?= $statusClass ?>">
            <?= htmlspecialchars($payment["status"]) ?>
        </span>
    </td>

    <td><?= htmlspecialchars($payment["created_at"]) ?></td>

    <td>
        <?php if (
            $payment["status"] === "Pending" &&
            in_array($payment["method"], ["CBE", "Telebirr"], true)
        ): ?>

        <div class="actions">

            <form method="POST"
                  onsubmit="return confirm('Verify this payment?');">
                <input type="hidden" name="payment_id"
                       value="<?= (int)$payment["id"] ?>">
                <input type="hidden" name="status" value="Verified">
                <button class="approve" type="submit">Verify</button>
            </form>

            <form method="POST"
                  onsubmit="return confirm('Reject this payment?');">
                <input type="hidden" name="payment_id"
                       value="<?= (int)$payment["id"] ?>">
                <input type="hidden" name="status" value="Rejected">
                <button class="reject" type="submit">Reject</button>
            </form>

        </div>

        <?php else: ?>
            -
        <?php endif; ?>
    </td>
</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>
    <td colspan="9">No payments found.</td>
</tr>

<?php endif; ?>

</tbody>
</table>

</div>
</div>

</body>
</html>
