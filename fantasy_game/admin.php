<?php
// Includi le funzioni di gioco
require_once 'backend.php';

// Verifica se l'utente è loggato e se è un amministratore
$logged_in = is_logged_in();
if (!$logged_in) {
    // Reindirizza alla pagina principale se non è loggato
    header('Location: index.php');
    exit;
}

// Ottieni l'ID e i dati dell'utente
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;

// Verifica se l'utente è un amministratore
$is_admin = false;
try {
    $pdo = db_connect();
    $stmt = $pdo->prepare('SELECT is_admin FROM users WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $is_admin = (bool) $stmt->fetchColumn();
} catch (PDOException $e) {
    // Gestisce l'errore
    error_log("Errore nell'accesso al database: " . $e->getMessage());
}

// Se l'utente non è un amministratore, reindirizza alla pagina principale
if (!$is_admin) {
    header('Location: index.php');
    exit;
}

// Gestisci le diverse azioni amministrative
$action = $_GET['action'] ?? 'dashboard';
$message = '';
$error = '';

// Gestione dell'eliminazione dell'utente
if (isset($_GET['delete_user']) && is_numeric($_GET['delete_user'])) {
    $delete_id = (int) $_GET['delete_user'];
    if ($delete_id !== $user_id) { // Impedisce all'amministratore di eliminare se stesso
        try {
            $pdo = db_connect();
            // Elimina tutti i dati correlati (a causa dei vincoli di chiave esterna)
            $pdo->beginTransaction();
            
            // Elimina gli edifici dell'utente
            $stmt = $pdo->prepare('DELETE FROM player_buildings WHERE user_id = ?');
            $stmt->execute([$delete_id]);
            
            // Elimina le risorse dell'utente
            $stmt = $pdo->prepare('DELETE FROM player_resources WHERE user_id = ?');
            $stmt->execute([$delete_id]);
            
            // Elimina i livelli dell'utente
            $stmt = $pdo->prepare('DELETE FROM player_levels WHERE user_id = ?');
            $stmt->execute([$delete_id]);
            
            // Elimina gli eventi dell'utente
            $stmt = $pdo->prepare('DELETE FROM game_events WHERE user_id = ?');
            $stmt->execute([$delete_id]);
            
            // Elimina l'utente
            $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = ?');
            $stmt->execute([$delete_id]);
            
            $pdo->commit();
            $message = "Utente eliminato con successo";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Errore durante l'eliminazione dell'utente: " . $e->getMessage();
        }
    } else {
        $error = "Non puoi eliminare il tuo account!";
    }
}

// Gestione dell'aggiornamento delle impostazioni del gioco
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
        $pdo = db_connect();
        
        // Aggiorna le costanti di gioco
        foreach ($_POST['constants'] as $constant => $value) {
            $stmt = $pdo->prepare('UPDATE game_constants SET constant_value = ? WHERE constant_name = ?');
            $stmt->execute([$value, $constant]);
        }
        
        $message = "Impostazioni aggiornate con successo";
    } catch (PDOException $e) {
        $error = "Errore durante l'aggiornamento delle impostazioni: " . $e->getMessage();
    }
}

// Gestione dell'aggiunta di un nuovo tipo di edificio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_building'])) {
    try {
        $pdo = db_connect();
        
        $stmt = $pdo->prepare('
            INSERT INTO building_types (
                name, description, level_required, 
                water_production, food_production, wood_production, stone_production, 
                capacity_increase,
                water_cost, food_cost, wood_cost, stone_cost, 
                build_time_minutes, image_url
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $_POST['name'],
            $_POST['description'],
            $_POST['level_required'],
            $_POST['water_production'],
            $_POST['food_production'],
            $_POST['wood_production'],
            $_POST['stone_production'],
            $_POST['capacity_increase'],
            $_POST['water_cost'],
            $_POST['food_cost'],
            $_POST['wood_cost'],
            $_POST['stone_cost'],
            $_POST['build_time_minutes'],
            $_POST['image_url']
        ]);
        
        $message = "Nuovo tipo di edificio aggiunto con successo";
    } catch (PDOException $e) {
        $error = "Errore durante l'aggiunta del nuovo edificio: " . $e->getMessage();
    }
}

