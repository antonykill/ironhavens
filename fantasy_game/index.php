<?php
/**
 * Index File
 * 
 * Punto di ingresso principale del gioco
 */

// Includi la configurazione
require_once 'config.php';

// Controlla se l'utente è loggato
$logged_in = is_logged_in();
$user_id = get_current_user_id();
$username = get_current_username();

// Verifica se l'utente è un amministratore
$is_admin = $logged_in ? is_admin() : false;

// Se l'utente è loggato, ottieni le sue risorse
$resources = $logged_in ? get_player_resources($user_id) : null;
$player_level = $logged_in ? get_player_level($user_id) : null;

// Se l'utente è loggato, controlla se ci sono edifici completati
if ($logged_in) {
    $completed_buildings = check_completed_buildings($user_id);
    
    // Aggiorna le risorse ogni volta che l'utente carica la pagina
    update_player_resources($user_id);
}

// Gestione del routing
$page = $_GET['page'] ?? '';

// Se è specificata una pagina, carica il file corrispondente
if (!empty($page)) {
    $page_file = 'pages/' . $page . '.php';
    
    if (file_exists($page_file)) {
        // Includi il file della pagina
        include $page_file;
        exit;
    }
}

// Se non è specificata una pagina valida, carica la pagina principale del gioco
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo get_asset_path('css/style.css'); ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body>
    <div class="game-container">
        <header>
            <h1><?php echo SITE_NAME; ?></h1>
            
            <div class="user-info">
                <?php if ($logged_in): ?>
                    <span class="welcome">Benvenuto, <strong><?php echo sanitize_input($username); ?></strong>!</span>
                    <span class="level">Livello: <?php echo $player_level['current_level']; ?></span>
                    <a href="<?php echo BASE_URL; ?>?page=profile" class="profile-link"><i class="fas fa-user"></i> Profilo</a>
                    <?php if ($is_admin): ?>
                        <a href="<?php echo BASE_URL; ?>?page=admin" class="admin-link"><i class="fas fa-crown"></i> Admin</a>
                    <?php endif; ?>
                    <a href="#" class="logout-btn" id="logout-btn">Logout</a>
                <?php else: ?>
                    <a href="#" class="login-btn" id="login-btn">Login</a>
                    <a href="#" class="register-btn" id="register-btn">Registrati</a>
                <?php endif; ?>
            </div>
        </header>
        
        <?php if (isset($_SESSION['admin_error'])): ?>
        <div class="admin-error-message">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['admin_error']; ?>
            <?php unset($_SESSION['admin_error']); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($logged_in): ?>
        <!-- Contatori delle risorse -->
        <div class="resources-container">
            <div class="resource">
                <i class="fas fa-tint resource-icon water-icon"></i>
                <span class="resource-value" id="water-value"><?php echo $resources['water']; ?></span>
                <span class="resource-name">Acqua</span>
            </div>
            <div class="resource">
                <i class="fas fa-drumstick-bite resource-icon food-icon"></i>
                <span class="resource-value" id="food-value"><?php echo $resources['food']; ?></span>
                <span class="resource-name">Cibo</span>
            </div>
            <div class="resource">
                <i class="fas fa-tree resource-icon wood-icon"></i>
                <span class="resource-value" id="wood-value"><?php echo $resources['wood']; ?></span>
                <span class="resource-name">Legno</span>
            </div>
            <div class="resource">
                <i class="fas fa-mountain resource-icon stone-icon"></i>
                <span class="resource-value" id="stone-value"><?php echo $resources['stone']; ?></span>
                <span class="resource-name">Pietra</span>
            </div>
        </div>
        
        <!-- Area principale del gioco -->
        <div class="game-main">
            <div class="game-tabs">
                <button class="tab-btn active" data-tab="village">Il Mio Villaggio</button>
                <button class="tab-btn" data-tab="buildings">Costruisci</button>
                <button class="tab-btn" data-tab="progress">Progressi</button>
                <a href="<?php echo BASE_URL; ?>?page=tech-tree" class="tech-tree-link"><i class="fas fa-sitemap"></i> Albero Tecnologico</a>
            </div>
            
            <div class="tab-content">
                <!-- Tab Villaggio -->
                <div class="tab-pane active" id="village-tab">
                    <h2>Il Tuo Villaggio</h2>
                    <div class="village-area">
                        <div class="buildings-grid" id="player-buildings-grid">
                            <!-- Gli edifici saranno caricati via JavaScript -->
                            <div class="loading">Caricamento degli edifici in corso...</div>
                        </div>
                    </div>
                </div>
                
                <!-- Tab Costruzioni -->
                <div class="tab-pane" id="buildings-tab">
                    <h2>Costruisci Nuovi Edifici</h2>
                    <div class="available-buildings" id="available-buildings-list">
                        <!-- Gli edifici disponibili saranno caricati via JavaScript -->
                        <div class="loading">Caricamento degli edifici disponibili in corso...</div>
                    </div>
                </div>
                
                <!-- Tab Progressi -->
                <div class="tab-pane" id="progress-tab">
                    <h2>I Tuoi Progressi</h2>
                    <div class="progress-stats">
                        <div class="stat">
                            <h3>Livello</h3>
                            <div class="level-display"><?php echo $player_level['current_level']; ?></div>
                            <div class="xp-bar">
                                <div class="xp-fill" style="width: <?php echo min(100, ($player_level['experience_points'] / $player_level['next_level_xp']) * 100); ?>%"></div>
                            </div>
                            <div class="xp-text">XP: <?php echo $player_level['experience_points']; ?> / <?php echo $player_level['next_level_xp']; ?></div>
                        </div>
                        
                        <div class="stat">
                            <h3>Edifici Costruiti</h3>
                            <div id="buildings-count" class="stat-value">Caricamento...</div>
                        </div>
                        
                        <div class="stat">
                            <h3>Risorse Prodotte</h3>
                            <div id="resources-production" class="resources-production">
                                <div class="production-item">
                                    <i class="fas fa-tint"></i> <span id="water-production">0</span>/min
                                </div>
                                <div class="production-item">
                                    <i class="fas fa-drumstick-bite"></i> <span id="food-production">0</span>/min
                                </div>
                                <div class="production-item">
                                    <i class="fas fa-tree"></i> <span id="wood-production">0</span>/min
                                </div>
                                <div class="production-item">
                                    <i class="fas fa-mountain"></i> <span id="stone-production">0</span>/min
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Pagina di benvenuto per utenti non loggati -->
        <div class="welcome-container">
            <div class="welcome-content">
                <h2>Benvenuto in <?php echo SITE_NAME; ?>!</h2>
                <p>Un epico gioco fantasy dove potrai costruire il tuo villaggio, raccogliere risorse e diventare il sovrano più potente del regno!</p>
                <div class="welcome-buttons">
                    <button class="welcome-btn register-btn" id="welcome-register-btn">Inizia l'Avventura</button>
                    <button class="welcome-btn login-btn" id="welcome-login-btn">Accedi al tuo Regno</button>
                </div>
                <div class="game-features">
                    <div class="feature">
                        <i class="fas fa-hammer"></i>
                        <h3>Costruisci</h3>
                        <p>Crea un potente villaggio con diverse strutture</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-coins"></i>
                        <h3>Raccogli</h3>
                        <p>Accumula risorse per espandere il tuo impero</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-crown"></i>
                        <h3>Domina</h3>
                        <p>Diventa il sovrano più potente del regno</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <footer>
            <div class="footer-content">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Versione <?php echo GAME_VERSION; ?></p>
            </div>
        </footer>
    </div>
    
    <!-- Modali -->
    <div class="modal" id="login-modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Accedi al tuo Account</h2>
            <form id="login-form">
                <div class="form-group">
                    <label for="username_email">Username o Email:</label>
                    <input type="text" id="username_email" name="username_email" required>
                </div>
                <div class="form-group">
                    <label for="login-password">Password:</label>
                    <input type="password" id="login-password" name="password" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="submit-btn">Accedi</button>
                </div>
                <div class="form-message" id="login-message"></div>
            </form>
        </div>
    </div>
    
    <div class="modal" id="register-modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Crea un Nuovo Account</h2>
            <form id="register-form">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="register-password">Password:</label>
                    <input type="password" id="register-password" name="password" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="submit-btn">Registrati</button>
                </div>
                <div class="form-message" id="register-message"></div>
            </form>
        </div>
    </div>
    
    <div class="modal" id="building-modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2 id="building-modal-title">Dettagli Edificio</h2>
            <div class="building-details" id="building-details">
                <!-- I dettagli dell'edificio saranno caricati via JavaScript -->
            </div>
        </div>
    </div>
    
    <div class="notification" id="notification">
        <div class="notification-content" id="notification-content"></div>
    </div>
    
    <script src="<?php echo get_asset_path('js/game.js'); ?>"></script>
</body>
</html>