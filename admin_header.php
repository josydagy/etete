<?php
// Optional reusable admin page header. Set $pageTitle before including.
$pageTitle = $pageTitle ?? 'Admin Workspace';
?>
<header class="topbar"><div><a class="brand" href="dashboard.php">Fresh<span>Mart</span></a><h1><?=htmlspecialchars($pageTitle)?></h1></div><nav class="actions"><a class="btn" href="staff.php">Staff Management</a><a class="btn" href="profile.php">My Account</a><a class="btn" href="../logout.php">Sign out</a></nav></header>