// Gestione della modifica del tipo di edificio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_building'])) {
    try {
        $pdo = db_connect();
        
        $stmt = $pdo->prepare('
            UPDATE building_types SET 
                name = ?, 
                description = ?, 
                level_required = ?, 
                water_production = ?, 
                food_production = ?, 
                wood_production = ?, 
                stone_production = ?, 
                capacity_increase = ?,
                water_cost = ?, 
                food_cost = ?, 
                wood_cost = ?, 
                stone_cost = ?, 
                build_time_minutes = ?, 
                image_url = ?
            WHERE building_type_id = ?
        ');
        
        $stmt->execute([
            $_POST['name'],
            $_POST['description'],
            $_POST['level_required'],
            $_POST['water_production'],
            $_POST['food_production'],
            $_POST['wood_production'],
            $_POST['stone_production'],
            $_POST['capacity_increase'],
            $_POST['water_cost'],
            $_POST['food_cost'],
            $_POST['wood_cost'],
            $_POST['stone_cost'],
            $_POST['build_time_minutes'],
            $_POST['image_url'],
            $_POST['building_id']
        ]);
        
        $message = "Edificio aggiornato con successo";
    } catch (PDOException $e) {
        $error = "Errore durante l'aggiornamento dell'edificio: " . $e->getMessage();
    }
}

// Gestione backup del database
// Sostituisci questo codice nel file admin.php
// Trova la parte relativa al backup del database (circa alla linea 199)
// e sostituiscila con questo codice alternativo

// Gestione backup del database
if (isset($_GET['action']) && $_GET['action'] === 'backup') {
    try {
        // Ottieni connessione al database
        $pdo = db_connect();
        
        // Crea directory per il backup se non esiste
        $backup_dir = 'backup/';
        if (!is_dir($backup_dir)) {
            if (!mkdir($backup_dir, 0755, true)) {
                throw new Exception("Impossibile creare la directory di backup");
            }
        }
        
        // Nome file con timestamp
        $backup_file = $backup_dir . 'ironhaven_backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Apri il file per la scrittura
        $file_handle = fopen($backup_file, 'w');
        if (!$file_handle) {
            throw new Exception("Impossibile creare il file di backup");
        }
        
        // Scrivi intestazione
        fwrite($file_handle, "-- Backup di " . DB_NAME . " - " . date('Y-m-d H:i:s') . "\n\n");
        
        // Ottieni tutte le tabelle
        $tables = array();
        $result = $pdo->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        // Per ogni tabella, scrivi la struttura e i dati
        foreach ($tables as $table) {
            // Ottieni struttura tabella
            fwrite($file_handle, "-- Struttura tabella `$table`\n");
            fwrite($file_handle, "DROP TABLE IF EXISTS `$table`;\n");
            
            $result = $pdo->query("SHOW CREATE TABLE `$table`");
            $row = $result->fetch(PDO::FETCH_NUM);
            fwrite($file_handle, $row[1] . ";\n\n");
            
            // Ottieni dati tabella
            $result = $pdo->query("SELECT * FROM `$table`");
            $column_count = $result->columnCount();
            
            if ($result->rowCount() > 0) {
                fwrite($file_handle, "-- Dati tabella `$table`\n");
                fwrite($file_handle, "INSERT INTO `$table` VALUES\n");
                
                $first_row = true;
                while ($row = $result->fetch(PDO::FETCH_NUM)) {
                    if (!$first_row) {
                        fwrite($file_handle, ",\n");
                    } else {
                        $first_row = false;
                    }
                    
                    fwrite($file_handle, "(");
                    for ($i = 0; $i < $column_count; $i++) {
                        if ($i > 0) {
                            fwrite($file_handle, ", ");
                        }
                        
                        if ($row[$i] === null) {
                            fwrite($file_handle, "NULL");
                        } else {
                            $value = str_replace("'", "''", $row[$i]);
                            fwrite($file_handle, "'$value'");
                        }
                    }
                    fwrite($file_handle, ")");
                }
                fwrite($file_handle, ";\n\n");
            }
        }
        
        // Chiudi il file
        fclose($file_handle);
        
        $message = "Backup del database creato con successo: " . $backup_file;
    } catch (Exception $e) {
        $error = "Errore durante il backup del database: " . $e->getMessage();
    }
}

