<?php
session_start();
require_once __DIR__ . "/../db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "customer") {
    header("Location: ../login.php");
    exit();
}

$user_id = (int)$_SESSION["user_id"];

$sql = "SELECT 
            o.id,
            o.full_name,
            o.total_amount,
            o.delivery_address,
            o.status AS order_status,
            o.created_at,
            p.method,
            p.status AS payment_status,
            p.transaction_reference
        FROM orders o
        LEFT JOIN payments p ON o.id = p.order_id
        WHERE o.user_id = ?
        ORDER BY o.id DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders - Supermarket</title>
<style>
* { box-sizing: border-box; }
body { margin: 0; font-family: Arial, sans-serif; background: #f4f6f9; color: #222; }
.container { width: 92%; max-width: 1100px; margin: 40px auto; }
h1 { color: #172554; }
.subtitle { color: #64748b; margin-bottom: 25px; }
.message { padding: 14px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
.success-message { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.order-card { background: white; padding: 24px; margin-bottom: 25px; border-radius: 12px; box-shadow: 0 3px 12px rgba(0,0,0,0.06); }
.order-header { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
.order-info { line-height: 1.9; margin-top: 15px; }
.status { padding: 7px 13px; border-radius: 20px; font-size: 13px; font-weight: bold; background: #e2e8f0; }
.status.Pending { background: #fef3c7; color: #92400e; }
.status.Cancelled { background: #fee2e2; color: #991b1b; }
.status.Processing { background: #dbeafe; color: #1e40af; }
.status.Completed, .status.Complete { background: #dcfce7; color: #166534; }
table { width: 100%; border-collapse: collapse; margin-top: 18px; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
th { background: #f8fafc; }
button { border: none; padding: 11px 18px; border-radius: 7px; cursor: pointer; color: white; font-weight: bold; margin-top: 12px; }
.cancel-btn { background: #f59e0b; }
.back-btn { display: inline-block; background: #172554; color: white; padding: 12px 20px; border-radius: 8px; text-decoration: none; margin-bottom: 20px; }
.empty { background: white; padding: 30px; border-radius: 10px; text-align: center; }
</style>
</head>
<body>

<div class="container">
    <a href="home.php" class="back-btn">← Back to Home</a>
    <h1>My Orders</h1>
    <p class="subtitle">View and manage your supermarket orders.</p>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <?php while ($order = mysqli_fetch_assoc($result)): 
            $raw_pay_status = $order["payment_status"] ?? "Pending";
            $is_completed = (strtolower(trim($raw_pay_status)) === 'completed' || strtolower(trim($raw_pay_status)) === 'complete');
        ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <h3>Order #<?php echo (int)$order["id"]; ?></h3>
                        <small><?php echo htmlspecialchars($order["created_at"]); ?></small>
                    </div>
                    <span class="status <?php echo htmlspecialchars($order["order_status"]); ?>">
                        <?php echo htmlspecialchars($order["order_status"]); ?>
                    </span>
                </div>

                <div class="order-info">
                    <strong>Customer:</strong> <?php echo htmlspecialchars($order["full_name"]); ?><br>
                    <strong>Delivery Address:</strong> <?php echo htmlspecialchars($order["delivery_address"]); ?><br>
                    <strong>Payment Method:</strong> <?php echo htmlspecialchars($order["method"] ?? "Not specified"); ?><br>
                    
                    <strong>Payment Status:</strong> 
                    <span style="font-weight: bold; color: <?php echo $is_completed ? '#166534' : '#92400e'; ?>">
                        <?php echo htmlspecialchars($raw_pay_status); ?>
                    </span><br>

                    <strong>Transaction Reference:</strong> <?php echo htmlspecialchars($order["transaction_reference"] ?? "N/A"); ?><br>
                    <strong>Total Amount:</strong> <?php echo number_format((float)$order["total_amount"], 2); ?> ETB
                </div>

                <!-- Payment Status: Completed Message -->
                <?php if ($is_completed): ?>
                    <div class="message success-message" style="margin-top: 15px;">
                        ✓ Payment Status: Completed — ክፍያዎ በአስተዳዳሪው ተረጋግጧል!
                    </div>
                <?php endif; ?>

                <h4>Order Items</h4>
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
                <?php mysqli_stmt_close($item_stmt); ?>

                <?php if (!$is_completed && in_array($order["order_status"], ["Pending", "Processing"], true)): ?>
                    <form action="../actions/cancel_order.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                        <input type="hidden" name="order_id" value="<?php echo (int)$order["id"]; ?>">
                        <button type="submit" class="cancel-btn">Cancel Order</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty">
            <h3>No Orders Found</h3>
            <p>You have not placed any orders yet.</p>
            <a href="products.php" class="back-btn">Start Shopping</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
<?php mysqli_stmt_close($stmt); ?>