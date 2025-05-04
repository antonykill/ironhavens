<footer>
            <div class="footer-content">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Versione <?php echo GAME_VERSION; ?></p>
            </div>
        </footer>
    </div>
    
    <?php if (!$logged_in): ?>
    <!-- Modali di login/registrazione -->
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
    <?php endif; ?>
    
    <div class="notification" id="notification">
        <div class="notification-content" id="notification-content"></div>
    </div>
    
    <script src="<?php echo get_asset_path('js/game.js'); ?>"></script>
    <?php if (isset($additional_js) && is_array($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo get_asset_path('js/' . $js); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>