<?php
// config.php - Configurazioni generali del gioco

// Configurazioni di connessione al database
define('DB_HOST', 'localhost');          // Host del database
define('DB_NAME', 'fantasy_game');       // Nome del database
define('DB_USER', 'root');               // Username del database (cambia con il tuo)
define('DB_PASS', 'password');           // Password del database (cambia con la tua)

// Configurazioni del gioco
define('SITE_NAME', 'Fantasy Resource Game');
define('GAME_VERSION', '1.0.0');

// Impostazioni di sviluppo
define('DEBUG_MODE', false);              // Mostra errori dettagliati quando è impostato su true

// Impostazioni di gioco
define('RESOURCE_UPDATE_INTERVAL', 60);   // Intervallo in secondi per l'aggiornamento automatico delle risorse
define('MAX_RESOURCE_CAPACITY', 10000);   // Capacità massima di risorse per un giocatore senza edifici speciali
define('XP_PER_BUILDING', 20);            // Esperienza guadagnata per ogni edificio costruito
define('BASE_XP_PER_LEVEL', 100);         // XP di base necessaria per salire di livello

// Impostazioni di sicurezza
define('SESSION_LIFETIME', 86400);        // Durata della sessione in secondi (24 ore)
define('PASSWORD_MIN_LENGTH', 6);         // Lunghezza minima per le password
define('SALT_PREFIX', 'fantasy_game_');   // Prefisso per il salt delle password (cambia per maggiore sicurezza)

// Impostazioni percorsi
define('BASE_URL', '/fantasy_game/');     // URL base del gioco (cambia in base alla tua configurazione)
define('IMAGES_PATH', 'images/');         // Percorso delle immagini
define('BUILDINGS_IMG_PATH', 'images/buildings/'); // Percorso delle immagini degli edifici

// Configurazione timezone
date_default_timezone_set('Europe/Rome'); // Cambia in base alla tua zona

// Funzione per mostrare errori solo in modalità debug
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Configurazione della sessione
ini_set('session.cookie_httponly', 1);   // Previene l'accesso al cookie da JavaScript
ini_set('session.use_only_cookies', 1);  // Forza l'utilizzo di cookie di sessione
ini_set('session.cookie_secure', 0);     // Imposta a 1 se utilizzi HTTPS
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
session_set_cookie_params(SESSION_LIFETIME);

// Controlla se la connessione al database funziona
function test_db_connection() {
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return true;
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            echo 'Errore di connessione al database: ' . $e->getMessage();
        }
        return false;
    }
}