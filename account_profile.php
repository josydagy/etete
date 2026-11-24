<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../db.php';
$requiredRole = $requiredRole ?? '';
$userId = (int)($_SESSION['user_id'] ?? 0);
if (!$userId || ($_SESSION['role'] ?? '') !== $requiredRole) { header('Location: ../login.php'); exit; }
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$message = ''; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Security check failed. Refresh the page and try again.';
    } else {
        $name = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $photoName = null;
        if ($name === '' || $username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Enter a valid full name, username, and email.';
        if (!$error) {
            $check = $conn->prepare('SELECT id FROM users WHERE (username=? OR email=?) AND id<>? LIMIT 1');
            $check->bind_param('ssi', $username, $email, $userId); $check->execute();
            if ($check->get_result()->num_rows) $error = 'Username or email is already used by another account.';
            $check->close();
        }
        if (!$error && $new !== '') {
            $get = $conn->prepare('SELECT password FROM users WHERE id=?'); $get->bind_param('i', $userId); $get->execute();
            $row = $get->get_result()->fetch_assoc(); $get->close();
            if (!$row || !password_verify($current, $row['password'])) $error = 'Current password is incorrect.';
            elseif (strlen($new) < 8) $error = 'New password must contain at least 8 characters.';
        }
        if (!$error && isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK || $_FILES['profile_photo']['size'] > 2*1024*1024) $error = 'Profile photo must be under 2 MB.';
            else {
                $finfo = new finfo(FILEINFO_MIME_TYPE); $mime = $finfo->file($_FILES['profile_photo']['tmp_name']);
                $exts = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
                if (!isset($exts[$mime])) $error = 'Only JPG, PNG, or WEBP profile photos are allowed.';
                else {
                    $dir = __DIR__ . '/../uploads/profiles';
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    $photoName = 'user_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $exts[$mime];
                    if (!move_uploaded_file($_FILES['profile_photo']['tmp_name'], $dir . '/' . $photoName)) $error = 'Could not save the uploaded photo.';
                }
            }
        }
        if (!$error) {
            $hash = $new !== '' ? password_hash($new, PASSWORD_DEFAULT) : null;
            if ($photoName !== null && $new !== '') {
                $stmt=$conn->prepare('UPDATE users SET full_name=?,username=?,email=?,password=?,profile_photo=? WHERE id=?');
                $stmt->bind_param('sssssi',$name,$username,$email,$hash,$photoName,$userId);
            } elseif ($photoName !== null) {
                $stmt=$conn->prepare('UPDATE users SET full_name=?,username=?,email=?,profile_photo=? WHERE id=?');
                $stmt->bind_param('ssssi',$name,$username,$email,$photoName,$userId);
            } elseif ($new !== '') {
                $stmt=$conn->prepare('UPDATE users SET full_name=?,username=?,email=?,password=? WHERE id=?');
                $stmt->bind_param('ssssi',$name,$username,$email,$hash,$userId);
            } else {
                $stmt=$conn->prepare('UPDATE users SET full_name=?,username=?,email=? WHERE id=?');
                $stmt->bind_param('sssi',$name,$username,$email,$userId);
            }
            if ($stmt->execute()) { $_SESSION['full_name']=$name; $_SESSION['username']=$username; $_SESSION['email']=$email; $message='Your account has been updated successfully.'; }
            else $error='Could not update your account. Please try again.';
            $stmt->close();
        }
    }
}
$stmt=$conn->prepare('SELECT full_name,username,email,role,profile_photo FROM users WHERE id=? AND is_active=1');
$stmt->bind_param('i',$userId); $stmt->execute(); $account=$stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$account) { session_destroy(); header('Location: ../login.php?disabled=1'); exit; }
$back = $requiredRole === 'customer' ? 'home.php' : 'dashboard.php';
$photo = !empty($account['profile_photo']) ? '../uploads/profiles/' . rawurlencode($account['profile_photo']) : '';
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>My Account | FreshMart</title><link rel="stylesheet" href="../assets/css/modern.css"><style>.profile-wrap{max-width:780px;margin:32px auto;padding:0 16px}.profile-card{background:#fff;border:1px solid #e3ebe6;border-radius:18px;padding:26px;box-shadow:0 12px 35px #1537250d}.profile-card label{display:block;font-weight:700;margin:15px 0 7px}.profile-card input{width:100%;padding:12px;border:1px solid #d7e2da;border-radius:9px}.profile-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 18px}.avatar{width:92px;height:92px;border-radius:50%;object-fit:cover;background:#eaf3ed;display:block;margin:8px 0 14px}.muted{color:#718078}@media(max-width:600px){.profile-grid{grid-template-columns:1fr}}</style></head><body><main class="profile-wrap"><header class="topbar"><div><a class="brand" href="<?=htmlspecialchars($back)?>">Fresh<span>Mart</span></a><h1>My Account</h1><p class="muted">Update your account details and password.</p></div><a class="btn" href="<?=htmlspecialchars($back)?>">Back to dashboard</a></header><section class="profile-card"><h2>Personal information</h2><p class="muted">Account type: <strong><?=htmlspecialchars(ucfirst($account['role']))?></strong>. Your role is controlled by the system administrator.</p><?php if($message):?><div class="alert success"><?=htmlspecialchars($message)?></div><?php endif;?><?php if($error):?><div class="alert error"><?=htmlspecialchars($error)?></div><?php endif;?><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'])?>"><div><label>Profile photo (JPG, PNG, WEBP; max 2 MB)</label><?php if($photo):?><img class="avatar" src="<?=htmlspecialchars($photo)?>" alt="Profile photo"><?php else:?><div class="avatar" style="display:grid;place-items:center;font-size:32px">👤</div><?php endif;?><input type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp"></div><div class="profile-grid"><div><label>Full name</label><input name="full_name" value="<?=htmlspecialchars($account['full_name'])?>" required maxlength="120"></div><div><label>Username</label><input name="username" value="<?=htmlspecialchars($account['username'])?>" required maxlength="80"></div><div><label>Email</label><input type="email" name="email" value="<?=htmlspecialchars($account['email'])?>" required maxlength="190"></div></div><hr style="margin:24px 0;border:0;border-top:1px solid #e5e7eb"><h3>Change password</h3><p class="muted">Leave both password fields empty to keep your current password.</p><label>Current password</label><input type="password" name="current_password" autocomplete="current-password"><label>New password (minimum 8 characters)</label><input type="password" name="new_password" minlength="8" autocomplete="new-password"><button class="btn primary" type="submit" style="margin-top:20px">Save changes</button></form></section></main></body></html>
