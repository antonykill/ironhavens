<?php
// File logout.php nella directory principale
require_once 'config.php';
require_once 'includes/auth.php';

// Esegui il logout
logout_user();

// Reindirizza alla home
header('Location: index.php');
exit;