// Funzione per ottenere tutti gli utenti
function get_all_users() {
    try {
        $pdo = db_connect();
        $stmt = $pdo->query('
            SELECT u.*, pl.current_level, pr.water, pr.food, pr.wood, pr.stone
            FROM users u
            LEFT JOIN player_levels pl ON u.user_id = pl.user_id
            LEFT JOIN player_resources pr ON u.user_id = pr.user_id
            ORDER BY u.registration_date DESC
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Errore nell'ottenere gli utenti: " . $e->getMessage());
        return [];
    }
}

// Funzione per ottenere tutte le costanti di gioco
function get_game_constants() {
    try {
        $pdo = db_connect();
        $stmt = $pdo->query('SELECT * FROM game_constants ORDER BY constant_name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Errore nell'ottenere le costanti di gioco: " . $e->getMessage());
        return [];
    }
}

// Funzione per ottenere tutti i tipi di edifici
function get_all_building_types() {
    try {
        $pdo = db_connect();
        $stmt = $pdo->query('SELECT * FROM building_types ORDER BY level_required, name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Errore nell'ottenere i tipi di edifici: " . $e->getMessage());
        return [];
    }
}

// Funzione per ottenere statistiche di gioco
function get_game_stats() {
    try {
        $pdo = db_connect();
        
        // Totale utenti
        $stmt = $pdo->query('SELECT COUNT(*) FROM users');
        $total_users = $stmt->fetchColumn();
        
        // Utenti attivi nell'ultima settimana
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
        $stmt->execute();
        $active_users = $stmt->fetchColumn();
        
        // Totale edifici costruiti
        $stmt = $pdo->query('SELECT COUNT(*) FROM player_buildings WHERE is_active = TRUE');
        $total_buildings = $stmt->fetchColumn();
        
        // Media edifici per utente
        if ($total_users > 0) {
            $avg_buildings = $total_buildings / $total_users;
        } else {
            $avg_buildings = 0;
        }
        
        // Livello medio degli utenti
        $stmt = $pdo->query('SELECT AVG(current_level) FROM player_levels');
        $avg_level = $stmt->fetchColumn();
        
        // Edificio più costruito
        $stmt = $pdo->query('
            SELECT bt.name, COUNT(*) as count
            FROM player_buildings pb
            JOIN building_types bt ON pb.building_type_id = bt.building_type_id
            WHERE pb.is_active = TRUE
            GROUP BY pb.building_type_id
            ORDER BY count DESC
            LIMIT 1
        ');
        $top_building = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_users' => $total_users,
            'active_users' => $active_users,
            'total_buildings' => $total_buildings,
            'avg_buildings' => $avg_buildings,
            'avg_level' => $avg_level,
            'top_building' => $top_building
        ];
    } catch (PDOException $e) {
        error_log("Errore nell'ottenere le statistiche di gioco: " . $e->getMessage());
        return [
            'total_users' => 0,
            'active_users' => 0,
            'total_buildings' => 0,
            'avg_buildings' => 0,
            'avg_level' => 0,
            'top_building' => ['name' => 'N/A', 'count' => 0]
        ];
    }
}

// Ottieni i dati necessari in base all'azione
$users = [];
$constants = [];
$building_types = [];
$stats = [];
$building_to_edit = null;

switch ($action) {
    case 'users':
        $users = get_all_users();
        break;
        
    case 'settings':
        $constants = get_game_constants();
        break;
        
    case 'buildings':
        $building_types = get_all_building_types();
        break;
        
    case 'edit_building':
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $building_id = (int) $_GET['id'];
            try {
                $pdo = db_connect();
                $stmt = $pdo->prepare('SELECT * FROM building_types WHERE building_type_id = ?');
                $stmt->execute([$building_id]);
                $building_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $error = "Errore nel recupero dei dati dell'edificio: " . $e->getMessage();
            }
        }
        break;
        
    case 'dashboard':
    default:
        $stats = get_game_stats();
        break;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pannello Amministrativo - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1><i class="fas fa-crown"></i> Pannello Amministrativo</h1>
            <div class="admin-user-info">
                <span class="welcome">Admin: <strong><?php echo htmlspecialchars($username); ?></strong></span>
                <a href="index.php" class="admin-btn">Torna al Gioco</a>
                <a href="#" class="admin-btn logout-btn" id="logout-btn">Logout</a>
            </div>
        </header>
        
        <?php if ($message): ?>
        <div class="admin-message success">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="admin-message error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <div class="admin-content">
            <div class="admin-sidebar">
                <ul class="admin-menu">
                    <li class="<?php echo $action === 'dashboard' ? 'active' : ''; ?>">
                        <a href="admin.php?action=dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    </li>
                    <li class="<?php echo $action === 'users' ? 'active' : ''; ?>">
                        <a href="admin.php?action=users"><i class="fas fa-users"></i> Utenti</a>
                    </li>
                    <li class="<?php echo $action === 'buildings' || $action === 'edit_building' ? 'active' : ''; ?>">
                        <a href="admin.php?action=buildings"><i class="fas fa-building"></i> Edifici</a>
                    </li>
                    <li class="<?php echo $action === 'settings' ? 'active' : ''; ?>">
                        <a href="admin.php?action=settings"><i class="fas fa-cogs"></i> Impostazioni</a>
                    </li>
                    <li>
                        <a href="admin.php?action=backup"><i class="fas fa-database"></i> Backup Database</a>
                    </li>
                </ul>
            </div>
            
            <div class="admin-main">
                <?php if ($action === 'dashboard'): ?>
                <div class="admin-section">
                    <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                            <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                            <div class="stat-label">Utenti Totali</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
                            <div class="stat-value"><?php echo $stats['active_users']; ?></div>
                            <div class="stat-label">Utenti Attivi (7 giorni)</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-building"></i></div>
                            <div class="stat-value"><?php echo $stats['total_buildings']; ?></div>
                            <div class="stat-label">Edifici Costruiti</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                            <div class="stat-value"><?php echo number_format($stats['avg_buildings'], 1); ?></div>
                            <div class="stat-label">Media Edifici/Utente</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-star"></i></div>
                            <div class="stat-value"><?php echo number_format($stats['avg_level'], 1); ?></div>
                            <div class="stat-label">Livello Medio</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-trophy"></i></div>
                            <div class="stat-value"><?php echo isset($stats['top_building']['name']) ? htmlspecialchars($stats['top_building']['name']) : 'N/A'; ?></div>
                            <div class="stat-label">Edificio Più Popolare</div>
                        </div>
                    </div>
                    
                    <div class="admin-quick-links">
                        <h3>Azioni Rapide</h3>
                        <div class="quick-links-grid">
                            <a href="admin.php?action=users" class="quick-link-card">
                                <i class="fas fa-users"></i>
                                <span>Gestisci Utenti</span>
                            </a>
                            <a href="admin.php?action=buildings" class="quick-link-card">
                                <i class="fas fa-building"></i>
                                <span>Gestisci Edifici</span>
                            </a>
                            <a href="admin.php?action=settings" class="quick-link-card">
                                <i class="fas fa-cogs"></i>
                                <span>Impostazioni</span>
                            </a>
                            <a href="admin.php?action=backup" class="quick-link-card">
                                <i class="fas fa-database"></i>
                                <span>Backup Database</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <?php elseif ($action === 'users'): ?>
                <div class="admin-section">
                    <h2><i class="fas fa-users"></i> Gestione Utenti</h2>
                    
                    <div class="admin-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Registrato il</th>
                                    <th>Ultimo Login</th>
                                    <th>Livello</th>
                                    <th>Risorsa Alta</th>
                                    <th>Stato</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['user_id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($user['username']); ?>
                                        <?php if ($user['is_admin']): ?>
                                            <span class="admin-badge"><i class="fas fa-crown"></i> Admin</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($user['registration_date'])); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($user['last_login'])); ?></td>
                                    <td><?php echo $user['current_level']; ?></td>
                                    <td>
                                        <?php
                                        $resources = [
                                            'water' => $user['water'] ?? 0,
                                            'food' => $user['food'] ?? 0,
                                            'wood' => $user['wood'] ?? 0,
                                            'stone' => $user['stone'] ?? 0
                                        ];
                                        arsort($resources);
                                        $top_resource = key($resources);
                                        $icons = [
                                            'water' => '<i class="fas fa-tint water-icon"></i>',
                                            'food' => '<i class="fas fa-drumstick-bite food-icon"></i>',
                                            'wood' => '<i class="fas fa-tree wood-icon"></i>',
                                            'stone' => '<i class="fas fa-mountain stone-icon"></i>'
                                        ];
                                        echo $icons[$top_resource] . ' ' . $resources[$top_resource];
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($user['is_active']): ?>
                                            <span class="status-active"><i class="fas fa-check-circle"></i> Attivo</span>
                                        <?php else: ?>
                                            <span class="status-inactive"><i class="fas fa-times-circle"></i> Inattivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                        <a href="#" class="edit-user-btn" data-id="<?php echo $user['user_id']; ?>" title="Modifica">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <?php if (!$user['is_admin']): // Non mostra il pulsante elimina per gli admin ?>
                                        <a href="admin.php?action=users&delete_user=<?php echo $user['user_id']; ?>" 
                                           class="delete-user-btn" 
                                           data-id="<?php echo $user['user_id']; ?>"
                                           data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                           title="Elimina"
                                           onclick="return confirm('Sei sicuro di voler eliminare l\'utente <?php echo htmlspecialchars($user['username']); ?>? Questa azione non può essere annullata!');">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <?php elseif ($action === 'settings'): ?>
                <div class="admin-section">
                    <h2><i class="fas fa-cogs"></i> Impostazioni del Gioco</h2>
                    
                    <form action="admin.php?action=settings" method="post" class="admin-form">
                        <div class="form-grid">
                            <?php foreach ($constants as $constant): ?>
                            <div class="form-group">
                                <label for="<?php echo $constant['constant_name']; ?>">
                                    <?php echo htmlspecialchars($constant['constant_name']); ?>
                                    <span class="form-help" title="<?php echo htmlspecialchars($constant['description']); ?>">
                                        <i class="fas fa-question-circle"></i>
                                    </span>
                                </label>
                                <input type="text" 
                                       id="<?php echo $constant['constant_name']; ?>" 
                                       name="constants[<?php echo $constant['constant_name']; ?>]" 
                                       value="<?php echo htmlspecialchars($constant['constant_value']); ?>"
                                       required>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="submit" name="update_settings" class="admin-btn primary-btn">
                                <i class="fas fa-save"></i> Salva Impostazioni
                            </button>
                        </div>
                    </form>
                </div>
                
                <?php elseif ($action === 'buildings'): ?>
                <div class="admin-section">
                    <h2><i class="fas fa-building"></i> Gestione Edifici</h2>
                    
                    <div class="admin-tabs">
                        <button class="admin-tab-btn active" data-tab="buildings-list">Lista Edifici</button>
                        <button class="admin-tab-btn" data-tab="add-building">Aggiungi Nuovo Edificio</button>
                    </div>
                    
                    <div class="admin-tab-content active" id="buildings-list-tab">
                        <div class="admin-table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>Livello Richiesto</th>
                                        <th>Produzione</th>
                                        <th>Costi</th>
                                        <th>Tempo (min)</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($building_types as $building): ?>
                                    <tr>
                                        <td><?php echo $building['building_type_id']; ?></td>
                                        <td><?php echo htmlspecialchars($building['name']); ?></td>
                                        <td><?php echo $building['level_required']; ?></td>
                                        <td>
                                            <?php if ($building['water_production'] > 0): ?>
                                                <i class="fas fa-tint water-icon"></i> <?php echo $building['water_production']; ?>
                                            <?php endif; ?>
                                            
                                            <?php if ($building['food_production'] > 0): ?>
                                                <i class="fas fa-drumstick-bite food-icon"></i> <?php echo $building['food_production']; ?>
                                            <?php endif; ?>
                                            
                                            <?php if ($building['wood_production'] > 0): ?>
                                                <i class="fas fa-tree wood-icon"></i> <?php echo $building['wood_production']; ?>
                                            <?php endif; ?>
                                            
                                            <?php if ($building['stone_production'] > 0): ?>
                                                <i class="fas fa-mountain stone-icon"></i> <?php echo $building['stone_production']; ?>
                                            <?php endif; ?>
                                            
                                            <?php if ($building['capacity_increase'] > 0): ?>
                                                <i class="fas fa-warehouse"></i> <?php echo $building['capacity_increase']; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($building['water_cost'] > 0): ?>
                                                <i class="fas fa-tint water-icon"></i> <?php echo $building['water_cost']; ?>
                                            <?php endif; ?>
                                            
                                            <?php if ($building['food_cost'] > 0): ?>
                                                <i class="fas fa-drumstick-bite food-icon"></i> <?php echo $building['food_cost']; ?>
                                            <?php endif; ?>
                                            
                                            <?php if ($building['wood_cost'] > 0): ?>
                                                <i class="fas fa-tree wood-icon"></i> <?php echo $building['wood_cost']; ?>
                                            <?php endif; ?>
                                            
                                            <?php if ($building['stone_cost'] > 0): ?>
                                                <i class="fas fa-mountain stone-icon"></i> <?php echo $building['stone_cost']; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $building['build_time_minutes']; ?></td>
                                        <td class="actions">
                                            <a href="admin.php?action=edit_building&id=<?php echo $building['building_type_id']; ?>" class="edit-building-btn" title="Modifica">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" class="view-building-btn" data-id="<?php echo $building['building_type_id']; ?>" title="Visualizza Dettagli">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="admin-tab-content" id="add-building-tab">
                        <form action="admin.php?action=buildings" method="post" class="admin-form">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="name">Nome Edificio</label>
                                    <input type="text" id="name" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label for="description">Descrizione</label>
                                    <textarea id="description" name="description" rows="3"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="level_required">Livello Richiesto</label>
                                    <input type="number" id="level_required" name="level_required" min="1" value="1" required>
                                </div>
                                <div class="form-group">
                                    <label for="water_production">Produzione Acqua</label>
                                    <input type="number" id="water_production" name="water_production" min="0" value="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="food_production">Produzione Cibo</label>
                                    <input type="number" id="food_production" name="food_production" min="0" value="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="wood_production">Produzione Legno</label>
                                    <input type="number" id="wood_production" name="wood_production" min="0" value="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="stone_production">Produzione Pietra</label>
                                    <input type="number" id="stone_production" name="stone_production" min="0" value="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="capacity_increase">Aumento Capacità</label>
                                    <input type="number" id="capacity_increase" name="capacity_increase" min="0" value="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="water_cost">Costo Acqua</label>
                                    <input type="number" id="water_cost" name="water_cost" min="0" value="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="food_cost">Costo Cibo</label>
                                    <input type="number" id="food_cost" name="food_cost" min="0" value="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="wood_cost">Costo Legno</label>
                                    <input type="number" id="wood_cost" name="wood_cost" min="0" value="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="stone_cost">Costo Pietra</label>
                                    <input type="number" id="stone_cost" name="stone_cost" min="0" value="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="build_time_minutes">Tempo di Costruzione (minuti)</label>
                                    <input type="number" id="build_time_minutes" name="build_time_minutes" min="1" value="10" required>
                                </div>
                                <div class="form-group">
                                    <label for="image_url">URL Immagine</label>
                                    <input type="text" id="image_url" name="image_url" value="images/buildings/placeholder.png">
                                </div>
                            </div>
                            
                            <div class="form-buttons">
                                <button type="submit" name="add_building" class="admin-btn primary-btn">
                                    <i class="fas fa-plus"></i> Aggiungi Edificio
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php elseif ($action === 'edit_building' && $building_to_edit): ?>
                <div class="admin-section">
                    <h2><i class="fas fa-edit"></i> Modifica Edificio</h2>
                    
                    <form action="admin.php?action=buildings" method="post" class="admin-form">
                        <input type="hidden" name="building_id" value="<?php echo $building_to_edit['building_type_id']; ?>">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name">Nome Edificio</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($building_to_edit['name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="description">Descrizione</label>
                                <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($building_to_edit['description']); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="level_required">Livello Richiesto</label>
                                <input type="number" id="level_required" name="level_required" min="1" value="<?php echo $building_to_edit['level_required']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="water_production">Produzione Acqua</label>
                                <input type="number" id="water_production" name="water_production" min="0" value="<?php echo $building_to_edit['water_production']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="food_production">Produzione Cibo</label>
                                <input type="number" id="food_production" name="food_production" min="0" value="<?php echo $building_to_edit['food_production']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="wood_production">Produzione Legno</label>
                                <input type="number" id="wood_production" name="wood_production" min="0" value="<?php echo $building_to_edit['wood_production']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="stone_production">Produzione Pietra</label>
                                <input type="number" id="stone_production" name="stone_production" min="0" value="<?php echo $building_to_edit['stone_production']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="capacity_increase">Aumento Capacità</label>
                                <input type="number" id="capacity_increase" name="capacity_increase" min="0" value="<?php echo $building_to_edit['capacity_increase']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="water_cost">Costo Acqua</label>
                                <input type="number" id="water_cost" name="water_cost" min="0" value="<?php echo $building_to_edit['water_cost']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="food_cost">Costo Cibo</label>
                                <input type="number" id="food_cost" name="food_cost" min="0" value="<?php echo $building_to_edit['food_cost']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="wood_cost">Costo Legno</label>
                                <input type="number" id="wood_cost" name="wood_cost" min="0" value="<?php echo $building_to_edit['wood_cost']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="stone_cost">Costo Pietra</label>
                                <input type="number" id="stone_cost" name="stone_cost" min="0" value="<?php echo $building_to_edit['stone_cost']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="build_time_minutes">Tempo di Costruzione (minuti)</label>
                                <input type="number" id="build_time_minutes" name="build_time_minutes" min="1" value="<?php echo $building_to_edit['build_time_minutes']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="image_url">URL Immagine</label>
                                <input type="text" id="image_url" name="image_url" value="<?php echo htmlspecialchars($building_to_edit['image_url']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="submit" name="edit_building" class="admin-btn primary-btn">
                                <i class="fas fa-save"></i> Salva Modifiche
                            </button>
                            <a href="admin.php?action=buildings" class="admin-btn secondary-btn">
                                <i class="fas fa-times"></i> Annulla
                            </a>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <footer class="admin-footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Pannello Amministrativo - Versione <?php echo GAME_VERSION; ?></p>
        </footer>
    </div>
    
    <!-- Modali Amministrative -->
    <div class="admin-modal" id="view-building-modal">
        <div class="admin-modal-content">
            <span class="admin-close-modal">&times;</span>
            <h2 id="view-building-title">Dettagli Edificio</h2>
            <div id="view-building-details" class="view-building-details">
                <!-- I dettagli saranno caricati via JavaScript -->
            </div>
        </div>
    </div>
    
    <script src="js/admin.js"></script>
</body>
</html>