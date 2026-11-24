<?php
// Product editing is handled from the Admin Products page.
header('Location: admin/products.php' . (isset($_GET['id']) ? '?edit=' . (int)$_GET['id'] : ''));
exit;
