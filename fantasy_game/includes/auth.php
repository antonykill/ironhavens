<?php
/**
 * Authentication Functions
 * 
 * Funzioni per l'autenticazione e la gestione degli utenti
 */

/**
 * Registra un nuovo utente
 * @param string $username Username dell'utente
 * @param string $email Email dell'utente
 * @param string $password Password dell'utente (in chiaro)
 * @return array Risultato dell'operazione con success=true/false e message
 */
function register_user($username, $email, $password) {
    try {
        // Verifica se l'utente o l'email esistono già
        $user_exists = db_fetch_row(
            "SELECT COUNT(*) as count FROM users WHERE username = ? OR email = ?", 
            [$username, $email]
        );
        
        if ($user_exists && $user_exists['count'] > 0) {
            return ['success' => false, 'message' => 'Username o email già in uso'];
        }
        
        // Hash della password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Inizia una transazione
        $pdo = db_transaction_begin();
        
        // Inserisci il nuovo utente
        $user_id = db_insert('users', [
            'username' => $username,
            'email' => $email,
            'password_hash' => $password_hash
        ]);
        
        if (!$user_id) {
            db_transaction_rollback($pdo);
            return ['success' => false, 'message' => 'Errore durante la registrazione'];
        }
        
        // Crea record per livello
        $level_inserted = db_insert('player_levels', [
            'user_id' => $user_id
        ]);
        
        if (!$level_inserted) {
            db_transaction_rollback($pdo);
            return ['success' => false, 'message' => 'Errore durante la creazione del livello'];
        }
        
        // Crea record per risorse
        $resources_inserted = db_insert('player_resources', [
            'user_id' => $user_id
        ]);
        
        if (!$resources_inserted) {
            db_transaction_rollback($pdo);
            return ['success' => false, 'message' => 'Errore durante la creazione delle risorse'];
        }
        
        // Registra l'evento
        db_insert('game_events', [
            'user_id' => $user_id,
            'event_type' => 'REGISTRATION',
            'event_description' => 'Nuovo giocatore registrato'
        ]);
        
        // Commit della transazione
        db_transaction_commit($pdo);
        
        return [
            'success' => true, 
            'user_id' => $user_id, 
            'message' => 'Registrazione completata con successo'
        ];
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            db_transaction_rollback($pdo);
        }
        error_log("Errore durante la registrazione: " . $e->getMessage());
        return ['success' => false, 'message' => 'Errore durante la registrazione: ' . $e->getMessage()];
    }
}

/**
 * Autentica un utente
 * @param string $username_or_email Username o email dell'utente
 * @param string $password Password dell'utente (in chiaro)
 * @return array Risultato dell'operazione con success=true/false e message
 */
function login_user($username_or_email, $password) {
    try {
        // Cerca l'utente
        $user = db_fetch_row(
            "SELECT user_id, username, password_hash FROM users WHERE username = ? OR email = ?",
            [$username_or_email, $username_or_email]
        );
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Credenziali non valide'];
        }
        
        // Aggiorna last_login
        db_update(
            'users', 
            ['last_login' => date('Y-m-d H:i:s')], 
            'user_id = ?', 
            [$user['user_id']]
        );
        
        // Inizia la sessione
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['logged_in'] = true;
        
        // Registra l'evento
        db_insert('game_events', [
            'user_id' => $user['user_id'],
            'event_type' => 'LOGIN',
            'event_description' => 'Accesso effettuato'
        ]);
        
        return [
            'success' => true, 
            'user_id' => $user['user_id'], 
            'message' => 'Login effettuato con successo'
        ];
    } catch (PDOException $e) {
        error_log("Errore durante il login: " . $e->getMessage());
        return ['success' => false, 'message' => 'Errore durante il login: ' . $e->getMessage()];
    }
}

/**
 * Termina la sessione dell'utente
 * @return array Risultato dell'operazione con success=true/false e message
 */
function logout_user() {
    // Assicurati che la sessione sia avviata
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Recupera l'ID utente prima di distruggere la sessione
    $user_id = $_SESSION['user_id'] ?? null;
    
    // Distruggi la sessione
    $_SESSION = [];
    
    // Se il cookie di sessione è utilizzato
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    session_destroy();
    
    // Registra l'evento se l'ID utente è disponibile
    if ($user_id) {
        try {
            db_insert('game_events', [
                'user_id' => $user_id,
                'event_type' => 'LOGOUT',
                'event_description' => 'Logout effettuato'
            ]);
        } catch (PDOException $e) {
            // Ignora errori durante il logout
            error_log("Errore durante la registrazione del logout: " . $e->getMessage());
        }
    }
    
    return ['success' => true, 'message' => 'Logout effettuato con successo'];
}

