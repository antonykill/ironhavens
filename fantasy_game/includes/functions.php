<?php
/**
 * Functions Main File
 * 
 * File principale che include tutte le funzioni del gioco
 */

// Includi i file delle funzioni
require_once 'db.php';
require_once 'auth.php';
require_once 'building.php';
require_once 'api.php';

/**
 * Genera un'intestazione HTTP per reindirizzare a un'altra pagina
 * @param string $url URL a cui reindirizzare
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Formatta una data in formato leggibile
 * @param string $date La data da formattare
 * @param string $format Il formato desiderato (default: 'd/m/Y H:i')
 * @return string La data formattata
 */
function format_date($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

/**
 * Formatta un numero con separatore di migliaia
 * @param int|float $number Il numero da formattare
 * @param int $decimals Il numero di decimali (default: 0)
 * @return string Il numero formattato
 */
function format_number($number, $decimals = 0) {
    return number_format($number, $decimals, ',', '.');
}

/**
 * Calcola il tempo rimanente fino a una data futura
 * @param string $end_time La data di fine
 * @return string Il tempo rimanente in formato leggibile
 */
function get_time_remaining($end_time) {
    $end = strtotime($end_time);
    $now = time();
    
    $diff = max(0, $end - $now); // secondi rimanenti
    
    if ($diff <= 0) {
        return "Completato!";
    }
    
    $hours = floor($diff / 3600);
    $diff %= 3600;
    $minutes = floor($diff / 60);
    $seconds = $diff % 60;
    
    if ($hours > 0) {
        return "{$hours}h {$minutes}m rimanenti";
    } elseif ($minutes > 0) {
        return "{$minutes}m {$seconds}s rimanenti";
    } else {
        return "{$seconds}s rimanenti";
    }
}

/**
 * Sanitizza l'input dell'utente per prevenire attacchi XSS
 * @param string $input L'input da sanitizzare
 * @return string L'input sanitizzato
 */
function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Genera un token CSRF per proteggere i form
 * @return string Il token CSRF
 */
function generate_csrf_token() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verifica un token CSRF
 * @param string $token Il token da verificare
 * @return bool True se il token è valido, altrimenti false
 */
function verify_csrf_token($token) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Registra un evento di gioco
 * @param int $user_id ID dell'utente
 * @param string $event_type Tipo di evento
 * @param string $event_description Descrizione dell'evento
 * @param int|null $related_entity_id ID dell'entità correlata (optional)
 * @param string|null $related_entity_type Tipo dell'entità correlata (optional)
 * @return bool True se l'evento è stato registrato con successo, altrimenti false
 */
function log_game_event($user_id, $event_type, $event_description, $related_entity_id = null, $related_entity_type = null) {
    try {
        $data = [
            'user_id' => $user_id,
            'event_type' => $event_type,
            'event_description' => $event_description
        ];
        
        if ($related_entity_id !== null) {
            $data['related_entity_id'] = $related_entity_id;
        }
        
        if ($related_entity_type !== null) {
            $data['related_entity_type'] = $related_entity_type;
        }
        
        return db_insert('game_events', $data) ? true : false;
    } catch (PDOException $e) {
        error_log("Errore nella registrazione dell'evento: " . $e->getMessage());
        return false;
    }
}

/**
 * Ottiene il valore di una costante di gioco
 * @param string $constant_name Nome della costante
 * @param mixed $default Valore predefinito se la costante non esiste
 * @return mixed Il valore della costante o il valore predefinito
 */
function get_game_constant($constant_name, $default = null) {
    try {
        $constant = db_fetch_row(
            "SELECT constant_value FROM game_constants WHERE constant_name = ?",
            [$constant_name]
        );
        
        return $constant ? $constant['constant_value'] : $default;
    } catch (PDOException $e) {
        error_log("Errore nel recupero della costante di gioco: " . $e->getMessage());
        return $default;
    }
}

/**
 * Imposta il valore di una costante di gioco
 * @param string $constant_name Nome della costante
 * @param mixed $value Nuovo valore
 * @return bool True se l'aggiornamento ha avuto successo, altrimenti false
 */
function set_game_constant($constant_name, $value) {
    try {
        // Verifica se la costante esiste
        $exists = db_fetch_row(
            "SELECT COUNT(*) as count FROM game_constants WHERE constant_name = ?",
            [$constant_name]
        );
        
        if ($exists && $exists['count'] > 0) {
            // Aggiorna la costante esistente
            return db_update(
                'game_constants',
                ['constant_value' => $value],
                'constant_name = ?',
                [$constant_name]
            );
        } else {
            // Crea una nuova costante
            return db_insert('game_constants', [
                'constant_name' => $constant_name,
                'constant_value' => $value,
                'description' => 'Costante di gioco'
            ]) ? true : false;
        }
    } catch (PDOException $e) {
        error_log("Errore nell'impostazione della costante di gioco: " . $e->getMessage());
        return false;
    }
}

/**
 * Carica un template e lo popola con i dati
 * @param string $template_name Nome del template
 * @param array $data Dati da passare al template
 * @return string Il template renderizzato
 */
function load_template($template_name, $data = []) {
    // Estrai le variabili per renderle disponibili nel template
    extract($data);
    
    // Avvia l'output buffering
    ob_start();
    
    // Includi il template
    $template_path = __DIR__ . '/../templates/' . $template_name . '.php';
    
    if (file_exists($template_path)) {
        include $template_path;
    } else {
        echo "Errore: template '$template_name' non trovato.";
    }
    
    // Ottieni il contenuto del buffer e puliscilo
    $output = ob_get_clean();
    
    return $output;
}

/**
 * Ottiene il percorso assoluto di una risorsa
 * @param string $path Percorso relativo della risorsa
 * @return string Percorso assoluto
 */
function get_asset_path($path) {
    return BASE_URL . 'assets/' . $path;
}

/**
 * Controlla se l'utente corrente è loggato e ha i permessi necessari
 * @param string $permission Permesso richiesto (default: null)
 * @return bool True se l'utente ha i permessi necessari, altrimenti false
 */
function check_permission($permission = null) {
    if (!is_logged_in()) {
        return false;
    }
    
    if ($permission === 'admin') {
        return is_admin();
    }
    
    return true;
}