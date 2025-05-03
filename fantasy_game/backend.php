<?php
// config.php - Configurazione database e altre impostazioni
define('DB_HOST', 'localhost');
define('DB_NAME', 'fantasy_game');
define('DB_USER', 'root'); // Cambia con il tuo username MySQL
define('DB_PASS', 'password'); // Cambia con la tua password MySQL
define('SITE_NAME', 'Fantasy Resource Game');
define('GAME_VERSION', '1.0.0');

// Connessione al database
function db_connect() {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        return $pdo;
    } catch (PDOException $e) {
        die('Errore di connessione al database: ' . $e->getMessage());
    }
}

// Funzioni per l'autenticazione
function register_user($username, $email, $password) {
    try {
        $pdo = db_connect();
        
        // Verifica se l'utente o l'email esistono già
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Username o email già in uso'];
        }
        
        // Hash della password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Inizia una transazione
        $pdo->beginTransaction();
        
        // Inserisci il nuovo utente
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$username, $email, $password_hash]);
        $user_id = $pdo->lastInsertId();
        
        // Crea record per livello
        $stmt = $pdo->prepare('INSERT INTO player_levels (user_id) VALUES (?)');
        $stmt->execute([$user_id]);
        
        // Crea record per risorse
        $stmt = $pdo->prepare('INSERT INTO player_resources (user_id) VALUES (?)');
        $stmt->execute([$user_id]);
        
        // Registra l'evento
        $stmt = $pdo->prepare('INSERT INTO game_events (user_id, event_type, event_description) VALUES (?, ?, ?)');
        $stmt->execute([$user_id, 'REGISTRATION', 'Nuovo giocatore registrato']);
        
        // Commit della transazione
        $pdo->commit();
        
        return ['success' => true, 'user_id' => $user_id, 'message' => 'Registrazione completata con successo'];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => 'Errore durante la registrazione: ' . $e->getMessage()];
    }
}

function login_user($username_or_email, $password) {
    try {
        $pdo = db_connect();
        
        // Cerca l'utente
        $stmt = $pdo->prepare('SELECT user_id, username, password_hash FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username_or_email, $username_or_email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Credenziali non valide'];
        }
        
        // Aggiorna last_login
        $stmt = $pdo->prepare('UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?');
        $stmt->execute([$user['user_id']]);
        
        // Inizia la sessione
        session_start();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['logged_in'] = true;
        
        // Registra l'evento
        $stmt = $pdo->prepare('INSERT INTO game_events (user_id, event_type, event_description) VALUES (?, ?, ?)');
        $stmt->execute([$user['user_id'], 'LOGIN', 'Accesso effettuato']);
        
        return ['success' => true, 'user_id' => $user['user_id'], 'message' => 'Login effettuato con successo'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Errore durante il login: ' . $e->getMessage()];
    }
}

function logout_user() {
    // Recupera l'ID utente prima di distruggere la sessione
    $user_id = $_SESSION['user_id'] ?? null;
    
    // Distruggi la sessione
    session_start();
    $_SESSION = [];
    session_destroy();
    
    // Registra l'evento se l'ID utente è disponibile
    if ($user_id) {
        try {
            $pdo = db_connect();
            $stmt = $pdo->prepare('INSERT INTO game_events (user_id, event_type, event_description) VALUES (?, ?, ?)');
            $stmt->execute([$user_id, 'LOGOUT', 'Logout effettuato']);
        } catch (PDOException $e) {
            // Ignora errori durante il logout
        }
    }
    
    return ['success' => true, 'message' => 'Logout effettuato con successo'];
}

