<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo get_asset_path('css/style.css'); ?>">
    <?php if (isset($additional_css) && is_array($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?php echo get_asset_path('css/' . $css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body>
    <div class="game-container">
        <header>
            <h1><?php echo SITE_NAME; ?></h1>
            
            <div class="user-info">
                <?php if ($logged_in): ?>
                    <span class="welcome">Benvenuto, <strong><?php echo sanitize_input($username); ?></strong>!</span>
                    <span class="level">Livello: <?php echo $player_level['current_level']; ?></span>
                    <a href="<?php echo BASE_URL; ?>?page=profile" class="profile-link"><i class="fas fa-user"></i> Profilo</a>
                    <?php if ($is_admin): ?>
                        <a href="<?php echo BASE_URL; ?>?page=admin" class="admin-link"><i class="fas fa-crown"></i> Admin</a>
                    <?php endif; ?>
                    <a href="#" class="logout-btn" id="logout-btn">Logout</a>
                <?php else: ?>
                    <a href="#" class="login-btn" id="login-btn">Login</a>
                    <a href="#" class="register-btn" id="register-btn">Registrati</a>
                <?php endif; ?>
            </div>
        </header>
        
        <?php if (isset($_SESSION['admin_error'])): ?>
        <div class="admin-error-message">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['admin_error']; ?>
            <?php unset($_SESSION['admin_error']); ?>
        </div>
        <?php endif; ?>