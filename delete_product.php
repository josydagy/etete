<?php
session_start();
require_once __DIR__ . "/../db.php";

// Admin access check
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["product_id"])) {
    $product_id = (int)$_POST["product_id"];

    // ምርቱን ከዳታቤዝ ውስጥ ሙሉ በሙሉ መሰረዝ
    $sql = "DELETE FROM products WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header("Location: ../admin/products.php?deleted=1");
    exit();
} else {
    header("Location: ../admin/products.php");
    exit();
}
?>