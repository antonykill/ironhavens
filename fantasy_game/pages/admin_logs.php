<?php
// File: admin_logs.php
// Versione aggiornata con miglioramenti di leggibilità e colori più scuri

// Includi le funzioni necessarie
require_once '../config.php';
require_once '../includes/functions.php';

// Verifica se l'utente è loggato e se è un amministratore
if (!is_logged_in()) {
    header('Location: ../index.php');
    exit;
}

if (!is_admin()) {
    $_SESSION['admin_error'] = "Accesso negato: richiesti privilegi di amministratore.";
    header('Location: ../index.php');
    exit;
}

// Ottieni i dati dell'utente corrente
$user_id = get_current_user_id();
$username = get_current_username();

// Query diretta per ottenere i log (senza filtri per ora)
try {
    $logs = db_fetch_all("
        SELECT gl.*, u.username 
        FROM game_logs gl
        LEFT JOIN users u ON gl.user_id = u.user_id
        ORDER BY gl.created_at DESC
        LIMIT 50
    ");
    
    // Conta il numero totale di log
    $total_count = db_fetch_row("SELECT COUNT(*) as total FROM game_logs");
    $total = $total_count ? $total_count['total'] : 0;
} catch (Exception $e) {
    // In caso di errore, mostra un messaggio di debug e inizializza variabili vuote
    $error_message = "Errore nella query: " . $e->getMessage();
    $logs = [];
    $total = 0;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log di Sistema - Ironhaven</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin_logs.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-list-alt"></i> Log di Sistema</h1>
            <div class="admin-user-info">
                <span>Admin: <strong><?php echo htmlspecialchars($username); ?></strong></span>
                <a href="admin.php" class="admin-btn">Torna al Pannello Admin</a>
                <a href="../index.php" class="admin-btn">Torna al Gioco</a>
            </div>
        </div>
        
        <?php if (isset($error_message)): ?>
        <div style="background: #742a2a; border: 1px solid #9b2c2c; color: #fed7d7; padding: 10px; margin: 10px 0; border-radius: 5px;">
            <h3>Errore:</h3>
            <p><?php echo htmlspecialchars($error_message); ?></p>
        </div>
        <?php endif; ?>
        
        <div class="admin-section">
            <h2>Log di Sistema (<?php echo $total; ?> risultati)</h2>
            
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data e Ora</th>
                            <th>Utente</th>
                            <th>Tipo</th>
                            <th>Azione</th>
                            <th>Dati</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" class="no-results">Nessun log trovato.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo $log['log_id']; ?></td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($log['username'] ?? 'ID: ' . $log['user_id']); ?>
                                </td>
                                <td class="log-type-<?php echo htmlspecialchars($log['log_type']); ?>">
                                    <?php echo htmlspecialchars($log['log_type']); ?>
                                </td>
                                <td class="log-action-<?php echo htmlspecialchars($log['action']); ?>">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </td>
                                <td>
                                    <?php if (!empty($log['data'])): ?>
                                        <div class="log-data-preview">
                                        <?php
                                        // Mostra un'anteprima dei dati
                                        $data = json_decode($log['data'], true);
                                        if (is_array($data)) {
                                            $preview = '';
                                            foreach ($data as $key => $value) {
                                                if (is_array($value)) {
                                                    $preview .= $key . ': [Array], ';
                                                } else {
                                                    $preview .= $key . ': ' . (is_string($value) ? $value : json_encode($value)) . ', ';
                                                }
                                            }
                                            $preview = rtrim($preview, ', ');
                                            echo htmlspecialchars(substr($preview, 0, 50));
                                            if (strlen($preview) > 50) echo '...';
                                        } else {
                                            echo htmlspecialchars(substr($log['data'], 0, 50));
                                            if (strlen($log['data']) > 50) echo '...';
                                        }
                                        ?>
                                        </div>
                                    <?php else: ?>
                                        <em>Nessun dato</em>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <footer class="admin-footer">
            <p>&copy; <?php echo date('Y'); ?> Ironhaven - Log di Sistema</p>
        </footer>
    </div>
</body>
</html>