
<?php
require_once "db.php";

$message = "";

$setup_key = "ChangeThisSecret123";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $key = $_POST["setup_key"];
    $full_name = trim($_POST["full_name"]);
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if ($key !== $setup_key) {
        $message = "Invalid setup key!";
    } else {
        $check = mysqli_query($conn, "SELECT id FROM users WHERE role='admin'");

        if (mysqli_num_rows($check) > 0) {
            $message = "Admin account already exists!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO users (full_name, username, email, password, role)
                 VALUES (?, ?, ?, ?, 'admin')"
            );

            mysqli_stmt_bind_param(
                $stmt, "ssss",
                $full_name, $username, $email, $hashed_password
            );

            if (mysqli_stmt_execute($stmt)) {
                $message = "Admin account created successfully!";
            } else {
                $message = "Error: " . mysqli_error($conn);
            }
        }
    }
}
?>

<h2>Create Admin Account</h2>

<p><?php echo htmlspecialchars($message); ?></p>

<form method="POST">
    <input type="password" name="setup_key"
           placeholder="Setup key" required><br><br>

    <input type="text" name="full_name"
           placeholder="Full name" required><br><br>

    <input type="text" name="username"
           placeholder="Admin username" required><br><br>

    <input type="email" name="email"
           placeholder="Admin email" required><br><br>

    <input type="password" name="password"
           placeholder="Admin password" required><br><br>

    <button type="submit">Create Admin</button>
</form>
