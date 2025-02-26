<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Set cache control headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Dinners</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
</head>
<body>
    <header>
        <div class="container">
            <h1 class="site-title"><a href="index.php">Community Dinners</a></h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="archive.php">Archives</a></li>
                    <?php if (isAdmin()): ?>
                    <li><a href="admin.php">Admin</a></li>
                    <?php endif; ?>
                    <?php if (isLoggedIn()): ?>
                    <li>
                        <span class="user-name">Welcome, <?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                        <a href="login.php?logout=1" class="logout-link">Logout</a>
                    </li>
                    <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <div class="container">
        <div id="update-status" class="update-status">Up to date</div>