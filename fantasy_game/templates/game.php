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