<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Supermarket</title>

    <?php require_once __DIR__ . "/includes/theme_head.php"; ?>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .header {
            padding: 20px 6%;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
        }

        .container {
            max-width: 750px;
            margin: 50px auto;
            padding: 25px;
        }

        .settings-card {
            background: var(--surface);
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 30px;
        }

        .subtitle {
            color: var(--muted);
            line-height: 1.6;
        }

        .options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 25px;
        }

        .theme-option {
            min-height: 130px;
            text-align: left;
        }

        .theme-option strong {
            display: block;
            margin-bottom: 8px;
        }

        .icon {
            font-size: 30px;
            display: block;
            margin-bottom: 12px;
        }

        .back {
            display: inline-block;
            margin-top: 25px;
            color: #27ae60;
            text-decoration: none;
        }

        @media(max-width: 600px) {
            .options {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div class="header">
    <h2 style="color:#27ae60;">SUPER MARKET</h2>
</div>

<div class="container">
    <div class="settings-card">

        <h1>⚙️ Settings</h1>

        <p class="subtitle">
            Customize your Supermarket Management System appearance.
        </p>

        <h3>Appearance</h3>
        <p class="subtitle">Choose your preferred theme.</p>

        <div class="options">

            <button type="button"
                    class="theme-option"
                    data-theme-choice="light">

                <span class="icon">☀️</span>
                <strong>Light Mode</strong>
                <small>Bright background</small>
                <span class="check">✓ Selected</span>
            </button>

            <button type="button"
                    class="theme-option"
                    data-theme-choice="dark">

                <span class="icon">🌙</span>
                <strong>Dark Mode</strong>
                <small>Dark background</small>
                <span class="check">✓ Selected</span>
            </button>

        </div>

        <p class="subtitle">
            Current Theme:
            <strong data-current-theme>Light Mode</strong>
        </p>

        <?php
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $back = 'admin/dashboard.php';
        } elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'customer') {
            $back = 'customer/home.php';
        } else {
            $back = 'index.php';
        }
        ?>

        <a class="back" href="<?= htmlspecialchars($back) ?>">
            ← Back to Project
        </a>

    </div>
</div>

</body>
</html>
