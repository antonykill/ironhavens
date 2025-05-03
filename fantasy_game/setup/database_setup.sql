-- database_setup.sql - Script di inizializzazione del database Fantasy Game

-- Crea il database se non esiste già
CREATE DATABASE IF NOT EXISTS fantasy_game DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fantasy_game;

-- Elimina le tabelle se esistono già (utile per reinstallare il gioco)
DROP TABLE IF EXISTS game_events;
DROP TABLE IF EXISTS player_buildings;
DROP TABLE IF EXISTS building_types;
DROP TABLE IF EXISTS player_resources;
DROP TABLE IF EXISTS player_levels;
DROP TABLE IF EXISTS users;

-- Tabella utenti per la registrazione e l'autenticazione
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    is_admin BOOLEAN DEFAULT FALSE
) ENGINE=InnoDB;

-- Tabella per i livelli dei giocatori
CREATE TABLE player_levels (
    level_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    current_level INT DEFAULT 1,
    experience_points INT DEFAULT 0,
    next_level_xp INT DEFAULT 100,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabella per le risorse dei giocatori
CREATE TABLE player_resources (
    resource_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    water INT DEFAULT 100,
    food INT DEFAULT 100,
    wood INT DEFAULT 50,
    stone INT DEFAULT 20,
    max_capacity INT DEFAULT 1000,
    last_update DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabella per i tipi di strutture disponibili nel gioco
CREATE TABLE building_types (
    building_type_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    level_required INT DEFAULT 1,
    water_production INT DEFAULT 0,
    food_production INT DEFAULT 0,
    wood_production INT DEFAULT 0,
    stone_production INT DEFAULT 0,
    capacity_increase INT DEFAULT 0,
    water_cost INT DEFAULT 0,
    food_cost INT DEFAULT 0,
    wood_cost INT DEFAULT 0,
    stone_cost INT DEFAULT 0,
    build_time_minutes INT DEFAULT 10,
    upgrade_cost_multiplier FLOAT DEFAULT 1.5,
    max_level INT DEFAULT 5,
    image_url VARCHAR(255)
) ENGINE=InnoDB;

-- Tabella per le strutture costruite dai giocatori
CREATE TABLE player_buildings (
    player_building_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    building_type_id INT NOT NULL,
    quantity INT DEFAULT 1,
    level INT DEFAULT 1,
    construction_started DATETIME DEFAULT CURRENT_TIMESTAMP,
    construction_completed DATETIME,
    is_active BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (building_type_id) REFERENCES building_types(building_type_id)
) ENGINE=InnoDB;

-- Tabella per gli eventi di gioco e log
CREATE TABLE game_events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    event_description TEXT,
    event_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    related_entity_id INT,
    related_entity_type VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Indici per migliorare le performance
CREATE INDEX idx_user_buildings ON player_buildings(user_id);
CREATE INDEX idx_user_resources ON player_resources(user_id);
CREATE INDEX idx_user_events ON game_events(user_id, event_type);
CREATE INDEX idx_building_construction ON player_buildings(construction_completed, is_active);

-- Inserisci dati iniziali per i tipi di edifici
INSERT INTO building_types (name, description, level_required, water_production, food_production, wood_production, stone_production, capacity_increase, water_cost, food_cost, wood_cost, stone_cost, build_time_minutes, image_url) VALUES
('Pozzo d\'acqua', 'Un pozzo che fornisce acqua fresca al tuo villaggio. Essenziale per la sopravvivenza e la crescita della popolazione.', 1, 10, 0, 0, 0, 200, 0, 10, 30, 15, 5, 'images/buildings/water_well.png'),
('Fattoria', 'Un campo fertile per coltivare cibo. Ogni fattoria aumenta notevolmente la produzione alimentare del tuo villaggio.', 1, 0, 10, 0, 0, 200, 20, 0, 40, 10, 8, 'images/buildings/farm.png'),
('Segheria', 'Una struttura per tagliare e lavorare il legno. Aumenta significativamente la raccolta del legno dal bosco circostante.', 2, 0, 0, 15, 0, 200, 30, 30, 0, 25, 12, 'images/buildings/sawmill.png'),
('Cava', 'Un sito per l\'estrazione di pietra. Le cave forniscono il materiale necessario per costruzioni più avanzate.', 3, 0, 0, 0, 12, 200, 40, 40, 60, 0, 15, 'images/buildings/quarry.png'),
('Cisterna', 'Un serbatoio per conservare maggiori quantità di acqua. Aumenta la capacità di immagazzinamento e fornisce un piccolo bonus alla produzione.', 2, 5, 0, 0, 0, 500, 50, 0, 30, 30, 10, 'images/buildings/cistern.png'),
('Granaio', 'Un edificio per conservare cibo più a lungo. Aumenta la capacità di immagazzinamento del cibo e riduce gli sprechi.', 2, 0, 5, 0, 0, 500, 0, 50, 40, 20, 10, 'images/buildings/granary.png'),
('Magazzino', 'Un edificio per conservare risorse varie. Aumenta la capacità di stoccaggio per legno e pietra.', 3, 0, 0, 5, 5, 500, 30, 30, 60, 60, 15, 'images/buildings/warehouse.png'),
('Mulino ad acqua', 'Sfrutta la potenza dell\'acqua per macinare il grano. Migliora l\'efficienza della produzione di cibo.', 3, 0, 20, 0, 0, 0, 80, 30, 50, 40, 20, 'images/buildings/watermill.png'),
('Torre di guardia', 'Protegge il villaggio dagli intrusi. Necessaria per espandere il territorio e costruire edifici di livello superiore.', 4, 0, 0, 0, 0, 0, 50, 50, 100, 80, 25, 'images/buildings/watchtower.png'),
('Mercato', 'Centro di commercio per il tuo villaggio. Sblocca la possibilità di commerciare risorse con altri villaggi.', 4, 0, 0, 0, 0, 1000, 70, 70, 120, 120, 30, 'images/buildings/market.png'),
('Accademia', 'Centro di apprendimento del villaggio. Permette lo sviluppo di tecnologie avanzate e fornisce bonus all\'esperienza.', 5, 0, 0, 0, 0, 0, 100, 100, 150, 150, 40, 'images/buildings/academy.png'),
('Castello', 'Un imponente fortezza che segna il tuo dominio. Fornisce bonus a tutte le produzioni e aumenta il limite massimo di edifici.', 5, 10, 10, 10, 10, 2000, 200, 200, 400, 600, 60, 'images/buildings/castle.png');

-- Trigger per aggiornare le risorse del giocatore quando una costruzione viene completata
DELIMITER //
CREATE TRIGGER after_construction_complete
AFTER UPDATE ON player_buildings
FOR EACH ROW
BEGIN
    DECLARE water_prod INT;
    DECLARE food_prod INT;
    DECLARE wood_prod INT;
    DECLARE stone_prod INT;
    DECLARE capacity_inc INT;
    
    IF NEW.is_active = TRUE AND OLD.is_active = FALSE THEN
        -- Ottieni la produzione e capacità dell'edificio
        SELECT water_production, food_production, wood_production, stone_production, capacity_increase
        INTO water_prod, food_prod, wood_prod, stone_prod, capacity_inc
        FROM building_types
        WHERE building_type_id = NEW.building_type_id;
        
        -- Aggiorna le risorse del giocatore
        UPDATE player_resources
        SET water = water + (water_prod * NEW.quantity * NEW.level),
            food = food + (food_prod * NEW.quantity * NEW.level),
            wood = wood + (wood_prod * NEW.quantity * NEW.level),
            stone = stone + (stone_prod * NEW.quantity * NEW.level),
            max_capacity = max_capacity + (capacity_inc * NEW.quantity * NEW.level),
            last_update = CURRENT_TIMESTAMP
        WHERE user_id = NEW.user_id;
        
        -- Aggiungi esperienza al giocatore
        UPDATE player_levels
        SET experience_points = experience_points + (XP_PER_BUILDING * NEW.level)
        WHERE user_id = NEW.user_id;
        
        -- Registra l'evento
        INSERT INTO game_events (
            user_id, 
            event_type, 
            event_description,
            related_entity_id,
            related_entity_type
        )
        VALUES (
            NEW.user_id, 
            'BUILDING_COMPLETED', 
            CONCAT('Edificio completato: ', 
                (SELECT name FROM building_types WHERE building_type_id = NEW.building_type_id), 
                ' livello ', NEW.level),
            NEW.player_building_id,
            'player_building'
        );
    END IF;
END //
DELIMITER ;

-- Trigger per controllare se il giocatore sale di livello
DELIMITER //
CREATE TRIGGER check_level_up
AFTER UPDATE ON player_levels
FOR EACH ROW
BEGIN
    IF NEW.experience_points >= NEW.next_level_xp AND NEW.experience_points > OLD.experience_points THEN
        -- Calcola il nuovo livello e l'XP richiesta per il livello successivo
        UPDATE player_levels
        SET current_level = current_level + 1,
            next_level_xp = next_level_xp + (BASE_XP_PER_LEVEL * (current_level + 1))
        WHERE level_id = NEW.level_id;
        
        -- Registra l'evento
        INSERT INTO game_events (
            user_id, 
            event_type, 
            event_description
        )
        VALUES (
            NEW.user_id, 
            'LEVEL_UP', 
            CONCAT('Sei salito al livello ', NEW.current_level + 1, '!')
        );
    END IF;
END //
DELIMITER ;

-- Aggiunge un utente amministratore (username: admin, password: admin123)
INSERT INTO users (username, email, password_hash, is_admin) 
VALUES ('admin', 'admin@example.com', '$2y$10$rRUaDf/3vVr3FAXMOZmySOClnZ45Mwpw/62l2RNj.zp0njrPZYK.K', TRUE);

-- Crea record per il livello dell'amministratore
INSERT INTO player_levels (user_id, current_level, experience_points, next_level_xp) 
VALUES (1, 10, 5000, 1100);

-- Crea record per le risorse dell'amministratore
INSERT INTO player_resources (user_id, water, food, wood, stone, max_capacity) 
VALUES (1, 10000, 10000, 10000, 10000, 20000);

-- Procedure stored per l'aggiornamento periodico delle risorse (da chiamare tramite cron job)
DELIMITER //
CREATE PROCEDURE update_all_resources()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE user_id_var INT;
    DECLARE cur CURSOR FOR SELECT user_id FROM users WHERE is_active = TRUE;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO user_id_var;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Calcola la produzione totale per l'utente
        SELECT 
            SUM(bt.water_production * pb.quantity * pb.level) as total_water,
            SUM(bt.food_production * pb.quantity * pb.level) as total_food,
            SUM(bt.wood_production * pb.quantity * pb.level) as total_wood,
            SUM(bt.stone_production * pb.quantity * pb.level) as total_stone
        INTO @water_prod, @food_prod, @wood_prod, @stone_prod
        FROM player_buildings pb
        JOIN building_types bt ON pb.building_type_id = bt.building_type_id
        WHERE pb.user_id = user_id_var AND pb.is_active = TRUE;
        
        -- Aggiorna le risorse del giocatore
        UPDATE player_resources
        SET water = LEAST(water + IFNULL(@water_prod, 0), max_capacity),
            food = LEAST(food + IFNULL(@food_prod, 0), max_capacity),
            wood = LEAST(wood + IFNULL(@wood_prod, 0), max_capacity),
            stone = LEAST(stone + IFNULL(@stone_prod, 0), max_capacity),
            last_update = CURRENT_TIMESTAMP
        WHERE user_id = user_id_var;
    END LOOP;
    
    CLOSE cur;
END //
DELIMITER ;

-- Visualizzazioni per semplificare le query complesse
CREATE VIEW view_player_stats AS
SELECT 
    u.user_id,
    u.username,
    pl.current_level,
    pl.experience_points,
    pl.next_level_xp,
    pr.water,
    pr.food,
    pr.wood,
    pr.stone,
    pr.max_capacity,
    COUNT(DISTINCT pb.player_building_id) AS total_buildings,
    SUM(pb.quantity) AS total_building_quantity
FROM 
    users u
    JOIN player_levels pl ON u.user_id = pl.user_id
    JOIN player_resources pr ON u.user_id = pr.user_id
    LEFT JOIN player_buildings pb ON u.user_id = pb.user_id AND pb.is_active = TRUE
GROUP BY
    u.user_id, u.username, pl.current_level, pl.experience_points, 
    pl.next_level_xp, pr.water, pr.food, pr.wood, pr.stone, pr.max_capacity;

-- Tabella delle costanti di gioco
CREATE TABLE game_constants (
    constant_name VARCHAR(50) PRIMARY KEY,
    constant_value VARCHAR(255) NOT NULL,
    description TEXT
) ENGINE=InnoDB;

-- Inserisci alcune costanti di gioco
INSERT INTO game_constants (constant_name, constant_value, description) VALUES
('XP_PER_BUILDING', '20', 'Punti esperienza guadagnati per ogni edificio costruito'),
('BASE_XP_PER_LEVEL', '100', 'XP di base necessaria per salire di livello'),
('RESOURCE_UPDATE_INTERVAL', '60', 'Intervallo in secondi per l\'aggiornamento automatico delle risorse'),
('MAX_RESOURCE_CAPACITY', '1000', 'Capacità massima di risorse senza edifici speciali'),
('BUILD_TIME_MULTIPLIER', '1.0', 'Moltiplicatore per il tempo di costruzione degli edifici');

-- Finalizzazione
SELECT 'Database fantasy_game inizializzato con successo!' AS Result;