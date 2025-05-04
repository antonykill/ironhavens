<?php
// Include le funzioni di gioco
require_once __DIR__ . '/../config.php';

// Controlla se l'utente è loggato
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

// Ottieni i dati dell'utente
$user_data = [];
try {
    $pdo = db_connect();
    $stmt = $pdo->prepare('SELECT username, email, registration_date, last_login, is_active FROM users WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Errore nel recupero dei dati utente: " . $e->getMessage());
}

// Gestisci l'aggiornamento dell'email
$email_updated = false;
$email_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_email'])) {
    $new_email = $_POST['email'] ?? '';
    
    if (empty($new_email)) {
        $email_error = "L'email non può essere vuota";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $email_error = "Formato email non valido";
    } else {
        try {
            // Verifica se l'email è già in uso
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?');
            $stmt->execute([$new_email, $user_id]);
            if ($stmt->fetchColumn() > 0) {
                $email_error = "Questa email è già in uso";
            } else {
                // Aggiorna l'email
                $stmt = $pdo->prepare('UPDATE users SET email = ? WHERE user_id = ?');
                $stmt->execute([$new_email, $user_id]);
                $email_updated = true;
                $user_data['email'] = $new_email;
            }
        } catch (PDOException $e) {
            $email_error = "Errore durante l'aggiornamento dell'email: " . $e->getMessage();
        }
    }
}

