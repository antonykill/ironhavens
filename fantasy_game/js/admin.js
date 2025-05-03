/**
 * JavaScript per il pannello di amministrazione di Ironhaven
 */

// Funzioni di utilità
function showNotification(message, isError = false) {
    // Questa funzione non è implementata nel pannello di amministrazione
    // Ma potrebbe essere utile per messaggi temporanei
    console.log(message);
}

// Gestione delle tabs
$(document).on('click', '.admin-tab-btn', function() {
    const tabId = $(this).data('tab');
    
    // Rimuovi la classe active da tutti i pulsanti e pannelli
    $('.admin-tab-btn').removeClass('active');
    $('.admin-tab-content').removeClass('active');
    
    // Aggiungi la classe active al pulsante e pannello corrente
    $(this).addClass('active');
    $(`#${tabId}-tab`).addClass('active');
});