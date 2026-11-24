<?php
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';

if (
    strpos($scriptPath, '/admin/') !== false ||
    strpos($scriptPath, '/customer/') !== false
) {
    $themeBase = '../';
} else {
    $themeBase = './';
}
?>

<script>
(function () {
    const theme = localStorage.getItem("supermart-theme") || "light";

    document.documentElement.setAttribute(
        "data-theme",
        theme === "dark" ? "dark" : "light"
    );
})();
</script>

<link rel="stylesheet"
      href="<?= htmlspecialchars($themeBase) ?>assets/css/theme.css">

<script src="<?= htmlspecialchars($themeBase) ?>assets/js/theme.js" defer></script>
