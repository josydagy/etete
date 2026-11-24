<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - SIMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 20px;
        }

        .card {
            max-width: 450px;
            margin: 50px auto;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            padding: 30px;
            border: 1px solid #e1e8ed;
        }

        .card-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .card-header i {
            font-size: 40px;
            color: #0d6efd;
            margin-bottom: 8px;
        }

        .card-header h2 {
            margin: 5px 0;
            font-size: 24px;
            color: #222;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 14px;
            color: #444;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-control:focus {
            border-color: #0d6efd;
            outline: none;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
        }

        .btn {
            width: 100%;
            padding: 11px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            background-color: #0d6efd;
            color: white;
            transition: background-color 0.2s ease;
        }

        .btn:hover {
            background-color: #0b5ed7;
        }

        .back-link {
            text-align: center;
            margin-top: 15px;
        }

        .back-link a {
            color: #6c757d;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link a:hover {
            color: #0d6efd;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<?php
include 'db.php'; 

if (isset($_POST['reset_password'])) {
    $email            = mysqli_real_escape_string($conn, $_POST['email']);
    $new_password     = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Check if passwords match
    if ($new_password !== $confirm_password) {
        echo "<script>alert('New passwords do not match!'); window.history.back();</script>";
        exit();
    }

    // 2. Check if email exists in database
    $check_email = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
    $result      = mysqli_query($conn, $check_email);
    
    if (mysqli_num_rows($result) > 0) {
        // 3. Hash the new password and update
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $update_query = "UPDATE users SET password = '$hashed_password' WHERE email = '$email'";
        $update_run   = mysqli_query($conn, $update_query);

        if ($update_run) {
            echo "<script>alert('Password updated successfully! You can now login.'); window.location.href='index.php';</script>";
        } else {
            echo "<script>alert('Error updating password: " . mysqli_error($conn) . "');</script>";
        }
    } else {
        echo "<script>alert('Email address not found in our system!'); window.history.back();</script>";
    }
}
?>

<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-key"></i>
        <h2>Reset Password</h2>
    </div>

    <form action="" method="POST">
        <div class="form-group">
            <label for="email">Enter your registered Email</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="name@example.com" required>
        </div>
        
        <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="New Password" required>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm New Password" required>
        </div>
        
        <button type="submit" name="reset_password" class="btn">Update Password</button>
        
        <div class="back-link">
            <a href="index.php"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
        </div>
    </form>
</div>

</body>
</html>