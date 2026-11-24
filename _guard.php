<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../db.php';
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'storekeeper') { header('Location: ../login.php'); exit; }
