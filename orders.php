<?php
session_start();
require_once __DIR__ . "/../db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: ../login.php");
    exit();
}

$sql = "SELECT
            o.id,
            o.user_id,
            o.full_name,
            o.total_amount,
            o.delivery_address,
            o.status,
            o.created_at,
            u.username,
            u.email,
            p.method,
            p.status AS payment_status,
            p.transaction_reference
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN payments p ON o.id = p.order_id
        ORDER BY o.id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database Error: " . htmlspecialchars(mysqli_error($conn)));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Orders - Admin</title>
<style>
* { box-sizing: border-box; }
body { margin: 0; font-family: Arial, sans-serif; background: #f1f5f9; color: #1e293b; }
.container { width: 94%; max-width: 1250px; margin: 35px auto; }
.header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px; }
h1 { color: #172554; margin-bottom: 5px; }
.subtitle { color: #64748b; }
.back-btn { background: #172554; color: white; padding: 12px 18px; border-radius: 8px; text-decoration: none; }
.order-card { background: white; padding: 22px; border-radius: 12px; margin-bottom: 22px; box-shadow: 0 3px 12px rgba(0,0,0,.05); }
.order-top { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; }
.details { line-height: 2; margin-top: 15px; overflow-wrap: anywhere; }
.status { display: inline-block; padding: 7px 12px; border-radius: 20px; background: #e2e8f0; font-weight: bold; font-size: 13px; }
.Pending { background: #fef3c7; color: #92400e; }
.Cancelled { background: #fee2e2; color: #991b1b; }
.Completed, .Complete { background: #dcfce7; color: #166534; }
.Processing { background: #dbeafe; color: #1e40af; }
table { width: 100%; border-collapse: collapse; margin-top: 18px; }
th, td { padding: 12px; border-bottom: 1px solid #e2e8f0; text-align: left; }
th { background: #f8fafc; }
.table-wrap { overflow-x: auto; }
.empty { background: white; padding: 35px; text-align: center; border-radius: 10px; }
.update-section { margin-top: 22px; padding-top: 15px; border-top: 1px solid #e2e8f0; }
select { padding: 10px; border: 1px solid #cbd5e1; border-radius: 7px; margin-right: 8px; margin-bottom: 8px; }
.update-btn { background: #172554; color: white; padding: 11px 18px; border: 0; border-radius: 7px; cursor: pointer; }
.update-btn:hover { background: #263d7a; }
.msg-success { background: #dcfce7; color: #166534; padding: 12px; border-radius: 8px; margin-top: 15px; font-weight: bold; border: 1px solid #bbf7d0; }
</style>
</head>

<body>
<div class="container">
    <div class="header">
        <div>
            <h1>Orders Management</h1>
            <p class="subtitle">View all customer orders and payment information.</p>
        </div>
        <a href="dashboard.php" class="back-btn">← Admin Dashboard</a>
    </div>

    <?php if (isset($_GET["updated"])): ?>
        <p style="background:#dcfce7;color:#166534;padding:14px;border-radius:8px;">Order status updated successfully!</p>
    <?php endif; ?>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <?php while ($order = mysqli_fetch_assoc($result)): 
            $raw_pay_status = $order["payment_status"] ?? "Pending";
            $is_completed = (strtolower(trim($raw_pay_status)) === 'completed' || strtolower(trim($raw_pay_status)) === 'complete');
        ?>
            <div class="order-card">
                <div class="order-top">
                    <div>
                        <h3>Order #<?php echo (int)$order["id"]; ?></h3>
                        <small><?php echo htmlspecialchars($order["created_at"]); ?></small>
                    </div>
                    <span class="status <?php echo htmlspecialchars($order["status"]); ?>">
                        <?php echo htmlspecialchars($order["status"]); ?>
                    </span>
                </div>

                <div class="details">
                    <strong>Customer:</strong> <?php echo htmlspecialchars($order["full_name"]); ?><br>
                    <strong>Username:</strong> <?php echo htmlspecialchars($order["username"] ?? "N/A"); ?><br>
                    <strong>Email:</strong> <?php echo htmlspecialchars($order["email"] ?? "N/A"); ?><br>
                    <strong>Delivery Address:</strong> <?php echo htmlspecialchars($order["delivery_address"]); ?><br>
                    <strong>Payment Method:</strong> <?php echo htmlspecialchars($order["method"] ?? "N/A"); ?><br>
                    
                    <strong>Payment Status:</strong> 
                    <span style="font-weight: bold; color: <?php echo $is_completed ? '#166534' : '#92400e'; ?>">
                        <?php echo htmlspecialchars($raw_pay_status); ?>
                    </span><br>

                    <strong>Transaction Reference:</strong> <?php echo htmlspecialchars($order["transaction_reference"] ?? "N/A"); ?><br>
                    <strong>Total:</strong> <?php echo number_format((float)$order["total_amount"], 2); ?> ETB
                </div>

                <?php if ($is_completed): ?>
                    <div class="msg-success">
                        ✓ Payment Status: Completed (ክፍያው በአስተዳዳሪው ተረጋግጧል)
                    </div>
                <?php endif; ?>

                <h4>Ordered Products</h4>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $item_sql = "SELECT product_name, quantity, unit_price FROM order_items WHERE order_id = ?";
                        $item_stmt = mysqli_prepare($conn, $item_sql);
                        mysqli_stmt_bind_param($item_stmt, "i", $order["id"]);
                        mysqli_stmt_execute($item_stmt);
                        $items = mysqli_stmt_get_result($item_stmt);

                        while ($item = mysqli_fetch_assoc($items)):
                            $subtotal = $item["quantity"] * $item["unit_price"];
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item["product_name"]); ?></td>
                                <td><?php echo (int)$item["quantity"]; ?></td>
                                <td><?php echo number_format((float)$item["unit_price"], 2); ?> ETB</td>
                                <td><?php echo number_format((float)$subtotal, 2); ?> ETB</td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php mysqli_stmt_close($item_stmt); ?>

                <!-- UPDATE ORDER STATUS -->
                <div class="update-section">
                    <h4>Update Order Status</h4>
                    <form action="../actions/update_order_status.php" method="POST">
                        <input type="hidden" name="order_id" value="<?php echo (int)$order["id"]; ?>">
                        <select name="status" required>
                            <option value="Pending" <?php if ($order["status"] === "Pending") echo "selected"; ?>>Pending</option>
                            <option value="Processing" <?php if ($order["status"] === "Processing") echo "selected"; ?>>Processing</option>
                            <option value="Completed" <?php if ($order["status"] === "Completed") echo "selected"; ?>>Completed</option>
                            <option value="Cancelled" <?php if ($order["status"] === "Cancelled") echo "selected"; ?>>Cancelled</option>
                        </select>
                        <button type="submit" class="update-btn">Update Status</button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty">
            <h3>No Orders Found</h3>
            <p>Customer orders will appear here.</p>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>