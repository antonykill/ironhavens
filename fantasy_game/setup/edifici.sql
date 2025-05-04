-- Crea una nuova tabella per le dipendenze degli edifici
CREATE TABLE IF NOT EXISTS building_dependencies (
    dependency_id INT AUTO_INCREMENT PRIMARY KEY,
    building_type_id INT NOT NULL,
    required_building_id INT NOT NULL,
    required_building_level INT DEFAULT 1,
    FOREIGN KEY (building_type_id) REFERENCES building_types(building_type_id) ON DELETE CASCADE,
    FOREIGN KEY (required_building_id) REFERENCES building_types(building_type_id) ON DELETE CASCADE
);

-- Popola con le dipendenze basate sul diagramma
-- Mulino ad acqua richiede Pozzo d'acqua
INSERT INTO building_dependencies (building_type_id, required_building_id, required_building_level) 
VALUES (8, 1, 1); -- ID 8 = Mulino ad acqua, ID 1 = Pozzo d'acqua

-- Mulino ad acqua richiede anche Fattoria
INSERT INTO building_dependencies (building_type_id, required_building_id, required_building_level) 
VALUES (8, 2, 1); -- ID 8 = Mulino ad acqua, ID 2 = Fattoria

-- Magazzino richiede Segheria
INSERT INTO building_dependencies (building_type_id, required_building_id, required_building_level) 
VALUES (7, 3, 1); -- ID 7 = Magazzino, ID 3 = Segheria

-- Magazzino richiede anche Cava
INSERT INTO building_dependencies (building_type_id, required_building_id, required_building_level) 
VALUES (7, 4, 1); -- ID 7 = Magazzino, ID 4 = Cava

-- Torre di guardia richiede Cava
INSERT INTO building_dependencies (building_type_id, required_building_id, required_building_level) 
VALUES (9, 4, 2); -- ID 9 = Torre di guardia, ID 4 = Cava livello 2

-- Mercato richiede Magazzino
INSERT INTO building_dependencies (building_type_id, required_building_id, required_building_level) 
VALUES (10, 7, 1); -- ID 10 = Mercato, ID 7 = Magazzino

-- Accademia richiede Torre di guardia
INSERT INTO building_dependencies (building_type_id, required_building_id, required_building_level) 
VALUES (11, 9, 1); -- ID 11 = Accademia, ID 9 = Torre di guardia

-- Castello richiede Mercato
INSERT INTO building_dependencies (building_type_id, required_building_id, required_building_level) 
VALUES (12, 10, 2); -- ID 12 = Castello, ID 10 = Mercato livello 2

-- Cisterna richiede Pozzo d'acqua
INSERT INTO building_dependencies (building_type_id, required_building_id, required_building_level) 
VALUES (5, 1, 2); -- ID 5 = Cisterna, ID 1 = Pozzo d'acqua livello 2

-- Granaio richiede Fattoria
INSERT INTO building_dependencies (building_type_id, required_building_id, required_building_level) 
VALUES (6, 2, 2); -- ID 6 = Granaio, ID 2 = Fattoria livello 2