// Gestisci l'aggiornamento della password
$password_updated = false;
$password_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $password_error = "Tutti i campi password sono obbligatori";
    } elseif ($new_password !== $confirm_password) {
        $password_error = "La nuova password e la conferma non corrispondono";
    } elseif (strlen($new_password) < 6) {
        $password_error = "La password deve contenere almeno 6 caratteri";
    } else {
        try {
            // Verifica la password corrente
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE user_id = ?');
            $stmt->execute([$user_id]);
            $current_hash = $stmt->fetchColumn();
            
            if (!password_verify($current_password, $current_hash)) {
                $password_error = "La password attuale non è corretta";
            } else {
                // Aggiorna la password
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
                $stmt->execute([$new_hash, $user_id]);
                $password_updated = true;
            }
        } catch (PDOException $e) {
            $password_error = "Errore durante l'aggiornamento della password: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo Utente - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body>
    <div class="profile-container">
        <header>
            <h1><?php echo SITE_NAME; ?></h1>
            
            <div class="user-info">
                <span class="welcome">Benvenuto, <strong><?php echo htmlspecialchars($username); ?></strong>!</span>
                <a href="index.php" class="profile-btn">Torna al Gioco</a>
                <a href="#" class="logout-btn" id="logout-btn">Logout</a>
            </div>
        </header>
        
        <div class="profile-content">
            <div class="profile-sidebar">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
                    <h2><?php echo htmlspecialchars($username); ?></h2>
                </div>
                
                <ul class="profile-menu">
                    <li class="active" data-tab="account">
                        <a href="#account"><i class="fas fa-user"></i> Informazioni Account</a>
                    </li>
                    <li data-tab="email">
                        <a href="#email"><i class="fas fa-envelope"></i> Cambia Email</a>
                    </li>
                    <li data-tab="password">
                        <a href="#password"><i class="fas fa-key"></i> Cambia Password</a>
                    </li>
                    <?php if ($is_admin): ?>
                    <li>
                        <a href="admin.php" target="_self"><i class="fas fa-crown"></i> Pannello Amministrativo</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="profile-main">
                <!-- Scheda Informazioni Account -->
                <div class="profile-tab active" id="account-tab">
                    <h2><i class="fas fa-user"></i> Informazioni Account</h2>
                    
                    <div class="profile-info-card">
                        <div class="profile-info-item">
                            <div class="info-label">Username:</div>
                            <div class="info-value"><?php echo htmlspecialchars($username); ?></div>
                        </div>
                        
                        <div class="profile-info-item">
                            <div class="info-label">Email:</div>
                            <div class="info-value"><?php echo htmlspecialchars($user_data['email'] ?? ''); ?></div>
                        </div>
                        
                        <div class="profile-info-item">
                            <div class="info-label">Registrato il:</div>
                            <div class="info-value"><?php echo isset($user_data['registration_date']) ? date('d/m/Y H:i', strtotime($user_data['registration_date'])) : ''; ?></div>
                        </div>
                        
                        <div class="profile-info-item">
                            <div class="info-label">Ultimo accesso:</div>
                            <div class="info-value"><?php echo isset($user_data['last_login']) ? date('d/m/Y H:i', strtotime($user_data['last_login'])) : ''; ?></div>
                        </div>
                        
                        <div class="profile-info-item">
                            <div class="info-label">Stato account:</div>
                            <div class="info-value">
                                <?php if (isset($user_data['is_active']) && $user_data['is_active']): ?>
                                    <span class="status-active"><i class="fas fa-check-circle"></i> Attivo</span>
                                <?php else: ?>
                                    <span class="status-inactive"><i class="fas fa-times-circle"></i> Inattivo</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($is_admin): ?>
                        <div class="profile-info-item">
                            <div class="info-label">Ruolo:</div>
                            <div class="info-value">
                                <span class="admin-badge"><i class="fas fa-crown"></i> Amministratore</span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Scheda Cambia Email -->
                <div class="profile-tab" id="email-tab">
                    <h2><i class="fas fa-envelope"></i> Cambia Email</h2>
                    
                    <?php if ($email_updated): ?>
                    <div class="profile-message success">
                        <i class="fas fa-check-circle"></i> Email aggiornata con successo!
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($email_error): ?>
                    <div class="profile-message error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $email_error; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="profile-form-card">
                        <form action="profile.php" method="post" class="profile-form">
                            <div class="form-group">
                                <label for="email">Nuova Email:</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-buttons">
                                <button type="submit" name="update_email" class="profile-btn primary-btn">
                                    <i class="fas fa-save"></i> Aggiorna Email
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Scheda Cambia Password -->
                <div class="profile-tab" id="password-tab">
                    <h2><i class="fas fa-key"></i> Cambia Password</h2>
                    
                    <?php if ($password_updated): ?>
                    <div class="profile-message success">
                        <i class="fas fa-check-circle"></i> Password aggiornata con successo!
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($password_error): ?>
                    <div class="profile-message error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $password_error; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="profile-form-card">
                        <form action="profile.php" method="post" class="profile-form">
                            <div class="form-group">
                                <label for="current_password">Password Attuale:</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">Nuova Password:</label>
                                <input type="password" id="new_password" name="new_password" required>
                                <div class="form-hint">La password deve contenere almeno 6 caratteri</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Conferma Nuova Password:</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="form-buttons">
                                <button type="submit" name="update_password" class="profile-btn primary-btn">
                                    <i class="fas fa-save"></i> Aggiorna Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <footer>
            <div class="footer-content">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Versione <?php echo GAME_VERSION; ?></p>
            </div>
        </footer>
    </div>
    
    <script>
        // Gestione delle tab
        $(document).ready(function() {
            // Verifica se l'URL ha un hash per determinare quale tab mostrare
            let hash = window.location.hash;
            if (hash) {
                let tab = hash.substring(1); // Rimuovi il # dall'hash
                $('.profile-menu li').removeClass('active');
                $('.profile-menu li[data-tab="' + tab + '"]').addClass('active');
                $('.profile-tab').removeClass('active');
                $('#' + tab + '-tab').addClass('active');
            }
            
            // Gestione del click sui link del menu
            $('.profile-menu li a').click(function(e) {
                e.preventDefault();
                let tab = $(this).parent('li').data('tab');
                
                $('.profile-menu li').removeClass('active');
                $(this).parent('li').addClass('active');
                
                $('.profile-tab').removeClass('active');
                $('#' + tab + '-tab').addClass('active');
                
                window.location.hash = tab;
            });
            
            // Gestione del logout
            $('#logout-btn').click(function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: 'backend.php?action=logout',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            window.location.href = 'index.php';
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Errore durante il logout:', error);
                    }
                });
            });
        });
<!-- Aggiungi questo JavaScript nella sezione script esistente -->
 // Gestione del link al pannello amministrativo
$('.profile-menu li a[href="admin.php"]').click(function(e) {
    // Impedisci il comportamento del tab normale
    e.stopPropagation();
    // Non prevenire il comportamento del link
    // window.location.href verrà gestito normalmente
});
    </script>

</body>
</html>