<?php
/**
 * Experience Functions
 * 
 * Funzioni per la gestione dell'esperienza e dei livelli dei giocatori
 */

/**
 * Aggiunge punti esperienza a un giocatore e controlla se sale di livello
 * @param int $user_id ID del giocatore
 * @param int $xp_amount Quantità di XP da aggiungere
 * @param string $reason Motivo dell'assegnazione di XP (per i log)
 * @return array Risultato dell'operazione con informazioni sul livello
 */
function add_player_xp($user_id, $xp_amount, $reason = 'Azione di gioco') {
    try {
        // Ottieni i dati attuali del livello del giocatore
        $player_level = get_player_level($user_id);
        
        if (!$player_level) {
            return [
                'success' => false,
                'message' => 'Dati del livello non trovati',
                'level_up' => false
            ];
        }
        
        $current_level = $player_level['current_level'];
        $current_xp = $player_level['experience_points'];
        $next_level_xp = $player_level['next_level_xp'];
        
        // Aggiungi i punti XP
        $new_xp = $current_xp + $xp_amount;
        
        // Controlla se il giocatore sale di livello
        $level_up = $new_xp >= $next_level_xp;
        $new_level = $current_level;
        
        if ($level_up) {
            $new_level = $current_level + 1;
            // Calcola l'XP per il prossimo livello (usando la formula definita nel database)
            $next_xp = calculate_next_level_xp($new_level);
        } else {
            $next_xp = $next_level_xp;
        }
        
        // Aggiorna il database
        $updated = db_update(
            'player_levels',
            [
                'current_level' => $new_level,
                'experience_points' => $new_xp,
                'next_level_xp' => $next_xp
            ],
            'user_id = ?',
            [$user_id]
        );
        
        if (!$updated) {
            return [
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento dell\'esperienza',
                'level_up' => false
            ];
        }
        
        // Registra l'evento
        db_insert('game_events', [
            'user_id' => $user_id,
            'event_type' => 'XP_GAINED',
            'event_description' => "Ottenuti {$xp_amount} punti esperienza: {$reason}"
        ]);
        
        // Se il giocatore è salito di livello, registra un evento separato
        if ($level_up) {
            db_insert('game_events', [
                'user_id' => $user_id,
                'event_type' => 'LEVEL_UP',
                'event_description' => "Sei salito al livello {$new_level}!"
            ]);
            
            // Sblocca nuovi edifici se disponibili
            $newly_available = get_buildings_by_level($new_level);
            if (!empty($newly_available)) {
                $building_names = array_map(function($b) { return $b['name']; }, $newly_available);
                $names_list = implode(', ', $building_names);
                
                db_insert('game_events', [
                    'user_id' => $user_id,
                    'event_type' => 'BUILDINGS_UNLOCKED',
                    'event_description' => "Nuovi edifici disponibili: {$names_list}"
                ]);
            }
        }
        
        return [
            'success' => true,
            'message' => 'Esperienza aggiornata con successo',
            'level_up' => $level_up,
            'old_level' => $current_level,
            'new_level' => $new_level,
            'old_xp' => $current_xp,
            'new_xp' => $new_xp,
            'next_level_xp' => $next_xp,
            'xp_gained' => $xp_amount
        ];
    } catch (PDOException $e) {
        error_log("Errore nell'aggiunta di punti esperienza: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Errore durante l\'aggiornamento dell\'esperienza: ' . $e->getMessage(),
            'level_up' => false
        ];
    }
}

/**
 * Calcola l'XP necessaria per il prossimo livello
 * @param int $level Livello attuale del giocatore
 * @return int XP necessaria per il prossimo livello
 */
function calculate_next_level_xp($level) {
    // Ottieni la costante BASE_XP_PER_LEVEL dal database
    $base_xp = intval(get_game_constant('BASE_XP_PER_LEVEL', 100));
    
    // Formula: BASE_XP_PER_LEVEL * (level ^ 1.5)
    // Questa formula crea una progressione che diventa più ripida man mano che il livello aumenta
    return ceil($base_xp * pow($level, 1.5));
}

/**
 * Ottiene tutti gli edifici disponibili a un certo livello
 * @param int $level Livello per cui cercare gli edifici
 * @return array Lista degli edifici disponibili a quel livello
 */
