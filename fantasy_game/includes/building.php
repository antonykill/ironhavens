<?php
/**
 * Building Functions
 * 
 * Funzioni per la gestione degli edifici e delle costruzioni
 */

/**
 * Ottiene tutti i tipi di edifici disponibili
 * @return array Lista di tutti i tipi di edifici
 */
function get_all_building_types() {
    try {
        return db_fetch_all("SELECT * FROM building_types ORDER BY level_required, name");
    } catch (PDOException $e) {
        error_log("Errore nel recupero dei tipi di edifici: " . $e->getMessage());
        return [];
    }
}

/**
 * Ottiene i dettagli di un tipo di edificio
 * @param int $building_type_id ID del tipo di edificio
 * @return array|bool Dettagli del tipo di edificio o false se non trovato
 */
function get_building_type($building_type_id) {
    try {
        return db_fetch_row(
            "SELECT * FROM building_types WHERE building_type_id = ?",
            [$building_type_id]
        );
    } catch (PDOException $e) {
        error_log("Errore nel recupero dei dettagli dell'edificio: " . $e->getMessage());
        return false;
    }
}

/**
 * Ottiene gli edifici disponibili per un giocatore in base al suo livello
 * @param int $player_level Livello del giocatore
 * @return array Lista degli edifici disponibili per il livello del giocatore
 */
function get_available_buildings($player_level) {
    try {
        return db_fetch_all(
            "SELECT * FROM building_types WHERE level_required <= ? ORDER BY level_required, name",
            [$player_level]
        );
    } catch (PDOException $e) {
        error_log("Errore nel recupero degli edifici disponibili: " . $e->getMessage());
        return [];
    }
}

/**
 * Ottiene gli edifici costruiti da un giocatore
 * @param int $user_id ID del giocatore
 * @return array Lista degli edifici costruiti dal giocatore
 */
