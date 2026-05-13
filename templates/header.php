<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <?php do_action('wp_head'); ?>
</head>
<body>
    <!-- HEADER -->
    <header class="header">
        <div class="header-content">
            <!-- Logo/Naziv sajta -->
            <a href="<?php echo SITE_URL; ?>" class="logo">
                api.cisivaz.com
            </a>

            <!-- Desktop navigacija (skrivena na mobilnom) -->
            <nav class="desktop-nav">
                <?php if (is_user_logged_in()): ?>
                    <span class="user-greeting">
                        Logged in as: <?php echo esc_html($_SESSION['username']); ?>
                    </span>
                    <?php do_action('desktop_menu_items'); ?>
                    <a href="?action=logout" class="btn btn-danger">Logout</a>
                <?php else: ?>
                    <a href="?action=login" class="btn btn-primary">Login</a>
                <?php endif; ?>
            </nav>

            <button class="menu-btn" id="hamburger-btn" aria-label="Open menu">Menu</button>
        </div>
    </header>

    <!-- FULLSCREEN MOBILNI MENI -->
    <div class="mobile-menu" id="mobile-menu">
        <div class="mobile-menu-header">
            <a href="<?php echo SITE_URL; ?>" class="logo">api.cisivaz.com</a>
            <button class="close-btn" id="close-menu-btn" aria-label="Close menu">Close</button>
        </div>
         <nav class="mobile-nav">
            <?php if (is_user_logged_in()): ?>
                <div class="mobile-user-info">
                    <span class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></span>
                    <span class="user-greeting">Logged in as: <?php echo esc_html($_SESSION['username']); ?></span>
                </div>
                <?php do_action('mobile_menu_items'); ?>
                <a href="?action=logout" class="btn btn-danger mobile-btn-full">Logout</a>
            <?php else: ?>
                <a href="?action=login" class="btn btn-primary mobile-btn-full">Login</a>
            <?php endif; ?>
        </nav>
    </div>

    <div class="container">