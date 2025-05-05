// Funzioni di utilità
function showNotification(message, isError = false) {
    const notification = document.getElementById('notification');
    const notificationContent = document.getElementById('notification-content');
    
    notificationContent.textContent = message;
    notification.style.display = 'block';
    
    if (isError) {
        notificationContent.style.borderLeftColor = '#f85149';
    } else {
        notificationContent.style.borderLeftColor = '#2ea043';
    }
    
    setTimeout(() => {
        notification.style.display = 'none';
    }, 5000);
}

function formatTime(date) {
    const options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit' 
    };
    
    return new Date(date).toLocaleDateString(undefined, options);
}

function formatTimeRemaining(endTime) {
    const end = new Date(endTime);
    const now = new Date();
    
    let diff = Math.max(0, (end - now) / 1000); // secondi rimanenti
    
    if (diff <= 0) {
        return "Completato!";
    }
    
    const hours = Math.floor(diff / 3600);
    diff %= 3600;
    const minutes = Math.floor(diff / 60);
    const seconds = Math.floor(diff % 60);
    
    if (hours > 0) {
        return `${hours}h ${minutes}m rimanenti`;
    } else if (minutes > 0) {
        return `${minutes}m ${seconds}s rimanenti`;
    } else {
        return `${seconds}s rimanenti`;
    }
}

// Aggiornamento delle risorse in tempo reale
function updateResourcesDisplay() {
    $.ajax({
        url: 'api.php?action=get_resources',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.error) {
                console.error('Errore nel recupero delle risorse:', response.error);
                return;
            }
            
            $('#water-value').text(response.water);
            $('#food-value').text(response.food);
            $('#wood-value').text(response.wood);
            $('#stone-value').text(response.stone);
        },
        error: function(xhr, status, error) {
            console.error('Errore AJAX:', error);
        }
    });
}

// Aggiornamento periodico delle risorse nel server
function updateServerResources() {
    $.ajax({
        url: 'api.php?action=update_resources',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (!response.success) {
                console.error('Errore nell\'aggiornamento delle risorse:', response.message);
                return;
            }
            
            // Aggiorna la produzione mostrata nella tab dei progressi
            $('#water-production').text(response.production.water);
            $('#food-production').text(response.production.food);
            $('#wood-production').text(response.production.wood);
            $('#stone-production').text(response.production.stone);
            
            // Aggiorna il display delle risorse
            updateResourcesDisplay();
        },
        error: function(xhr, status, error) {
            console.error('Errore AJAX:', error);
        }
    });
}

