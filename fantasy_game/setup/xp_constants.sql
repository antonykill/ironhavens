-- Aggiorna o aggiungi le costanti di gioco per il sistema XP
INSERT INTO game_constants (constant_name, constant_value, description) 
VALUES 
('XP_PER_BUILDING', '20', 'Punti esperienza guadagnati per ogni edificio costruito'),
('BASE_XP_PER_LEVEL', '100', 'XP di base necessaria per salire di livello'),
('DAILY_LOGIN_XP', '10', 'Punti esperienza guadagnati per il login giornaliero')
ON DUPLICATE KEY UPDATE
constant_value = VALUES(constant_value),
description = VALUES(description);