function get_buildings_by_level($level) {
    try {
        return db_fetch_all(
            "SELECT * FROM building_types WHERE level_required = ? ORDER BY name",
            [$level]
        );
    } catch (PDOException $e) {
        error_log("Errore nel recupero degli edifici per livello: " . $e->getMessage());
        return [];
    }
}

/**
 * Assegna punti XP per il completamento di un edificio
 * @param int $user_id ID del giocatore
 * @param int $building_id ID dell'edificio completato
 * @param int $building_level Livello dell'edificio completato
 * @return array Risultato dell'operazione
 */
function award_building_completion_xp($user_id, $building_id, $building_level) {
    // Ottieni la costante XP_PER_BUILDING dal database
    $xp_per_building = intval(get_game_constant('XP_PER_BUILDING', 20));
    
    // Calcola l'XP in base al livello dell'edificio
    $xp_to_award = $xp_per_building * $building_level;
    
    // Ottieni il nome dell'edificio
    $building_name = db_fetch_row(
        "SELECT bt.name FROM player_buildings pb
         JOIN building_types bt ON pb.building_type_id = bt.building_type_id
         WHERE pb.player_building_id = ?",
        [$building_id]
    );
    
    $name = $building_name ? $building_name['name'] : 'Edificio sconosciuto';
    
    // Assegna i punti XP
    return add_player_xp(
        $user_id, 
        $xp_to_award, 
        "Completamento edificio: {$name} livello {$building_level}"
    );
}

/**
 * Assegna punti XP per il login giornaliero
 * @param int $user_id ID del giocatore
 * @return array Risultato dell'operazione
 */
function award_daily_login_xp($user_id) {
    // Verifica se l'utente ha già ricevuto XP per il login oggi
    $last_login_xp = db_fetch_row(
        "SELECT COUNT(*) as count FROM game_events 
         WHERE user_id = ? AND event_type = 'XP_GAINED' 
         AND event_description LIKE '%Login giornaliero%'
         AND DATE(event_time) = CURDATE()",
        [$user_id]
    );
    
    if ($last_login_xp && $last_login_xp['count'] > 0) {
        return [
            'success' => false,
            'message' => 'Hai già ricevuto i punti XP per il login di oggi',
            'level_up' => false
        ];
    }
    
    // Assegna punti XP per il login
    $login_xp = intval(get_game_constant('DAILY_LOGIN_XP', 10));
    
    return add_player_xp($user_id, $login_xp, "Login giornaliero");
}

/**
 * Assegna punti XP per attività periodiche
 * @param int $user_id ID del giocatore
 * @return array Risultati delle operazioni
 */
function award_periodic_xp($user_id) {
    $results = [];
    
    // XP per mantenimento degli edifici
    $buildings_count = db_fetch_row(
        "SELECT COUNT(*) as count FROM player_buildings WHERE user_id = ? AND is_active = 1",
        [$user_id]
    );
    
    if ($buildings_count && $buildings_count['count'] > 0) {
        $buildings_xp = intval($buildings_count['count'] * 2); // 2 XP per edificio
        $results['buildings'] = add_player_xp(
            $user_id, 
            $buildings_xp, 
            "Mantenimento edifici attivi"
        );
    }
    
    // XP per produzione di risorse
    $resources = get_player_resources($user_id);
    $production_xp = intval(
        ($resources['water'] + $resources['food'] + $resources['wood'] + $resources['stone']) / 100
    );
    
    if ($production_xp > 0) {
        $results['production'] = add_player_xp(
            $user_id, 
            $production_xp, 
            "Produzione di risorse"
        );
    }
    
    return $results;
}

/**
 * Ottiene la classifica dei giocatori per livello ed esperienza
 * @param int $limit Numero massimo di giocatori da restituire (default: 10)
 * @return array Lista dei giocatori ordinata per livello ed esperienza
 */
function get_player_leaderboard($limit = 10) {
    try {
        return db_fetch_all("
            SELECT u.username, pl.current_level, pl.experience_points,
                   ROW_NUMBER() OVER (ORDER BY pl.current_level DESC, pl.experience_points DESC) as rank
            FROM player_levels pl
            JOIN users u ON pl.user_id = u.user_id
            ORDER BY pl.current_level DESC, pl.experience_points DESC
            LIMIT ?
        ", [$limit]);
    } catch (PDOException $e) {
        error_log("Errore nel recupero della classifica: " . $e->getMessage());
        return [];
    }
}