/**
 * Verifica se un utente è loggato
 * @return bool True se l'utente è loggato, altrimenti false
 */
function is_logged_in() {
    // Assicurati che la sessione sia avviata
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Ottiene l'ID dell'utente attualmente loggato
 * @return int|null L'ID dell'utente loggato o null se non loggato
 */
function get_current_user_id() {
    // Assicurati che la sessione sia avviata
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    return $_SESSION['user_id'] ?? null;
}

/**
 * Ottiene lo username dell'utente attualmente loggato
 * @return string|null Lo username dell'utente loggato o null se non loggato
 */
function get_current_username() {
    // Assicurati che la sessione sia avviata
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    return $_SESSION['username'] ?? null;
}

/**
 * Verifica se un utente è un amministratore
 * @param int $user_id ID dell'utente da controllare (se null, usa l'utente corrente)
 * @return bool True se l'utente è un amministratore, altrimenti false
 */
function is_admin($user_id = null) {
    if ($user_id === null) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    try {
        $admin = db_fetch_row(
            "SELECT is_admin FROM users WHERE user_id = ?",
            [$user_id]
        );
        
        return isset($admin['is_admin']) && $admin['is_admin'] == 1;
    } catch (PDOException $e) {
        error_log("Errore nella verifica dello stato di amministratore: " . $e->getMessage());
        return false;
    }
}

/**
 * Richiede che l'utente sia loggato, altrimenti reindirizza
 * @param string $redirect_url URL a cui reindirizzare se l'utente non è loggato
 * @return void
 */
function require_login($redirect_url = 'index.php') {
    if (!is_logged_in()) {
        header("Location: $redirect_url");
        exit;
    }
}

/**
 * Richiede che l'utente sia un amministratore, altrimenti reindirizza
 * @param string $redirect_url URL a cui reindirizzare se l'utente non è admin
 * @param string $error_message Messaggio di errore da mostrare
 * @return void
 */
function require_admin($redirect_url = 'index.php', $error_message = 'Accesso negato: richiesti privilegi di amministratore') {
    if (!is_admin()) {
        // Memorizza il messaggio di errore in sessione
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['admin_error'] = $error_message;
        
        header("Location: $redirect_url");
        exit;
    }
}

/**
 * Aggiorna i dati dell'utente
 * @param int $user_id ID dell'utente da aggiornare
 * @param array $data Dati da aggiornare (chiave => valore)
 * @return bool True se l'aggiornamento ha avuto successo, altrimenti false
 */
function update_user($user_id, $data) {
    try {
        return db_update('users', $data, 'user_id = ?', [$user_id]);
    } catch (PDOException $e) {
        error_log("Errore nell'aggiornamento dell'utente: " . $e->getMessage());
        return false;
    }
}

/**
 * Ottiene i dati di un utente
 * @param int $user_id ID dell'utente di cui ottenere i dati
 * @return array|bool I dati dell'utente o false se non trovato
 */
function get_user_data($user_id) {
    try {
        return db_fetch_row(
            "SELECT user_id, username, email, registration_date, last_login, is_active, is_admin FROM users WHERE user_id = ?",
            [$user_id]
        );
    } catch (PDOException $e) {
        error_log("Errore nel recupero dei dati utente: " . $e->getMessage());
        return false;
    }
}

/**
 * Aggiorna la password di un utente
 * @param int $user_id ID dell'utente
 * @param string $current_password Password attuale
 * @param string $new_password Nuova password
 * @return array Risultato dell'operazione con success=true/false e message
 */
function update_user_password($user_id, $current_password, $new_password) {
    try {
        // Verifica la password corrente
        $user = db_fetch_row(
            "SELECT password_hash FROM users WHERE user_id = ?",
            [$user_id]
        );
        
        if (!$user || !password_verify($current_password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'La password attuale non è corretta'];
        }
        
        // Aggiorna la password
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $updated = db_update(
            'users', 
            ['password_hash' => $new_hash], 
            'user_id = ?', 
            [$user_id]
        );
        
        if ($updated) {
            return ['success' => true, 'message' => 'Password aggiornata con successo'];
        } else {
            return ['success' => false, 'message' => 'Errore durante l\'aggiornamento della password'];
        }
    } catch (PDOException $e) {
        error_log("Errore nell'aggiornamento della password: " . $e->getMessage());
        return ['success' => false, 'message' => 'Errore durante l\'aggiornamento della password: ' . $e->getMessage()];
    }
}