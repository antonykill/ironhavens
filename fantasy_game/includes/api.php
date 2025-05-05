<?php
/**
 * API Endpoints
 * 
 * Gestisce le richieste AJAX dal client
 */

/**
 * Gestisce le richieste API
 * @return void
 */
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
            $resources = get_player_resources(get_current_user_id());
            echo json_encode($resources);
            break;
            
        case 'get_buildings':
            if (!is_logged_in()) {
                echo json_encode(['error' => 'Utente non autenticato']);
                return;
            }
            $buildings = get_player_buildings(get_current_user_id());
            echo json_encode($buildings);
            break;
            
        case 'available_buildings':
            if (!is_logged_in()) {
                echo json_encode(['error' => 'Utente non autenticato']);
                return;
            }
            $player_level = get_player_level(get_current_user_id());
            $buildings = get_available_buildings($player_level['current_level']);
            echo json_encode($buildings);
            break;
            
        case 'unlocked_buildings':
            if (!is_logged_in()) {
                echo json_encode(['error' => 'Utente non autenticato']);
                return;
            }
            $buildings = get_unlocked_buildings(get_current_user_id());
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
            $result = start_building_construction(get_current_user_id(), $_POST['building_type_id']);
            echo json_encode($result);
            break;
            
        case 'check_buildings':
            if (!is_logged_in()) {
                echo json_encode(['error' => 'Utente non autenticato']);
                return;
            }
            $result = check_completed_buildings(get_current_user_id());
            echo json_encode($result);
            break;
            
        case 'update_resources':
            if (!is_logged_in()) {
                echo json_encode(['error' => 'Utente non autenticato']);
                return;
            }
            $result = update_player_resources(get_current_user_id());
            echo json_encode($result);
            break;
            
        case 'get_tech_tree':
            if (!is_logged_in()) {
                echo json_encode(['error' => 'Utente non autenticato']);
                return;
            }
            $tech_tree = get_tech_tree();
            echo json_encode($tech_tree);
            break;
            
        case 'get_building_details':
            if (!isset($_GET['building_id'])) {
                echo json_encode(['error' => 'ID edificio mancante']);
                return;
            }
            
            $building_id = (int) $_GET['building_id'];
            $building = get_building_type($building_id);
            
            if (!$building) {
                echo json_encode(['error' => 'Edificio non trovato']);
                return;
            }
            
            // Ottieni le dipendenze dell'edificio
            $building['dependencies'] = get_building_dependencies($building_id);
            
            // Controlla se l'edificio è sbloccato per l'utente
            if (is_logged_in()) {
                $user_id = get_current_user_id();
                $result = check_building_dependencies($user_id, $building_id);
                $building['is_unlocked'] = $result['success'];
                $building['locked_reason'] = $result['success'] ? null : $result['message'];
                
                // Controlla se l'utente ha già costruito questo edificio
                $has_built = db_fetch_row("
                    SELECT COUNT(*) as count FROM player_buildings
                    WHERE user_id = ? AND building_type_id = ? AND is_active = 1
                ", [$user_id, $building_id]);
                
                $building['is_built'] = ($has_built && $has_built['count'] > 0);
            } else {
                $building['is_unlocked'] = false;
                $building['locked_reason'] = 'Devi effettuare il login per vedere se questo edificio è sbloccato.';
                $building['is_built'] = false;
            }
            
            echo json_encode($building);
            break;
            
        case 'update_email':
            if (!is_logged_in()) {
                echo json_encode(['error' => 'Utente non autenticato']);
                return;
            }
            
            if (!isset($_POST['email'])) {
                echo json_encode(['error' => 'Email mancante']);
                return;
            }
            
            $new_email = $_POST['email'];
            $updated = update_user(get_current_user_id(), ['email' => $new_email]);
            
            if ($updated) {
                echo json_encode(['success' => true, 'message' => 'Email aggiornata con successo']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiornamento dell\'email']);
            }
            break;
            
        case 'update_password':
            if (!is_logged_in()) {
                echo json_encode(['error' => 'Utente non autenticato']);
                return;
            }
            
            if (!isset($_POST['current_password']) || !isset($_POST['new_password'])) {
                echo json_encode(['error' => 'Parametri mancanti']);
                return;
            }
            
            $result = update_user_password(
                get_current_user_id(),
                $_POST['current_password'],
                $_POST['new_password']
            );
            
            echo json_encode($result);
            break;
            
        case 'get_user_data':
            if (!is_logged_in()) {
                echo json_encode(['error' => 'Utente non autenticato']);
                return;
            }
            
            $user_data = get_user_data(get_current_user_id());
            
            if ($user_data) {
                echo json_encode(['success' => true, 'user' => $user_data]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Errore durante il recupero dei dati utente']);
            }
            break;
            
        // Endpoint per l'amministrazione
        case 'admin_get_users':
            if (!is_logged_in() || !is_admin()) {
                echo json_encode(['error' => 'Accesso negato']);
                return;
            }
            
            $users = db_fetch_all("
                SELECT u.*, pl.current_level
                FROM users u
                LEFT JOIN player_levels pl ON u.user_id = pl.user_id
                ORDER BY u.registration_date DESC
            ");
            
            echo json_encode(['success' => true, 'users' => $users]);
            break;
            
        case 'admin_delete_user':
            if (!is_logged_in() || !is_admin()) {
                echo json_encode(['error' => 'Accesso negato']);
                return;
            }
            
            if (!isset($_POST['user_id'])) {
                echo json_encode(['error' => 'ID utente mancante']);
                return;
            }
            
            $user_id_to_delete = (int) $_POST['user_id'];
            
            // Non permettere di eliminare se stessi
            if ($user_id_to_delete === get_current_user_id()) {
                echo json_encode(['success' => false, 'message' => 'Non puoi eliminare il tuo account!']);
                return;
            }
            
            try {
                $pdo = db_transaction_begin();
                
                // Elimina gli edifici dell'utente
                db_delete('player_buildings', 'user_id = ?', [$user_id_to_delete]);
                
                // Elimina le risorse dell'utente
                db_delete('player_resources', 'user_id = ?', [$user_id_to_delete]);
                
                // Elimina i livelli dell'utente
                db_delete('player_levels', 'user_id = ?', [$user_id_to_delete]);
                
                // Elimina gli eventi dell'utente
                db_delete('game_events', 'user_id = ?', [$user_id_to_delete]);
                
                // Elimina l'utente
                db_delete('users', 'user_id = ?', [$user_id_to_delete]);
                
                db_transaction_commit($pdo);
                
                echo json_encode(['success' => true, 'message' => 'Utente eliminato con successo']);
            } catch (PDOException $e) {
                if (isset($pdo) && $pdo->inTransaction()) {
                    db_transaction_rollback($pdo);
                }
                echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione dell\'utente: ' . $e->getMessage()]);
            }
            break;
            
        case 'admin_update_game_settings':
            if (!is_logged_in() || !is_admin()) {
                echo json_encode(['error' => 'Accesso negato']);
                return;
            }
            
            if (!isset($_POST['settings']) || !is_array($_POST['settings'])) {
                echo json_encode(['error' => 'Parametri mancanti']);
                return;
            }
            
            $settings = $_POST['settings'];
            $success = true;
            $errors = [];
            
            foreach ($settings as $name => $value) {
                try {
                    $updated = db_update(
                        'game_constants',
                        ['constant_value' => $value],
                        'constant_name = ?',
                        [$name]
                    );
                    
                    if (!$updated) {
                        $success = false;
                        $errors[] = "Impossibile aggiornare $name";
                    }
                } catch (PDOException $e) {
                    $success = false;
                    $errors[] = "Errore durante l'aggiornamento di $name: " . $e->getMessage();
                }
            }
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Impostazioni aggiornate con successo']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Errori durante l\'aggiornamento delle impostazioni', 'errors' => $errors]);
            }
            break;
            
        default:
            echo json_encode(['error' => 'Azione non valida']);
    }
}

// Se questo file viene chiamato direttamente, gestisci la richiesta API
if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    // Determine if we're in the includes directory
    if (dirname($_SERVER['SCRIPT_FILENAME']) == __DIR__) {
        require_once __DIR__ . '/../config.php';
        require_once 'functions.php';
        handle_api_request();
    }
}