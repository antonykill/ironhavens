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

// Ottieni il livello del giocatore
$player_level = get_player_level($user_id);

// Ottieni l'albero tecnologico
$tech_tree = get_tech_tree();

// Ottieni gli edifici sbloccati per questo giocatore
$unlocked_buildings = get_unlocked_buildings($user_id);

// Converti in un array associativo per un accesso più facile
$unlocked_map = [];
foreach ($unlocked_buildings as $building) {
    $unlocked_map[$building['building_type_id']] = $building;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Albero Tecnologico - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/tech-tree.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body>
    <div class="tech-tree-container">
        <header>
            <h1><?php echo SITE_NAME; ?></h1>
            
            <div class="user-info">
                <span class="welcome">Benvenuto, <strong><?php echo htmlspecialchars($username); ?></strong>!</span>
                <span class="level">Livello: <?php echo $player_level['current_level']; ?></span>
                <a href="profile.php" class="profile-link"><i class="fas fa-user"></i> Profilo</a>
                <?php if (is_admin($user_id)): ?>
                <a href="admin.php" class="admin-link"><i class="fas fa-crown"></i> Admin</a>
                <?php endif; ?>
                <a href="index.php" class="back-to-game-btn"><i class="fas fa-gamepad"></i> Torna al Gioco</a>
                <a href="#" class="logout-btn" id="logout-btn">Logout</a>
            </div>
        </header>
        
        <div class="tech-tree-content">
            <h2><i class="fas fa-sitemap"></i> Albero Tecnologico</h2>
            <p class="tech-tree-intro">
                Questo diagramma mostra il percorso di progressione degli edifici. Costruisci gli edifici di livello inferiore per sbloccare quelli avanzati.
                Il tuo livello attuale (<strong><?php echo $player_level['current_level']; ?></strong>) determina quali edifici puoi costruire.
            </p>
            
            <div class="tech-levels-container">
                <?php for($level = 1; $level <= 5; $level++): ?>
                <div class="tech-level<?php echo $level <= $player_level['current_level'] ? ' unlocked' : ' locked'; ?>">
                    <div class="level-header">
                        <h3>Livello <?php echo $level; ?></h3>
                        <?php if($level > $player_level['current_level']): ?>
                        <span class="level-locked"><i class="fas fa-lock"></i> Bloccato</span>
                        <?php else: ?>
                        <span class="level-unlocked"><i class="fas fa-unlock"></i> Sbloccato</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="level-buildings">
                        <?php if (isset($tech_tree[$level])): ?>
                            <?php foreach($tech_tree[$level] as $building): ?>
                                <?php
                                $is_unlocked = isset($unlocked_map[$building['building_type_id']]) && $unlocked_map[$building['building_type_id']]['is_unlocked'];
                                $is_built = isset($unlocked_map[$building['building_type_id']]) && $unlocked_map[$building['building_type_id']]['is_built'];
                                $locked_reason = isset($unlocked_map[$building['building_type_id']]) ? $unlocked_map[$building['building_type_id']]['locked_reason'] : null;
                                ?>
                                <div class="building-card <?php echo $is_unlocked ? 'unlocked' : 'locked'; ?> <?php echo $is_built ? 'built' : ''; ?>"
                                     data-building-id="<?php echo $building['building_type_id']; ?>">
                                    <div class="building-image">
                                        <img src="<?php echo $building['image_url'] ?: 'images/buildings/placeholder.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($building['name']); ?>">
                                        <?php if (!$is_unlocked): ?>
                                        <div class="building-lock">
                                            <i class="fas fa-lock"></i>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($is_built): ?>
                                        <div class="building-built">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="building-info">
                                        <h4><?php echo htmlspecialchars($building['name']); ?></h4>
                                        <?php if (!$is_unlocked && $locked_reason): ?>
                                        <div class="building-lock-reason">
                                            <?php echo htmlspecialchars($locked_reason); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-buildings">Nessun edificio disponibile a questo livello</div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        
        <footer>
            <div class="footer-content">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Versione <?php echo GAME_VERSION; ?></p>
            </div>
        </footer>
    </div>
    
    <!-- Modal per i dettagli degli edifici -->
    <div class="tech-modal" id="building-details-modal">
        <div class="tech-modal-content">
            <span class="tech-close-modal">&times;</span>
            <h2 id="modal-building-title">Dettagli Edificio</h2>
            <div id="modal-building-details" class="building-details">
                <!-- I dettagli saranno caricati via AJAX -->
                <div class="loading">Caricamento...</div>
            </div>
        </div>
    </div>
    
    <script>
        $(document).ready(function() {
            // Gestione click sugli edifici
            $('.building-card').click(function() {
                const buildingId = $(this).data('building-id');
                
                // Mostra il modal
                $('#building-details-modal').css('display', 'flex');
                $('#modal-building-details').html('<div class="loading">Caricamento...</div>');
                
                // Carica i dettagli via AJAX
                $.ajax({
                    url: 'backend.php?action=get_building_details',
                    method: 'GET',
                    data: { building_id: buildingId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.error) {
                            $('#modal-building-details').html('<div class="error">' + response.error + '</div>');
                            return;
                        }
                        
                        const building = response;
                        $('#modal-building-title').text(building.name);
                        
                        let detailsHTML = `
                            <div class="modal-building-image">
                                <img src="${building.image_url || 'images/buildings/placeholder.png'}" alt="${building.name}">
                            </div>
                            <div class="modal-building-description">
                                ${building.description || 'Nessuna descrizione disponibile.'}
                            </div>
                        `;
                        
                        // Requisiti
                        detailsHTML += `
                            <div class="modal-building-requirements">
                                <h3>Requisiti</h3>
                                <div class="requirement">
                                    <i class="fas fa-user-graduate"></i> Livello Giocatore: ${building.level_required}
                                </div>
                        `;
                        
                        // Dipendenze
                        if (building.dependencies && building.dependencies.length > 0) {
                            detailsHTML += `<h4>Edifici Richiesti:</h4><ul class="dependency-list">`;
                            building.dependencies.forEach(function(dep) {
                                detailsHTML += `
                                    <li>
                                        <img src="${dep.image_url || 'images/buildings/placeholder.png'}" 
                                             alt="${dep.name}" class="dependency-icon">
                                        ${dep.name} (Livello ${dep.required_building_level})
                                    </li>
                                `;
                            });
                            detailsHTML += `</ul>`;
                        }
                        
                        detailsHTML += `</div>`; // Chiude modal-building-requirements
                        
                        // Produzione
                        detailsHTML += `<div class="modal-building-production">
                            <h3>Produzione</h3>
                            <div class="production-details">`;
                            
                        let hasProduction = false;
                        
                        if (parseInt(building.water_production) > 0) {
                            detailsHTML += `
                                <div class="production-item">
                                    <i class="fas fa-tint water-icon"></i> +${building.water_production} Acqua/min
                                </div>
                            `;
                            hasProduction = true;
                        }
                        
                        if (parseInt(building.food_production) > 0) {
                            detailsHTML += `
                                <div class="production-item">
                                    <i class="fas fa-drumstick-bite food-icon"></i> +${building.food_production} Cibo/min
                                </div>
                            `;
                            hasProduction = true;
                        }
                        
                        if (parseInt(building.wood_production) > 0) {
                            detailsHTML += `
                                <div class="production-item">
                                    <i class="fas fa-tree wood-icon"></i> +${building.wood_production} Legno/min
                                </div>
                            `;
                            hasProduction = true;
                        }
                        
                        if (parseInt(building.stone_production) > 0) {
                            detailsHTML += `
                                <div class="production-item">
                                    <i class="fas fa-mountain stone-icon"></i> +${building.stone_production} Pietra/min
                                </div>
                            `;
                            hasProduction = true;
                        }
                        
                        if (parseInt(building.capacity_increase) > 0) {
                            detailsHTML += `
                                <div class="production-item">
                                    <i class="fas fa-warehouse"></i> +${building.capacity_increase} Capacità
                                </div>
                            `;
                            hasProduction = true;
                        }
                        
                        if (!hasProduction) {
                            detailsHTML += `<div class="no-production">Questo edificio non produce risorse.</div>`;
                        }
                        
                        detailsHTML += `</div></div>`; // Chiude production-details e modal-building-production
                        
                        // Costi
                        detailsHTML += `<div class="modal-building-costs">
                            <h3>Costi</h3>
                            <div class="costs-details">`;
                            
                        let hasCosts = false;
                        
                        if (parseInt(building.water_cost) > 0) {
                            detailsHTML += `
                                <div class="cost-item">
                                    <i class="fas fa-tint water-icon"></i> ${building.water_cost} Acqua
                                </div>
                            `;
                            hasCosts = true;
                        }
                        
                        if (parseInt(building.food_cost) > 0) {
                            detailsHTML += `
                                <div class="cost-item">
                                    <i class="fas fa-drumstick-bite food-icon"></i> ${building.food_cost} Cibo
                                </div>
                            `;
                            hasCosts = true;
                        }
                        
                        if (parseInt(building.wood_cost) > 0) {
                            detailsHTML += `
                                <div class="cost-item">
                                    <i class="fas fa-tree wood-icon"></i> ${building.wood_cost} Legno
                                </div>
                            `;
                            hasCosts = true;
                        }
                        
                        if (parseInt(building.stone_cost) > 0) {
                            detailsHTML += `
                                <div class="cost-item">
                                    <i class="fas fa-mountain stone-icon"></i> ${building.stone_cost} Pietra
                                </div>
                            `;
                            hasCosts = true;
                        }
                        
                        detailsHTML += `
                                <div class="cost-item">
                                    <i class="fas fa-clock"></i> ${building.build_time_minutes} minuti
                                </div>
                            </div>
                        </div>`;
                        
                        // Azioni
                        if (building.is_unlocked) {
                            detailsHTML += `
                                <div class="modal-building-actions">
                                    <a href="index.php?tab=buildings" class="tech-btn primary-btn">
                                        <i class="fas fa-hammer"></i> Vai alla Costruzione
                                    </a>
                                </div>
                            `;
                        } else {
                            detailsHTML += `
                                <div class="modal-building-actions">
                                    <span class="tech-btn disabled-btn">
                                        <i class="fas fa-lock"></i> Edificio Bloccato
                                    </span>
                                </div>
                            `;
                        }
                        
                        $('#modal-building-details').html(detailsHTML);
                    },
                    error: function(xhr, status, error) {
                        $('#modal-building-details').html('<div class="error">Errore durante il caricamento dei dettagli.</div>');
                        console.error('Errore AJAX:', error);
                    }
                });
            });
            
            // Chiudi modal quando si clicca sulla X
            $('.tech-close-modal').click(function() {
                $('#building-details-modal').css('display', 'none');
            });
            
            // Chiudi modal quando si clicca fuori dal contenuto
            $('#building-details-modal').click(function(e) {
                if (e.target === this) {
                    $(this).css('display', 'none');
                }
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
    </script>
</body>
</html>