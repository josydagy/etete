<?php
session_start();
require_once __DIR__ . "/../db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

// Summary statistics
$totalProducts = $conn->query(
    "SELECT COUNT(*) AS total FROM products"
)->fetch_assoc()["total"];

$totalCustomers = $conn->query(
    "SELECT COUNT(*) AS total FROM users WHERE role = 'customer'"
)->fetch_assoc()["total"];

$totalOrders = $conn->query(
    "SELECT COUNT(*) AS total FROM orders"
)->fetch_assoc()["total"];

$pendingOrders = $conn->query(
    "SELECT COUNT(*) AS total FROM orders WHERE status = 'Pending'"
)->fetch_assoc()["total"];

$completedOrders = $conn->query(
    "SELECT COUNT(*) AS total FROM orders WHERE status = 'Completed'"
)->fetch_assoc()["total"];

$lowStock = $conn->query(
    "SELECT COUNT(*) AS total
     FROM products
     WHERE quantity <= reorder_level"
)->fetch_assoc()["total"];

// Verified sales only
$totalSales = $conn->query(
    "SELECT COALESCE(SUM(amount), 0) AS total
     FROM payments
     WHERE status = 'Verified'"
)->fetch_assoc()["total"];

// Cash on delivery amounts are not counted as verified sales
// until payment has actually been collected and verified.

$totalPayments = $conn->query(
    "SELECT COUNT(*) AS total FROM payments"
)->fetch_assoc()["total"];

// Recent orders
$recentOrders = $conn->query(
    "SELECT o.id, o.full_name, o.total_amount,
            o.status, o.created_at, u.username
     FROM orders o
     INNER JOIN users u ON o.user_id = u.id
     ORDER BY o.id DESC
     LIMIT 10"
);

if (!$recentOrders) {
    die("Database error: " . htmlspecialchars($conn->error));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Reports</title>

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
    max-width: 1250px;
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
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 18px;
    margin: 25px 0;
}

.card {
    background: white;
    padding: 23px;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,.05);
}

.card p {
    color: #64748b;
    margin-bottom: 12px;
}

.card h2 {
    color: #172554;
    font-size: 26px;
}

.panel {
    background: white;
    padding: 22px;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,.05);
    margin-bottom: 25px;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 750px;
    border-collapse: collapse;
    margin-top: 18px;
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

.completed {
    background: #dcfce7;
    color: #166534;
}

.cancelled {
    background: #fee2e2;
    color: #991b1b;
}

.processing {
    background: #dbeafe;
    color: #1e40af;
}

.print-btn {
    background: #2563eb;
    color: white;
    border: none;
    padding: 11px 18px;
    border-radius: 7px;
    cursor: pointer;
    margin-bottom: 18px;
}

@media print {
    header, .back, .print-btn {
        display: none;
    }

    body {
        background: white;
    }

    .card, .panel {
        box-shadow: none;
        border: 1px solid #ddd;
    }
}
</style>
</head>

<body>

<header>
    <h2>Supermarket Admin</h2>
    <a href="../logout.php">Logout</a>
</header>

<div class="container">

<a href="dashboard.php" class="back">← Back to Dashboard</a>

<h2>Reports & Analytics</h2>
<p style="color:#64748b; margin-top:8px;">
    Supermarket business overview and performance.
</p>

<br>

<button class="print-btn" onclick="window.print()">
    Print / Save PDF
</button>

<div class="cards">

    <div class="card">
        <p>Total Sales (Verified Payments)</p>
        <h2><?= number_format((float)$totalSales, 2) ?></h2>
    </div>

    <div class="card">
        <p>Total Orders</p>
        <h2><?= (int)$totalOrders ?></h2>
    </div>

    <div class="card">
        <p>Pending Orders</p>
        <h2><?= (int)$pendingOrders ?></h2>
    </div>

    <div class="card">
        <p>Completed Orders</p>
        <h2><?= (int)$completedOrders ?></h2>
    </div>

    <div class="card">
        <p>Total Products</p>
        <h2><?= (int)$totalProducts ?></h2>
    </div>

    <div class="card">
        <p>Total Customers</p>
        <h2><?= (int)$totalCustomers ?></h2>
    </div>

    <div class="card">
        <p>Total Payment Records</p>
        <h2><?= (int)$totalPayments ?></h2>
    </div>

    <div class="card">
        <p>Low Stock Products</p>
        <h2><?= (int)$lowStock ?></h2>
    </div>

</div>

<div class="panel">

<h3>Recent Orders</h3>

<div class="table-wrap">

<table>
<thead>
<tr>
    <th>Order ID</th>
    <th>Customer</th>
    <th>Username</th>
    <th>Total Amount</th>
    <th>Status</th>
    <th>Date</th>
</tr>
</thead>

<tbody>

<?php if ($recentOrders->num_rows > 0): ?>

<?php while ($order = $recentOrders->fetch_assoc()): ?>

<tr>
    <td>#<?= (int)$order["id"] ?></td>

    <td><?= htmlspecialchars($order["full_name"]) ?></td>

    <td><?= htmlspecialchars($order["username"]) ?></td>

    <td><?= number_format((float)$order["total_amount"], 2) ?></td>

    <td>
        <?php
        $status = $order["status"];
        $class = "pending";

        if ($status === "Completed") {
            $class = "completed";
        } elseif ($status === "Cancelled") {
            $class = "cancelled";
        } elseif ($status === "Processing") {
            $class = "processing";
        }
        ?>
        <span class="badge <?= $class ?>">
            <?= htmlspecialchars($status) ?>
        </span>
    </td>

    <td><?= htmlspecialchars($order["created_at"]) ?></td>
</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>
    <td colspan="6">No orders found.</td>
</tr>

<?php endif; ?>

</tbody>
</table>

</div>
</div>

</div>

</body>
</html>