// Caricamento degli edifici del giocatore
function loadPlayerBuildings() {
    $.ajax({
        url: 'api.php?action=get_buildings',
        method: 'GET',
        dataType: 'json',
        success: function(buildings) {
            if (buildings.error) {
                console.error('Errore nel recupero degli edifici:', buildings.error);
                return;
            }
            
            const grid = $('#player-buildings-grid');
            grid.empty();
            
            if (buildings.length === 0) {
                grid.html('<div class="empty-message">Non hai ancora costruito edifici. Vai nella tab "Costruisci" per iniziare!</div>');
                $('#buildings-count').text('0');
                return;
            }
            
            $('#buildings-count').text(buildings.length);
            
            buildings.forEach(function(building) {
                let productionBadges = '';
                
                if (building.is_active) {
                    if (parseInt(building.water_production) > 0) {
                        productionBadges += `<div class="production-badge water-production">
                            <i class="fas fa-tint"></i> +${building.water_production}
                        </div>`;
                    }
                    
                    if (parseInt(building.food_production) > 0) {
                        productionBadges += `<div class="production-badge food-production">
                            <i class="fas fa-drumstick-bite"></i> +${building.food_production}
                        </div>`;
                    }
                    
                    if (parseInt(building.wood_production) > 0) {
                        productionBadges += `<div class="production-badge wood-production">
                            <i class="fas fa-tree"></i> +${building.wood_production}
                        </div>`;
                    }
                    
                    if (parseInt(building.stone_production) > 0) {
                        productionBadges += `<div class="production-badge stone-production">
                            <i class="fas fa-mountain"></i> +${building.stone_production}
                        </div>`;
                    }
                }
                
                let buildingHTML = `
                    <div class="building-card" data-building-id="${building.player_building_id}">
                `;
                
                // Se l'edificio è ancora in costruzione
                if (building.is_active === "0") {
                    const completionTime = new Date(building.construction_completed);
                    const now = new Date();
                    
                    buildingHTML += `
                        <div class="in-construction">
                            <div class="building-image">
                                <img src="${building.image_url || 'images/buildings/placeholder.png'}" alt="${building.name}">
                            </div>
                            <div class="construction-overlay">
                                <i class="fas fa-hammer construction-icon"></i>
                                <div class="construction-time" data-completion="${building.construction_completed}">
                                    ${formatTimeRemaining(building.construction_completed)}
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    buildingHTML += `
                        <div class="building-image">
                            <img src="${building.image_url || 'images/buildings/placeholder.png'}" alt="${building.name}">
                        </div>
                    `;
                }
                
                buildingHTML += `
                        <div class="building-info">
                            <div class="building-name">${building.name}</div>
                            <div class="building-quantity">Quantità: ${building.quantity} • Livello: ${building.level}</div>
                            <div class="building-production">
                                ${productionBadges || '<span class="no-production">Nessuna produzione</span>'}
                            </div>
                        </div>
                    </div>
                `;
                
                grid.append(buildingHTML);
            });
            
            // Aggiunge handler per mostrare i dettagli dell'edificio
            $('.building-card').click(function() {
                const buildingId = $(this).data('building-id');
                showBuildingDetails(buildingId, buildings);
            });
            
            // Aggiorna i timer di costruzione
            updateConstructionTimers();
        },
        error: function(xhr, status, error) {
            console.error('Errore AJAX:', error);
        }
    });
}

// Mostra i dettagli di un edificio
function showBuildingDetails(buildingId, buildings) {
    const building = buildings.find(b => b.player_building_id == buildingId);
    
    if (!building) {
        console.error('Edificio non trovato:', buildingId);
        return;
    }
    
    const modal = $('#building-modal');
    const modalTitle = $('#building-modal-title');
    const detailsContainer = $('#building-details');
    
    modalTitle.text(building.name);
    
    let productionHTML = '';
    if (parseInt(building.water_production) > 0) {
        productionHTML += `
            <div class="cost-item">
                <i class="fas fa-tint water-icon"></i> +${building.water_production * building.quantity * building.level}/min
            </div>
        `;
    }
    if (parseInt(building.food_production) > 0) {
        productionHTML += `
            <div class="cost-item">
                <i class="fas fa-drumstick-bite food-icon"></i> +${building.food_production * building.quantity * building.level}/min
            </div>
        `;
    }
    if (parseInt(building.wood_production) > 0) {
        productionHTML += `
            <div class="cost-item">
                <i class="fas fa-tree wood-icon"></i> +${building.wood_production * building.quantity * building.level}/min
            </div>
        `;
    }
    if (parseInt(building.stone_production) > 0) {
        productionHTML += `
            <div class="cost-item">
                <i class="fas fa-mountain stone-icon"></i> +${building.stone_production * building.quantity * building.level}/min
            </div>
        `;
    }
    
    let statusHTML = '';
    if (building.is_active === "0") {
        statusHTML = `
            <div class="building-stat">
                <div class="stat-label">Stato:</div>
                <div class="stat-value">In Costruzione</div>
            </div>
            <div class="building-stat">
                <div class="stat-label">Completamento:</div>
                <div class="stat-value">${formatTime(building.construction_completed)}</div>
            </div>
        `;
    } else {
        statusHTML = `
            <div class="building-stat">
                <div class="stat-label">Stato:</div>
                <div class="stat-value">Attivo</div>
            </div>
            <div class="building-stat">
                <div class="stat-label">Costruito il:</div>
                <div class="stat-value">${formatTime(building.construction_completed)}</div>
            </div>
        `;
    }
    
    detailsContainer.html(`
        <div class="building-description">
            ${building.description || 'Nessuna descrizione disponibile.'}
        </div>
        <div class="building-stats">
            <div class="building-stat">
                <div class="stat-label">Quantità:</div>
                <div class="stat-value">${building.quantity}</div>
            </div>
            <div class="building-stat">
                <div class="stat-label">Livello:</div>
                <div class="stat-value">${building.level}</div>
            </div>
            ${statusHTML}
        </div>
        <div class="production-details">
            <h3>Produzione</h3>
            <div class="production-list">
                ${productionHTML || '<div>Questo edificio non produce risorse.</div>'}
            </div>
        </div>
    `);
    
    modal.css('display', 'flex');
}

// Caricamento degli edifici disponibili per la costruzione
function loadAvailableBuildings() {
    $.ajax({
        url: 'api.php?action=available_buildings',
        method: 'GET',
        dataType: 'json',
        success: function(buildings) {
            if (buildings.error) {
                console.error('Errore nel recupero degli edifici disponibili:', buildings.error);
                return;
            }
            
            const container = $('#available-buildings-list');
            container.empty();
            
            if (buildings.length === 0) {
                container.html('<div class="empty-message">Nessun edificio disponibile al tuo livello attuale.</div>');
                return;
            }
            
            // Ottieni le risorse del giocatore per controllare se ha abbastanza per costruire
            $.ajax({
                url: 'api.php?action=get_resources',
                method: 'GET',
                dataType: 'json',
                success: function(resources) {
                    buildings.forEach(function(building) {
                        let costsHTML = '';
                        
                        if (parseInt(building.water_cost) > 0) {
                            const hasEnough = resources.water >= building.water_cost;
                            costsHTML += `
                                <div class="cost-item ${hasEnough ? '' : 'not-enough'}">
                                    <i class="fas fa-tint water-icon"></i> ${building.water_cost}
                                </div>
                            `;
                        }
                        
                        if (parseInt(building.food_cost) > 0) {
                            const hasEnough = resources.food >= building.food_cost;
                            costsHTML += `
                                <div class="cost-item ${hasEnough ? '' : 'not-enough'}">
                                    <i class="fas fa-drumstick-bite food-icon"></i> ${building.food_cost}
                                </div>
                            `;
                        }
                        
                        if (parseInt(building.wood_cost) > 0) {
                            const hasEnough = resources.wood >= building.wood_cost;
                            costsHTML += `
                                <div class="cost-item ${hasEnough ? '' : 'not-enough'}">
                                    <i class="fas fa-tree wood-icon"></i> ${building.wood_cost}
                                </div>
                            `;
                        }
                        
                        if (parseInt(building.stone_cost) > 0) {
                            const hasEnough = resources.stone >= building.stone_cost;
                            costsHTML += `
                                <div class="cost-item ${hasEnough ? '' : 'not-enough'}">
                                    <i class="fas fa-mountain stone-icon"></i> ${building.stone_cost}
                                </div>
                            `;
                        }
                        
                        let productionHTML = '';
                        if (parseInt(building.water_production) > 0) {
                            productionHTML += `
                                <div class="production-item">
                                    <i class="fas fa-tint water-icon"></i> +${building.water_production}/min
                                </div>
                            `;
                        }
                        
                        if (parseInt(building.food_production) > 0) {
                            productionHTML += `
                                <div class="production-item">
                                    <i class="fas fa-drumstick-bite food-icon"></i> +${building.food_production}/min
                                </div>
                            `;
                        }
                        
                        if (parseInt(building.wood_production) > 0) {
                            productionHTML += `
                                <div class="production-item">
                                    <i class="fas fa-tree wood-icon"></i> +${building.wood_production}/min
                                </div>
                            `;
                        }
                        
                        if (parseInt(building.stone_production) > 0) {
                            productionHTML += `
                                <div class="production-item">
                                    <i class="fas fa-mountain stone-icon"></i> +${building.stone_production}/min
                                </div>
                            `;
                        }
                        
                        const canBuild = resources.water >= building.water_cost && 
                                      resources.food >= building.food_cost && 
                                      resources.wood >= building.wood_cost && 
                                      resources.stone >= building.stone_cost;
                        
                        container.append(`
                            <div class="available-building-card">
                                <div class="building-image">
                                    <img src="${building.image_url || 'images/buildings/placeholder.png'}" alt="${building.name}">
                                </div>
                                <div class="available-building-info">
                                    <div class="available-building-name">${building.name}</div>
                                    <div class="available-building-description">
                                        ${building.description || 'Nessuna descrizione disponibile.'}
                                    </div>
                                    <div class="building-costs">
                                        <h4>Costi:</h4>
                                        ${costsHTML}
                                        <div class="cost-item">
                                            <i class="fas fa-clock"></i> ${building.build_time_minutes} minuti
                                        </div>
                                    </div>
                                    <div class="building-production">
                                        <h4>Produzione:</h4>
                                        ${productionHTML || '<div>Nessuna produzione di risorse.</div>'}
                                    </div>
                                    <button class="build-btn" 
                                            data-building-id="${building.building_type_id}" 
                                            ${canBuild ? '' : 'disabled'}>
                                        ${canBuild ? 'Costruisci' : 'Risorse insufficienti'}
                                    </button>
                                </div>
                            </div>
                        `);
                    });
                    
                    // Aggiungi event listener per il pulsante di costruzione
                    $('.build-btn').click(function() {
                        if (!$(this).prop('disabled')) {
                            const buildingId = $(this).data('building-id');
                            startConstruction(buildingId);
                        }
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Errore nel recupero delle risorse:', error);
                }
            });
        },
        error: function(xhr, status, error) {
            console.error('Errore AJAX:', error);
        }
    });
}

// Avvia la costruzione di un edificio
function startConstruction(buildingTypeId) {
    $.ajax({
        url: 'api.php?action=start_construction',
        method: 'POST',
        data: {
            building_type_id: buildingTypeId
        },
        dataType: 'json',
        success: function(response) {
            if (!response.success) {
                showNotification(response.message, true);
                return;
            }
            
            showNotification(response.message);
            
            // Aggiorna le risorse e gli edifici
            updateResourcesDisplay();
            loadPlayerBuildings();
            loadAvailableBuildings();
            
            // Cambia tab per mostrare il villaggio
            $('.tab-btn[data-tab="village"]').click();
        },
        error: function(xhr, status, error) {
            console.error('Errore AJAX:', error);
            showNotification('Si è verificato un errore durante la costruzione.', true);
        }
    });
}

// Aggiorna i timer di costruzione
function updateConstructionTimers() {
    $('.construction-time').each(function() {
        const completionTime = $(this).data('completion');
        $(this).text(formatTimeRemaining(completionTime));
        
        // Se il timer è scaduto, aggiorna gli edifici
        if ($(this).text() === "Completato!") {
            checkCompletedBuildings();
        }
    });
}

// Controlla gli edifici completati
function checkCompletedBuildings() {
    $.ajax({
        url: BASE_URL + 'api.php?action=check_buildings',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Risposta completa:', response); // Aggiungi questo per debug
            
            if (!response.success) {
                console.error('Errore nel controllo degli edifici completati:', response.message || 'Nessun messaggio di errore');
                return;
            }
            
            if (response.buildings && response.buildings.length > 0) {
                response.buildings.forEach(building => {
                    showNotification(`${building.name} è stato completato!`);
                });
                
                // Aggiorna le risorse e gli edifici
                updateResourcesDisplay();
                loadPlayerBuildings();
                updateServerResources();
            }
        },
        error: function(xhr, status, error) {
            console.error('Errore AJAX nel controllo degli edifici completati:', error);
            console.error('Dettagli risposta:', xhr.responseText);
            try {
                const errorData = JSON.parse(xhr.responseText);
                console.error('Dati di errore:', errorData);
            } catch (e) {
                console.error('Risposta non in formato JSON:', xhr.responseText);
            }
        }
    });
}

// Gestione delle tabs
$(document).on('click', '.tab-btn', function() {
    const tabId = $(this).data('tab');
    
    // Rimuovi la classe active da tutti i pulsanti e pannelli
    $('.tab-btn').removeClass('active');
    $('.tab-pane').removeClass('active');
    
    // Aggiungi la classe active al pulsante e pannello corrente
    $(this).addClass('active');
    $(`#${tabId}-tab`).addClass('active');
    
    // Carica i dati specifici della tab
    if (tabId === 'village') {
        loadPlayerBuildings();
    } else if (tabId === 'buildings') {
        loadAvailableBuildings();
    }
});

// Gestione delle modali
$(document).on('click', '.login-btn, #welcome-login-btn', function() {
    $('#login-modal').css('display', 'flex');
});

$(document).on('click', '.register-btn, #welcome-register-btn', function() {
    $('#register-modal').css('display', 'flex');
});

$(document).on('click', '.close-modal', function() {
    $(this).closest('.modal').css('display', 'none');
});

// Chiudi modale quando si clicca fuori dal contenuto
$(document).on('click', '.modal', function(e) {
    if (e.target === this) {
        $(this).css('display', 'none');
    }
});

// Gestione del form di login
$('#login-form').submit(function(e) {
    e.preventDefault();
    
    const username_email = $('#username_email').val();
    const password = $('#login-password').val();
    
    if (!username_email || !password) {
        $('#login-message').addClass('error').text('Per favore, compila tutti i campi.');
        return;
    }
    
    $.ajax({
        url: 'api.php?action=login',
        method: 'POST',
        data: {
            username_email: username_email,
            password: password
        },
        dataType: 'json',
        success: function(response) {
            if (!response.success) {
                $('#login-message').addClass('error').text(response.message);
                return;
            }
            
            // Reindirizza alla stessa pagina per aggiornare la sessione
            window.location.reload();
        },
        error: function(xhr, status, error) {
            $('#login-message').addClass('error').text('Si è verificato un errore durante il login.');
            console.error('Errore AJAX:', error);
        }
    });
});

// Gestione del form di registrazione
$('#register-form').submit(function(e) {
    e.preventDefault();
    
    const username = $('#username').val();
    const email = $('#email').val();
    const password = $('#register-password').val();
    
    if (!username || !email || !password) {
        $('#register-message').addClass('error').text('Per favore, compila tutti i campi.');
        return;
    }
    
    if (password.length < 6) {
        $('#register-message').addClass('error').text('La password deve contenere almeno 6 caratteri.');
        return;
    }
    
    $.ajax({
        url: 'api.php?action=register',
        method: 'POST',
        data: {
            username: username,
            email: email,
            password: password
        },
        dataType: 'json',
        success: function(response) {
            if (!response.success) {
                $('#register-message').addClass('error').text(response.message);
                return;
            }
            
            $('#register-message').removeClass('error').addClass('success').text(response.message);
            
            // Reindirizza dopo un breve ritardo
            setTimeout(function() {
                window.location.reload();
            }, 1500);
        },
        error: function(xhr, status, error) {
            $('#register-message').addClass('error').text('Si è verificato un errore durante la registrazione.');
            console.error('Errore AJAX:', error);
        }
    });
});

// Gestione del logout
$('#logout-btn').click(function(e) {
    e.preventDefault();
    
    $.ajax({
        url: 'api.php?action=logout',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                window.location.reload();
            }
        },
        error: function(xhr, status, error) {
            console.error('Errore durante il logout:', error);
        }
    });
});

// Inizializzazione
$(document).ready(function() {
    // Controllo se l'utente è loggato
    const isLoggedIn = $('.resources-container').length > 0;
    
    if (isLoggedIn) {
        // Carica gli edifici del giocatore
        loadPlayerBuildings();
        
        // Aggiorna le risorse ogni 30 secondi
        setInterval(updateServerResources, 30000);
        
        // Aggiorna i timer di costruzione ogni secondo
        setInterval(updateConstructionTimers, 1000);
        
        // Controlla gli edifici completati ogni 10 secondi
        setInterval(checkCompletedBuildings, 10000);
    }
});