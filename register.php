<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User - ETETE</title>
    <!-- FontAwesome icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 20px;
        }

        .card {
            max-width: 500px;
            margin: 30px auto;
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

        .card-header i.user-icon {
            font-size: 40px;
            color: #333;
            margin-bottom: 8px;
        }

        .card-header h2 {
            margin: 5px 0;
            font-size: 24px;
            color: #222;
        }

        .badge-sims {
            background-color: #0d6efd;
            color: white;
            padding: 3px 8px;
            font-size: 12px;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
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
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-control:focus {
            border-color: #0d6efd;
            outline: none;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
        }

        .btn-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 25px;
        }
        

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background-color 0.2s ease;
        }

        .btn-cancel {
            background-color: #0d6efd;
            color: white;
        }

        .btn-cancel:hover {
            background-color: #0b5ed7;
        }

        .btn-create {
            background-color: #198754;
            color: white;
        }

        .btn-create:hover {
            background-color: #157347;
        }

        @media (max-width: 576px) {
            .card {
                padding: 20px;
                margin: 10px auto;
            }
            .btn-container {
                flex-direction: column;
                gap: 10px;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<?php
include 'db.php'; 

if (isset($_POST['create_user'])) {
    $username         = mysqli_real_escape_string($conn, $_POST['username']);
    $email            = mysqli_real_escape_string($conn, $_POST['email']);
    $gender           = mysqli_real_escape_string($conn, $_POST['gender']);
    $role             = mysqli_real_escape_string($conn, $_POST['role']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Check if passwords match
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit();
    }

    // 2. Check if username or email already exists
    $check_user = "SELECT * FROM users WHERE username = '$username' OR email = '$email' LIMIT 1";
    $result     = mysqli_query($conn, $check_user);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        if ($row['username'] === $username) {
            echo "<script>alert('Username ($username) is already taken! Please choose another one.'); window.history.back();</script>";
        } else if ($row['email'] === $email) {
            echo "<script>alert('This Email address is already registered! Please use another email.'); window.history.back();</script>";
        }
    } else {
        // 3. Insert into database
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO users (username, email, gender, role, password) 
                  VALUES ('$username', '$email', '$gender', '$role', '$hashed_password')";
        
        $query_run = mysqli_query($conn, $query);

        if ($query_run) {
            echo "<script>alert('User registered successfully!'); window.location.href='index.php';</script>";
        } else {
            echo "<script>alert('Error occurred: " . mysqli_error($conn) . "');</script>";
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-user-gear user-icon"></i>
        <h2>Create User</h2>
        <span class="badge-sims">Etete SuperMarket</span>
    </div>

    <form action="" method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" class="form-control" placeholder="Username" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="name@example.com" required>
        </div>
        
        <div class="form-group">
            <label for="gender">Gender</label>
            <select id="gender" name="gender" class="form-control" required>
                <option value="male">male</option>
                <option value="female">female</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="role">Role</label>
            <select id="role" name="role" class="form-control" required>
                <option value="customer">customer</option>
                
            </select>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Retype Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Retype Password" required>
        </div>
        
        <div class="btn-container">
            <a href="index.php" class="btn btn-cancel">
                <i class="fa-solid fa-chevron-left"></i> Cancel
            </a>
            <button type="submit" name="create_user" class="btn btn-create">
                <i class="fa-solid fa-check"></i> Create
            </button>
        </div>
    </form>
</div>

</body>
</html>