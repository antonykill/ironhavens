<?php
/**
 * Game Logging System
 * 
 * Sistema di logging centralizzato per il gioco Ironhaven
 */

/**
 * Registra un'operazione nel log del sistema
 * 
 * @param string $log_type Tipo di log (BUILDING, RESOURCE, DB, SYSTEM, AUTH)
 * @param string $action Azione specifica
 * @param array $data Dati relativi all'azione
 * @param int|null $user_id ID dell'utente (null per azioni di sistema)
 * @return bool True se il log è stato registrato con successo
 */
function log_game_action($log_type, $action, $data = [], $user_id = null) {
    try {
        // Se l'utente non è specificato ma è loggato, usa l'ID dell'utente corrente
        if ($user_id === null && function_exists('get_current_user_id')) {
            $user_id = get_current_user_id();
        }
        
        // Serializza i dati per il salvataggio
        $serialized_data = json_encode($data, JSON_UNESCAPED_UNICODE);
        
        // Inserisci il log nel database
        return db_insert('game_logs', [
            'user_id' => $user_id,
            'log_type' => $log_type,
            'action' => $action,
            'data' => $serialized_data,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]) ? true : false;
    } catch (PDOException $e) {
        // In caso di errore, registra l'errore nei log di sistema
        error_log("Errore durante la registrazione del log: " . $e->getMessage());
        return false;
    }
}

/**
 * Registra un'operazione relativa agli edifici
 * 
 * @param string $action Azione (CONSTRUCTION_STARTED, CONSTRUCTION_COMPLETED, BUILDING_UPGRADED, ecc.)
 * @param array $data Dati relativi all'azione
 * @param int|null $user_id ID dell'utente
 * @return bool True se il log è stato registrato con successo
 */
function log_building_action($action, $data = [], $user_id = null) {
    return log_game_action('BUILDING', $action, $data, $user_id);
}

/**
 * Registra un'operazione relativa alle risorse
 * 
 * @param string $action Azione (RESOURCES_UPDATED, RESOURCES_PRODUCED, RESOURCES_CONSUMED, ecc.)
 * @param array $data Dati relativi all'azione
 * @param int|null $user_id ID dell'utente
 * @return bool True se il log è stato registrato con successo
 */
function log_resource_action($action, $data = [], $user_id = null) {
    return log_game_action('RESOURCE', $action, $data, $user_id);
}

/**
 * Registra un'operazione relativa al database
 * 
 * @param string $action Azione (QUERY_EXECUTED, TRANSACTION_STARTED, TRANSACTION_COMMITTED, TRANSACTION_ROLLED_BACK)
 * @param array $data Dati relativi all'azione
 * @param int|null $user_id ID dell'utente
 * @return bool True se il log è stato registrato con successo
 */
function log_db_action($action, $data = [], $user_id = null) {
    return log_game_action('DB', $action, $data, $user_id);
}

/**
 * Ottiene i log di gioco in base ai filtri
 * @param array $filters Array di filtri (log_type, user_id, action, date_from, date_to)
 * @param int $page Numero di pagina
 * @param int $per_page Risultati per pagina
 * @return array Array con log e info di paginazione
 */
function get_game_logs($filters = [], $page = 1, $per_page = 50) {
    try {
        $page = max(1, $page);
        $per_page = max(1, $per_page);
        $offset = ($page - 1) * $per_page;
        
        // Costruisci la query base
        $query = "
            SELECT gl.*, u.username 
            FROM game_logs gl
            LEFT JOIN users u ON gl.user_id = u.user_id
            WHERE 1=1
        ";
        
        $count_query = "SELECT COUNT(*) as total FROM game_logs gl WHERE 1=1";
        $params = [];
        
        // Aggiungi i filtri alla query
        if (!empty($filters['log_type'])) {
            $query .= " AND gl.log_type = ?";
            $count_query .= " AND gl.log_type = ?";
            $params[] = $filters['log_type'];
        }
        
        if (!empty($filters['user_id'])) {
            $query .= " AND gl.user_id = ?";
            $count_query .= " AND gl.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $query .= " AND gl.action = ?";
            $count_query .= " AND gl.action = ?";
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['date_from'])) {
            $date_from = date('Y-m-d', strtotime($filters['date_from'])) . ' 00:00:00';
            $query .= " AND gl.created_at >= ?";
            $count_query .= " AND gl.created_at >= ?";
            $params[] = $date_from;
        }
        
        if (!empty($filters['date_to'])) {
            $date_to = date('Y-m-d', strtotime($filters['date_to'])) . ' 23:59:59';
            $query .= " AND gl.created_at <= ?";
            $count_query .= " AND gl.created_at <= ?";
            $params[] = $date_to;
        }
        
        // Aggiungi ordinamento e paginazione
        $query .= " ORDER BY gl.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;
        
        // Esegui le query
        $logs = db_fetch_all($query, $params);
        
        // Ottieni il totale dei risultati
        $total_result = db_fetch_row($count_query, array_slice($params, 0, count($params) - 2));
        $total = $total_result ? $total_result['total'] : 0;
        
        // Decodifica il campo data JSON
        foreach ($logs as $key => $log) {
            if (!empty($log['data'])) {
                $logs[$key]['data'] = json_decode($log['data'], true);
            } else {
                $logs[$key]['data'] = [];
            }
        }
        
        return [
            'logs' => $logs,
            'pagination' => [
                'total' => $total,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => ceil($total / $per_page)
            ]
        ];
    } catch (PDOException $e) {
        error_log("Errore nel recupero dei log: " . $e->getMessage());
        return [
            'logs' => [],
            'pagination' => [
                'total' => 0,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => 0
            ]
        ];
    }
}

/**
 * Elimina i log più vecchi di un certo periodo
 * 
 * @param int $days Numero di giorni da mantenere (default: 30)
 * @return int Numero di log eliminati
 */
function clean_old_logs($days = 30) {
    try {
        $date_limit = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        $sql = "DELETE FROM game_logs WHERE created_at < ?";
        $stmt = db_query($sql, [$date_limit]);
        
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Errore durante la pulizia dei log: " . $e->getMessage());
        return 0;
    }
}