function get_player_buildings($user_id) {
    try {
        return db_fetch_all("
            SELECT pb.*, bt.name, bt.description, bt.image_url, 
                   bt.water_production, bt.food_production, bt.wood_production, bt.stone_production,
                   bt.capacity_increase
            FROM player_buildings pb
            JOIN building_types bt ON pb.building_type_id = bt.building_type_id
            WHERE pb.user_id = ?
            ORDER BY pb.is_active DESC, bt.name
        ", [$user_id]);
    } catch (PDOException $e) {
        error_log("Errore nel recupero degli edifici del giocatore: " . $e->getMessage());
        return [];
    }
}

/**
 * Avvia la costruzione di un edificio
 * @param int $user_id ID del giocatore
 * @param int $building_type_id ID del tipo di edificio da costruire
 * @return array Risultato dell'operazione con success=true/false e message
 */
function start_building_construction($user_id, $building_type_id) {
    try {
        // Controlla le dipendenze prima di tutto
        $dependencies_check = check_building_dependencies($user_id, $building_type_id);
        if (!$dependencies_check['success']) {
            return [
                'success' => false, 
                'message' => $dependencies_check['message']
            ];
        }
        
        // Ottieni informazioni sull'edificio
        $building = get_building_type($building_type_id);
        
        if (!$building) {
            return ['success' => false, 'message' => 'Edificio non trovato'];
        }
        
        // Ottieni il livello del giocatore
        $player_level = get_player_level($user_id);
        
        if ($player_level['current_level'] < $building['level_required']) {
            return [
                'success' => false, 
                'message' => 'Livello del giocatore troppo basso per costruire questo edificio'
            ];
        }
        
        // Ottieni le risorse del giocatore
        $resources = get_player_resources($user_id);
        
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
        $pdo = db_transaction_begin();
        
        // Sottrai le risorse
        $updated = db_update(
            'player_resources',
            [
                'water' => $resources['water'] - $building['water_cost'],
                'food' => $resources['food'] - $building['food_cost'],
                'wood' => $resources['wood'] - $building['wood_cost'],
                'stone' => $resources['stone'] - $building['stone_cost'],
                'last_update' => date('Y-m-d H:i:s')
            ],
            'user_id = ?',
            [$user_id]
        );
        
        if (!$updated) {
            db_transaction_rollback($pdo);
            return ['success' => false, 'message' => 'Errore durante l\'aggiornamento delle risorse'];
        }
        
        // Verifica se l'edificio esiste già per questo utente
        $existing_building = db_fetch_row("
            SELECT player_building_id, quantity FROM player_buildings
            WHERE user_id = ? AND building_type_id = ?
        ", [$user_id, $building_type_id]);
        
        if ($existing_building) {
            // Aggiorna la quantità
            $updated = db_update(
                'player_buildings',
                [
                    'quantity' => $existing_building['quantity'] + 1,
                    'construction_started' => date('Y-m-d H:i:s'),
                    'construction_completed' => $completion_time,
                    'is_active' => 0
                ],
                'player_building_id = ?',
                [$existing_building['player_building_id']]
            );
            
            if (!$updated) {
                db_transaction_rollback($pdo);
                return ['success' => false, 'message' => 'Errore durante l\'aggiornamento dell\'edificio'];
            }
            
            $building_id = $existing_building['player_building_id'];
        } else {
            // Crea un nuovo record
            $building_id = db_insert('player_buildings', [
                'user_id' => $user_id,
                'building_type_id' => $building_type_id,
                'construction_started' => date('Y-m-d H:i:s'),
                'construction_completed' => $completion_time,
                'is_active' => 0
            ]);
            
            if (!$building_id) {
                db_transaction_rollback($pdo);
                return ['success' => false, 'message' => 'Errore durante la creazione dell\'edificio'];
            }
        }
        
        // Registra l'evento
        $event_inserted = db_insert('game_events', [
            'user_id' => $user_id,
            'event_type' => 'CONSTRUCTION_STARTED',
            'event_description' => 'Iniziata costruzione: ' . $building['name']
        ]);
        
        if (!$event_inserted) {
            db_transaction_rollback($pdo);
            return ['success' => false, 'message' => 'Errore durante la registrazione dell\'evento'];
        }
        
        // Commit della transazione
        db_transaction_commit($pdo);
        
        return [
            'success' => true,
            'message' => 'Costruzione avviata con successo',
            'completion_time' => $completion_time,
            'building_id' => $building_id
        ];
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            db_transaction_rollback($pdo);
        }
        error_log("Errore durante l'avvio della costruzione: " . $e->getMessage());
        return ['success' => false, 'message' => 'Errore durante l\'avvio della costruzione: ' . $e->getMessage()];
    }
}

/**
 * Controlla se ci sono edifici la cui costruzione è stata completata
 * @param int $user_id ID del giocatore
 * @return array Risultato dell'operazione con success=true/false, message e buildings (array di edifici completati)
 */
function check_completed_buildings($user_id) {
    $pdo = null; // Inizializza $pdo come null
    
    try {
        // Trova edifici completati ma non ancora attivati
        $completed_buildings = db_fetch_all("
            SELECT pb.*, bt.name 
            FROM player_buildings pb
            JOIN building_types bt ON pb.building_type_id = bt.building_type_id
            WHERE pb.user_id = ? 
              AND pb.is_active = 0 
              AND pb.construction_completed <= ?
        ", [$user_id, date('Y-m-d H:i:s')]);
        
        if (empty($completed_buildings)) {
            return ['success' => true, 'message' => 'Nessun edificio completato', 'buildings' => []];
        }
        
        // Attiva tutti gli edifici completati
        $pdo = db_transaction_begin();
        
        foreach ($completed_buildings as $building) {
            $updated = db_update(
                'player_buildings',
                ['is_active' => 1],
                'player_building_id = ?',
                [$building['player_building_id']]
            );
            
            if (!$updated) {
                db_transaction_rollback($pdo);
                return [
                    'success' => false,
                    'message' => 'Errore durante l\'attivazione dell\'edificio: ' . $building['name'] . ' (ID: ' . $building['player_building_id'] . ')',
                    'buildings' => []
                ];
            }
        }
        
        db_transaction_commit($pdo);
        
        return [
            'success' => true,
            'message' => count($completed_buildings) . ' edifici completati e attivati',
            'buildings' => $completed_buildings
        ];
    } catch (PDOException $e) {
        if ($pdo !== null && $pdo->inTransaction()) {
            db_transaction_rollback($pdo);
        }
        error_log("Errore durante il controllo degli edifici completati: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Errore durante il controllo degli edifici completati: ' . $e->getMessage(),
            'buildings' => []
        ];
    }
}

/**
 * Ottiene le dipendenze per un edificio specifico
 * @param int $building_type_id ID del tipo di edificio
 * @return array Lista delle dipendenze
 */
function get_building_dependencies($building_type_id) {
    try {
        return db_fetch_all("
            SELECT bd.required_building_id, bd.required_building_level, bt.name, bt.image_url 
            FROM building_dependencies bd
            JOIN building_types bt ON bd.required_building_id = bt.building_type_id
            WHERE bd.building_type_id = ?
        ", [$building_type_id]);
    } catch (PDOException $e) {
        error_log("Errore nell'ottenere le dipendenze degli edifici: " . $e->getMessage());
        return [];
    }
}

/**
 * Controlla se un edificio soddisfa tutti i requisiti di dipendenza
 * @param int $user_id ID dell'utente
 * @param int $building_type_id ID del tipo di edificio da controllare
 * @return array Risultato del controllo con success=true/false e message contenente eventuali errori
 */
function check_building_dependencies($user_id, $building_type_id) {
    try {
        // Ottieni il livello del giocatore
        $player_level = get_player_level($user_id);
        
        // Ottieni il requisito di livello per l'edificio
        $building = get_building_type($building_type_id);
        if (!$building) {
            return [
                'success' => false,
                'message' => "Edificio non trovato."
            ];
        }
        
        // Controlla se il giocatore ha il livello richiesto
        if ($player_level['current_level'] < $building['level_required']) {
            return [
                'success' => false,
                'message' => "Livello giocatore insufficiente. Necessario livello " . $building['level_required'] . "."
            ];
        }
        
        // Ottieni le dipendenze per questo edificio
        $dependencies = get_building_dependencies($building_type_id);
        
        // Se non ci sono dipendenze, restituisci success
        if (empty($dependencies)) {
            return ['success' => true];
        }
        
        // Controlla ogni dipendenza
        foreach ($dependencies as $dependency) {
            $required_building_id = $dependency['required_building_id'];
            $required_level = $dependency['required_building_level'];
            $building_name = $dependency['name'];
            
            // Verifica se il giocatore ha l'edificio richiesto al livello richiesto
            $has_required = db_fetch_row("
                SELECT COUNT(*) as count FROM player_buildings
                WHERE user_id = ? 
                  AND building_type_id = ?
                  AND level >= ?
                  AND is_active = 1
            ", [$user_id, $required_building_id, $required_level]);
            
            if (!$has_required || $has_required['count'] == 0) {
                return [
                    'success' => false,
                    'message' => "Necessario " . $building_name . " livello " . $required_level . " prima di costruire questo edificio."
                ];
            }
        }
        
        // Tutte le dipendenze sono soddisfatte
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Errore nel controllo delle dipendenze degli edifici: " . $e->getMessage());
        return [
            'success' => false,
            'message' => "Errore durante il controllo dei requisiti: " . $e->getMessage()
        ];
    }
}

/**
 * Ottiene una lista di edifici sbloccati per un giocatore
 * @param int $user_id ID dell'utente
 * @return array Lista degli edifici disponibili con indicazione di quali sono sbloccati
 */
function get_unlocked_buildings($user_id) {
    try {
        // Ottieni il livello del giocatore
        $player_level = get_player_level($user_id);
        
        // Ottieni tutti gli edifici disponibili per il livello del giocatore
        $available_buildings = get_available_buildings($player_level['current_level']);
        
        // Per ogni edificio disponibile, controlla se tutte le dipendenze sono soddisfatte
        foreach ($available_buildings as &$building) {
            $result = check_building_dependencies($user_id, $building['building_type_id']);
            $building['is_unlocked'] = $result['success'];
            $building['locked_reason'] = $result['success'] ? null : $result['message'];
            
            // Controlla anche se il giocatore ha già costruito questo edificio
            $built = db_fetch_row("
                SELECT COUNT(*) as count FROM player_buildings
                WHERE user_id = ? AND building_type_id = ? AND is_active = 1
            ", [$user_id, $building['building_type_id']]);
            
            $building['is_built'] = ($built && $built['count'] > 0);
        }
        
        return $available_buildings;
    } catch (PDOException $e) {
        error_log("Errore nell'ottenere gli edifici sbloccati: " . $e->getMessage());
        return [];
    }
}

/**
 * Ottiene l'albero tecnologico completo
 * @return array Albero tecnologico organizzato per livello
 */
function get_tech_tree() {
    try {
        // Ottieni tutti gli edifici
        $buildings = get_all_building_types();
        
        // Per ogni edificio, ottieni le sue dipendenze
        foreach ($buildings as &$building) {
            $building['dependencies'] = get_building_dependencies($building['building_type_id']);
        }
        
        // Raggruppa gli edifici per livello richiesto
        $tech_tree = [];
        foreach ($buildings as $building) {
            $level = $building['level_required'];
            if (!isset($tech_tree[$level])) {
                $tech_tree[$level] = [];
            }
            $tech_tree[$level][] = $building;
        }
        
        return $tech_tree;
    } catch (PDOException $e) {
        error_log("Errore nell'ottenere l'albero tecnologico: " . $e->getMessage());
        return [];
    }
}

/**
 * Aggiorna le risorse del giocatore in base agli edifici attivi
 * @param int $user_id ID del giocatore
 * @return array Risultato dell'operazione con success=true/false, message e production (array con la produzione)
 */
function update_player_resources($user_id) {
    try {
        // Ottieni tutti gli edifici attivi del giocatore
        $buildings = db_fetch_all("
            SELECT pb.building_type_id, pb.quantity, pb.level, 
                   bt.water_production, bt.food_production, bt.wood_production, bt.stone_production
            FROM player_buildings pb
            JOIN building_types bt ON pb.building_type_id = bt.building_type_id
            WHERE pb.user_id = ? AND pb.is_active = 1
        ", [$user_id]);
        
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
        
        // Ottieni le risorse attuali e la capacità massima
        $resources = get_player_resources($user_id);
        $max_capacity = $resources['max_capacity'];
        
        // Aggiorna le risorse del giocatore
        $updated = db_update(
            'player_resources',
            [
                'water' => min($resources['water'] + $total_water, $max_capacity),
                'food' => min($resources['food'] + $total_food, $max_capacity),
                'wood' => min($resources['wood'] + $total_wood, $max_capacity),
                'stone' => min($resources['stone'] + $total_stone, $max_capacity),
                'last_update' => date('Y-m-d H:i:s')
            ],
            'user_id = ?',
            [$user_id]
        );
        
        if (!$updated) {
            return [
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento delle risorse',
                'production' => [
                    'water' => $total_water,
                    'food' => $total_food,
                    'wood' => $total_wood,
                    'stone' => $total_stone
                ]
            ];
        }
        
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
        error_log("Errore durante l'aggiornamento delle risorse: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Errore durante l\'aggiornamento delle risorse: ' . $e->getMessage(),
            'production' => [
                'water' => 0,
                'food' => 0,
                'wood' => 0,
                'stone' => 0
            ]
        ];
    }
}

/**
 * Ottiene le risorse di un giocatore
 * @param int $user_id ID del giocatore
 * @return array Risorse del giocatore
 */
function get_player_resources($user_id) {
    try {
        $resources = db_fetch_row("SELECT * FROM player_resources WHERE user_id = ?", [$user_id]);
        return $resources ?: [
            'water' => 0,
            'food' => 0,
            'wood' => 0,
            'stone' => 0,
            'max_capacity' => 1000
        ];
    } catch (PDOException $e) {
        error_log("Errore nel recupero delle risorse: " . $e->getMessage());
        return [
            'water' => 0,
            'food' => 0,
            'wood' => 0,
            'stone' => 0,
            'max_capacity' => 1000
        ];
    }
}

/**
 * Ottiene il livello di un giocatore
 * @param int $user_id ID del giocatore
 * @return array Livello e punti esperienza del giocatore
 */
function get_player_level($user_id) {
    try {
        $level = db_fetch_row("SELECT * FROM player_levels WHERE user_id = ?", [$user_id]);
        return $level ?: [
            'current_level' => 1,
            'experience_points' => 0,
            'next_level_xp' => 100
        ];
    } catch (PDOException $e) {
        error_log("Errore nel recupero del livello: " . $e->getMessage());
        return [
            'current_level' => 1,
            'experience_points' => 0,
            'next_level_xp' => 100
        ];
    }
}