function is_logged_in() {
    session_start();
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Funzioni per la gestione delle risorse del giocatore
function get_player_resources($user_id) {
    try {
        $pdo = db_connect();
        $stmt = $pdo->prepare('SELECT * FROM player_resources WHERE user_id = ?');
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return ['error' => 'Errore nel recupero delle risorse: ' . $e->getMessage()];
    }
}

function get_player_level($user_id) {
    try {
        $pdo = db_connect();
        $stmt = $pdo->prepare('SELECT * FROM player_levels WHERE user_id = ?');
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return ['error' => 'Errore nel recupero del livello: ' . $e->getMessage()];
    }
}

// Funzioni per la gestione degli edifici
function get_available_buildings($user_level) {
    try {
        $pdo = db_connect();
        $stmt = $pdo->prepare('SELECT * FROM building_types WHERE level_required <= ?');
        $stmt->execute([$user_level]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return ['error' => 'Errore nel recupero degli edifici disponibili: ' . $e->getMessage()];
    }
}

function get_player_buildings($user_id) {
    try {
        $pdo = db_connect();
        $stmt = $pdo->prepare('
            SELECT pb.*, bt.name, bt.description, bt.image_url 
            FROM player_buildings pb
            JOIN building_types bt ON pb.building_type_id = bt.building_type_id
            WHERE pb.user_id = ?
        ');
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return ['error' => 'Errore nel recupero degli edifici del giocatore: ' . $e->getMessage()];
    }
}

function start_building_construction($user_id, $building_type_id) {
    try {
        $pdo = db_connect();
        
        // Ottieni informazioni sull'edificio
        $stmt = $pdo->prepare('SELECT * FROM building_types WHERE building_type_id = ?');
        $stmt->execute([$building_type_id]);
        $building = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$building) {
            return ['success' => false, 'message' => 'Edificio non trovato'];
        }
        
        // Ottieni il livello del giocatore
        $stmt = $pdo->prepare('SELECT current_level FROM player_levels WHERE user_id = ?');
        $stmt->execute([$user_id]);
        $player_level = $stmt->fetchColumn();
        
        if ($player_level < $building['level_required']) {
            return [
                'success' => false, 
                'message' => 'Livello del giocatore troppo basso per costruire questo edificio'
            ];
        }
        
        // Ottieni le risorse del giocatore
        $stmt = $pdo->prepare('SELECT * FROM player_resources WHERE user_id = ?');
        $stmt->execute([$user_id]);
        $resources = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Controlla se ci sono abbastanza risorse
        if ($resources['water'] < $building['water_cost'] ||
            $resources['food'] < $building['food_cost'] ||
            $resources['wood'] < $building['wood_cost'] ||
            $resources['stone'] < $building['stone_cost']) {
            return ['success' => false, 'message' => 'Risorse insufficienti per costruire questo edificio'];
        }
        
        // Calcola quando la costruzione sarà completata
        $completion_time = date('Y-m-d H:i:s', strtotime('+' . $building['build_time_minutes'] . ' minutes'));
        
        // Inizia una transazione
        $pdo->beginTransaction();
        
        // Sottrai le risorse
        $stmt = $pdo->prepare('
            UPDATE player_resources 
            SET water = water - ?, food = food - ?, wood = wood - ?, stone = stone - ?, last_update = CURRENT_TIMESTAMP 
            WHERE user_id = ?
        ');
        $stmt->execute([
            $building['water_cost'],
            $building['food_cost'],
            $building['wood_cost'],
            $building['stone_cost'],
            $user_id
        ]);
        
        // Verifica se l'edificio esiste già per questo utente
        $stmt = $pdo->prepare('
            SELECT player_building_id, quantity FROM player_buildings
            WHERE user_id = ? AND building_type_id = ?
        ');
        $stmt->execute([$user_id, $building_type_id]);
        $existing_building = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_building) {
            // Aggiorna la quantità
            $stmt = $pdo->prepare('
                UPDATE player_buildings 
                SET quantity = quantity + 1,
                    construction_started = CURRENT_TIMESTAMP,
                    construction_completed = ?,
                    is_active = FALSE
                WHERE player_building_id = ?
            ');
            $stmt->execute([$completion_time, $existing_building['player_building_id']]);
            $building_id = $existing_building['player_building_id'];
        } else {
            // Crea un nuovo record
            $stmt = $pdo->prepare('
                INSERT INTO player_buildings 
                (user_id, building_type_id, construction_started, construction_completed) 
                VALUES (?, ?, CURRENT_TIMESTAMP, ?)
            ');
            $stmt->execute([$user_id, $building_type_id, $completion_time]);
            $building_id = $pdo->lastInsertId();
        }
        
        // Registra l'evento
        $stmt = $pdo->prepare('
            INSERT INTO game_events (user_id, event_type, event_description) 
            VALUES (?, ?, ?)
        ');
        $stmt->execute([
            $user_id, 
            'CONSTRUCTION_STARTED', 
            'Iniziata costruzione: ' . $building['name']
        ]);
        
        // Commit della transazione
        $pdo->commit();
        
        return [
            'success' => true, 
            'message' => 'Costruzione avviata con successo',
            'completion_time' => $completion_time,
            'building_id' => $building_id
        ];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => 'Errore durante l\'avvio della costruzione: ' . $e->getMessage()];
    }
}

function check_completed_buildings($user_id) {
    try {
        $pdo = db_connect();
        
        // Trova edifici completati ma non ancora attivati
        $stmt = $pdo->prepare('
            SELECT pb.*, bt.name 
            FROM player_buildings pb
            JOIN building_types bt ON pb.building_type_id = bt.building_type_id
            WHERE pb.user_id = ? 
              AND pb.is_active = FALSE 
              AND pb.construction_completed <= CURRENT_TIMESTAMP
        ');
        $stmt->execute([$user_id]);
        $completed_buildings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($completed_buildings)) {
            return ['success' => true, 'message' => 'Nessun edificio completato', 'buildings' => []];
        }
        
        // Attiva tutti gli edifici completati
        $pdo->beginTransaction();
        
        foreach ($completed_buildings as $building) {
            $stmt = $pdo->prepare('
                UPDATE player_buildings 
                SET is_active = TRUE 
                WHERE player_building_id = ?
            ');
            $stmt->execute([$building['player_building_id']]);
        }
        
        $pdo->commit();
        
        return [
            'success' => true, 
            'message' => count($completed_buildings) . ' edifici completati e attivati',
            'buildings' => $completed_buildings
        ];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => 'Errore durante il controllo degli edifici completati: ' . $e->getMessage()];
    }
}

// Funzione per aggiornare periodicamente le risorse del giocatore
function update_player_resources($user_id) {
    try {
        $pdo = db_connect();
        
        // Ottieni tutti gli edifici attivi del giocatore
        $stmt = $pdo->prepare('
            SELECT pb.building_type_id, pb.quantity, pb.level, 
                   bt.water_production, bt.food_production, bt.wood_production, bt.stone_production
            FROM player_buildings pb
            JOIN building_types bt ON pb.building_type_id = bt.building_type_id
            WHERE pb.user_id = ? AND pb.is_active = TRUE
        ');
        $stmt->execute([$user_id]);
        $buildings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcola la produzione totale
        $total_water = 0;
        $total_food = 0;
        $total_wood = 0;
        $total_stone = 0;
        
        foreach ($buildings as $building) {
            $total_water += $building['water_production'] * $building['quantity'] * $building['level'];
            $total_food += $building['food_production'] * $building['quantity'] * $building['level'];
            $total_wood += $building['wood_production'] * $building['quantity'] * $building['level'];
            $total_stone += $building['stone_production'] * $building['quantity'] * $building['level'];
        }
        
        // Aggiorna le risorse del giocatore
        $stmt = $pdo->prepare('
            UPDATE player_resources 
            SET water = water + ?, 
                food = food + ?, 
                wood = wood + ?, 
                stone = stone + ?, 
                last_update = CURRENT_TIMESTAMP 
            WHERE user_id = ?
        ');
        $stmt->execute([$total_water, $total_food, $total_wood, $total_stone, $user_id]);
        
        return [
            'success' => true, 
            'message' => 'Risorse aggiornate con successo',
            'production' => [
                'water' => $total_water,
                'food' => $total_food,
                'wood' => $total_wood,
                'stone' => $total_stone
            ]
        ];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Errore durante l\'aggiornamento delle risorse: ' . $e->getMessage()];
    }
}

// API endpoint per gestire le richieste AJAX
function handle_api_request() {
    header('Content-Type: application/json');
    
    if (!isset($_GET['action'])) {
        echo json_encode(['error' => 'Azione non specificata']);
        return;
    }
    
    $action = $_GET['action'];
    
    switch ($action) {
        case 'register':
            if (!isset($_POST['username']) || !isset($_POST['email']) || !isset($_POST['password'])) {
                echo json_encode(['error' => 'Parametri mancanti']);
                return;
            }
            $result = register_user($_POST['username'], $_POST['email'], $_POST['password']);
            echo json_encode($result);
            break;
            
        case 'login':
            if (!isset($_POST['username_email']) || !isset($_POST['password'])) {
                echo json_encode(['error' => 'Parametri mancanti']);
                return;
            }
            $result = login_user($_POST['username_email'], $_POST['password']);
            echo json_encode($result);
            break;
            
        case 'logout':
            $result = logout_user();
            echo json_encode($result);
            break;
            
        case 'get_resources':
            if (!is_logged_in()) {
                echo json_encode(['error' => 'Utente non autenticato']);
                return;
            }
            $resources = get_player_resources($_SESSION['user_id']);
            echo json_encode($resources);
            break;
            
        case 'get_buildings':
            if (!is_logged_in()) {
                echo json_encode(['error' => 'Utente non autenticato']);
                return;
            }
            $buildings = get_player_buildings($_SESSION['user_id']);
            echo json_encode($buildings);
            break;
            
        case 'available_buildings':
            if (!is_logged_in()) {
                echo json_encode(['error' => 'Utente non autenticato']);
                return;
            }
            $player_level = get_player_level($_SESSION['user_id']);
            $buildings = get_available_buildings($player_level['current_level']);
            echo json_encode($buildings);
            break;
            
        case 'start_construction':
            if (!is_logged_in()) {
                echo json_encode(['error' => 'Utente non autenticato']);
                return;
            }
            if (!isset($_POST['building_type_id'])) {
                echo json_encode(['error' => 'ID edificio mancante']);
                return;
            }
            $result = start_building_construction($_SESSION['user_id'], $_POST['building_type_id']);
            echo json_encode($result);
            break;
            
        case 'check_buildings':
            if (!is_logged_in()) {
                echo json_encode(['error' => 'Utente non autenticato']);
                return;
            }
            $result = check_completed_buildings($_SESSION['user_id']);
            echo json_encode($result);
            break;
            
        case 'update_resources':
            if (!is_logged_in()) {
                echo json_encode(['error' => 'Utente non autenticato']);
                return;
            }
            $result = update_player_resources($_SESSION['user_id']);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['error' => 'Azione non valida']);
    }
}

// Se questo file viene chiamato direttamente, gestisci la richiesta API
if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    handle_api_request();
}
?>