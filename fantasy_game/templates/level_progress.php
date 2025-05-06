<div class="level-progress-container">
    <div class="level-info">
        <h3>Livello <?= $current_level ?></h3>
        <p>XP: <?= $experience_points ?> / <?= $next_level_xp ?></p>
    </div>
    
    <div class="progress-bar">
        <div class="progress" style="width: <?= min(100, ($experience_points / $next_level_xp) * 100) ?>%"></div>
    </div>
    
    <div class="xp-to-next">
        <p><?= $next_level_xp - $experience_points ?> XP per salire di livello</p>
    </div>
</div>