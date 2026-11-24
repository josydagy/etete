<?php
session_start();
require_once __DIR__ . "/../db.php";

// Admin security
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

$message = "";
$error = "";

// Delete customer
if (isset($_POST["delete_customer"])) {
    $customerId = (int) $_POST["customer_id"];

    // Do not allow admin to delete own account
    if ($customerId === (int)$_SESSION["user_id"]) {
        $error = "You cannot delete your own admin account.";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "DELETE FROM users WHERE id = ? AND role = 'customer'"
        );

        mysqli_stmt_bind_param($stmt, "i", $customerId);

        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $message = "Customer deleted successfully.";
            } else {
                $error = "Customer not found.";
            }
        } else {
            $error = "Cannot delete this customer because related records exist.";
        }

        mysqli_stmt_close($stmt);
    }
}

// Search customers
$search = trim($_GET["search"] ?? "");

if ($search !== "") {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, full_name, username, email
         FROM users
         WHERE role = 'customer'
         AND (full_name LIKE ? OR username LIKE ? OR email LIKE ?)
         ORDER BY id DESC"
    );

    $searchValue = "%" . $search . "%";

    mysqli_stmt_bind_param(
        $stmt,
        "sss",
        $searchValue,
        $searchValue,
        $searchValue
    );

    mysqli_stmt_execute($stmt);
    $customers = mysqli_stmt_get_result($stmt);
} else {
    $customers = mysqli_query(
        $conn,
        "SELECT id, full_name, username, email
         FROM users
         WHERE role = 'customer'
         ORDER BY id DESC"
    );
}

// Total customers
$totalResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM users WHERE role = 'customer'"
);

$totalCustomers = mysqli_fetch_assoc($totalResult)["total"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Customers</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background: #f1f5f9;
    color: #1e293b;
}

.container {
    max-width: 1150px;
    margin: 35px auto;
    padding: 20px;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
}

.header h1 {
    font-size: 27px;
}

.back {
    background: #1e293b;
    color: white;
    text-decoration: none;
    padding: 11px 17px;
    border-radius: 7px;
}

.summary {
    background: white;
    padding: 23px;
    border-radius: 12px;
    margin-bottom: 22px;
    box-shadow: 0 3px 12px rgba(0,0,0,.05);
}

.summary h2 {
    color: #2563eb;
    font-size: 30px;
    margin-top: 8px;
}

.panel {
    background: white;
    padding: 22px;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,.05);
}

.panel h2 {
    margin-bottom: 18px;
}

.message {
    padding: 13px;
    background: #dcfce7;
    color: #166534;
    border-radius: 7px;
    margin-bottom: 15px;
}

.error {
    padding: 13px;
    background: #fee2e2;
    color: #991b1b;
    border-radius: 7px;
    margin-bottom: 15px;
}

.search {
    display: flex;
    gap: 9px;
    margin-bottom: 20px;
}

.search input {
    flex: 1;
    min-width: 0;
    padding: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
}

button {
    border: none;
    cursor: pointer;
    border-radius: 7px;
    padding: 10px 14px;
    font-weight: bold;
}

.search button {
    background: #2563eb;
    color: white;
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 650px;
}

th, td {
    padding: 15px 12px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
}

th {
    background: #f8fafc;
}

.delete {
    background: #dc2626;
    color: white;
}

.empty {
    text-align: center;
    padding: 25px;
    color: #64748b;
}

@media(max-width: 600px) {
    .container {
        padding: 12px;
    }

    .panel {
        padding: 14px;
    }
}
</style>
</head>

<body>

<div class="container">

    <div class="header">
        <h1>Customer Management</h1>
        <a href="dashboard.php" class="back">← Dashboard</a>
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

    <div class="summary">
        <p>Total Registered Customers</p>
        <h2><?php echo (int)$totalCustomers; ?></h2>
    </div>

    <div class="panel">

        <h2>All Customers</h2>

        <form method="GET" class="search">
            <input
                type="text"
                name="search"
                placeholder="Search by name, username or email..."
                value="<?php echo htmlspecialchars($search); ?>"
            >

            <button type="submit">Search</button>
        </form>

        <div class="table-wrapper">
            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($customers && mysqli_num_rows($customers) > 0): ?>

                    <?php while ($row = mysqli_fetch_assoc($customers)): ?>

                        <tr>
                            <td><?php echo (int)$row["id"]; ?></td>

                            <td>
                                <?php echo htmlspecialchars($row["full_name"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row["username"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row["email"]); ?>
                            </td>

                            <td>
                                <form method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this customer?');">

                                    <input type="hidden"
                                           name="customer_id"
                                           value="<?php echo (int)$row["id"]; ?>">

                                    <button type="submit"
                                            name="delete_customer"
                                            class="delete">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5" class="empty">
                            No customers found.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>
        </div>

    </div>

</div>

</body>
</html>
