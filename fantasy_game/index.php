<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Index File
 * 
 * Punto di ingresso principale del gioco
 */

// Includi la configurazione
require_once 'config.php';

// Controlla se l'utente è loggato
$logged_in = is_logged_in();
$user_id = get_current_user_id();
$username = get_current_username();

// Verifica se l'utente è un amministratore
$is_admin = $logged_in ? is_admin() : false;

// Se l'utente è loggato, ottieni le sue risorse
$resources = $logged_in ? get_player_resources($user_id) : null;
$player_level = $logged_in ? get_player_level($user_id) : null;

// Se l'utente è loggato, controlla se ci sono edifici completati
if ($logged_in) {
    $completed_buildings = check_completed_buildings($user_id);
    
    // Aggiorna le risorse ogni volta che l'utente carica la pagina
    //update_player_resources($user_id); evita di aggiornare ogni volta le risorse
}

// Gestione del routing
$page = $_GET['page'] ?? '';

// File CSS e JS aggiuntivi per la pagina
$additional_css = [];
$additional_js = [];

// Se è specificata una pagina, carica il file corrispondente
if (!empty($page)) {
    $page_file = 'pages/' . $page . '.php';
    
    if (file_exists($page_file)) {
        // A seconda della pagina, aggiungi CSS e JS specifici
        switch ($page) {
            case 'admin':
                $additional_css[] = 'admin.css';
                $additional_js[] = 'admin.js';
                break;
            case 'profile':
                $additional_css[] = 'profile.css';
                break;
            case 'tech-tree':
                $additional_css[] = 'tech-tree.css';
                break;
        }
        
        // Includi l'header
        include 'templates/header.php';
        
        // Includi il file della pagina
        include $page_file;
        
        // Includi il footer
        include 'templates/footer.php';
        exit;
    }
}

// Se non è specificata una pagina valida, carica la pagina principale del gioco
include 'templates/header.php';
include 'templates/game.php';
include 'templates/footer.php';