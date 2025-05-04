<?php
/**
 * API Endpoint
 * 
 * Gestisce tutte le richieste API
 */

// Includi la configurazione
require_once 'config.php';

// Includi direttamente il file api.php degli includes
require_once 'includes/api.php';

// Processa la richiesta API
handle_api_request();