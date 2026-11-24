
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];
$role = $_SESSION["role"] ?? "";

if (!in_array($role, ["admin", "customer"], true)) {
    die("Access denied.");
}

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $current_password = $_POST["current_password"] ?? "";
    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    // Get the logged-in user's password only
    $sql = "SELECT password FROM users WHERE id = ? AND role = ?";
    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "is", $user_id, $role);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$user || !password_verify($current_password, $user["password"])) {

        $message = "Current password is incorrect!";
        $message_type = "error";

    } elseif ($new_password !== $confirm_password) {

        $message = "New passwords do not match!";
        $message_type = "error";

    } elseif ($role === "admin" && (
        strlen($new_password) < 12 ||
        !preg_match('/[A-Z]/', $new_password) ||
        !preg_match('/[a-z]/', $new_password) ||
        !preg_match('/[0-9]/', $new_password) ||
        !preg_match('/[^A-Za-z0-9]/', $new_password)
    )) {

        $message = "Admin password must contain at least 12 characters, uppercase, lowercase, number and special character!";
        $message_type = "error";

    } elseif ($role === "customer" && strlen($new_password) < 8) {

        $message = "Customer password must contain at least 8 characters!";
        $message_type = "error";

    } elseif (password_verify($new_password, $user["password"])) {

        $message = "New password must be different from current password!";
        $message_type = "error";

    } else {

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $update = mysqli_prepare(
            $conn,
            "UPDATE users SET password = ? WHERE id = ? AND role = ?"
        );

        mysqli_stmt_bind_param(
            $update,
            "sis",
            $hashed_password,
            $user_id,
            $role
        );

        if (mysqli_stmt_execute($update)) {
            $message = "Password changed successfully!";
            $message_type = "success";
        } else {
            $message = "Unable to change password. Please try again.";
            $message_type = "error";
        }

        mysqli_stmt_close($update);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Change Password</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

.container {
    background: white;
    width: 92%;
    max-width: 460px;
    padding: 35px;
    border-radius: 15px;
    box-shadow: 0 5px 25px rgba(0,0,0,.08);
}

h2 {
    color: #172554;
    text-align: center;
}

.subtitle {
    text-align: center;
    color: #64748b;
    margin-bottom: 25px;
}

label {
    display: block;
    margin: 15px 0 7px;
    font-weight: bold;
}

input {
    width: 100%;
    padding: 13px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
}

button {
    width: 100%;
    padding: 14px;
    margin-top: 22px;
    border: none;
    border-radius: 8px;
    background: #172554;
    color: white;
    font-weight: bold;
    cursor: pointer;
}

.message {
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.success {
    background: #dcfce7;
    color: #166534;
}

.error {
    background: #fee2e2;
    color: #991b1b;
}

.requirements {
    font-size: 13px;
    color: #64748b;
    margin-top: 12px;
    line-height: 1.7;
}

.back {
    display: block;
    text-align: center;
    margin-top: 20px;
    color: #172554;
    text-decoration: none;
}
</style>
</head>

<body>

<div class="container">

    <h2>Change Password</h2>

    <p class="subtitle">
        <?php echo $role === "admin" ? "Admin Account Security" : "Customer Account Security"; ?>
    </p>

    <?php if ($message !== ""): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label>Current Password</label>
        <input type="password" name="current_password" required>

        <label>New Password</label>
        <input type="password" name="new_password" required>

        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" required>

        <?php if ($role === "admin"): ?>
            <div class="requirements">
                Admin password requirements:
                <br>• At least 12 characters
                <br>• One uppercase letter (A-Z)
                <br>• One lowercase letter (a-z)
                <br>• One number (0-9)
                <br>• One special character (@, #, !, etc.)
            </div>
        <?php else: ?>
            <div class="requirements">
                Customer password must contain at least 8 characters.
            </div>
        <?php endif; ?>

        <button type="submit">Change Password</button>

    </form>

    <a class="back"
       href="<?php echo $role === 'admin' ? 'admin/dashboard.php' : 'customer/home.php'; ?>">
       ← Back to Dashboard
    </a>

</div>

</body>
</html>
