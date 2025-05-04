<?php
/**
 * Database Functions
 * 
 * Funzioni per la connessione e l'interazione con il database
 */

/**
 * Stabilisce una connessione al database
 * @return PDO L'oggetto PDO per la connessione al database
 * @throws PDOException Se la connessione fallisce
 */
function db_connect() {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log("Errore di connessione al database: " . $e->getMessage());
        throw new PDOException('Errore di connessione al database: ' . $e->getMessage());
    }
}

/**
 * Esegue una query SQL con parametri
 * @param string $sql La query SQL da eseguire
 * @param array $params I parametri per la query
 * @return PDOStatement L'oggetto PDOStatement risultante
 */
function db_query($sql, $params = []) {
    try {
        $pdo = db_connect();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Errore di query: " . $e->getMessage());
        throw new PDOException('Errore di query: ' . $e->getMessage());
    }
}

/**
 * Recupera una singola riga dal database
 * @param string $sql La query SQL da eseguire
 * @param array $params I parametri per la query
 * @param int $fetch_style Lo stile di fetch (default: PDO::FETCH_ASSOC)
 * @return mixed La riga recuperata o false se non trovata
 */
function db_fetch_row($sql, $params = [], $fetch_style = PDO::FETCH_ASSOC) {
    try {
        $stmt = db_query($sql, $params);
        return $stmt->fetch($fetch_style);
    } catch (PDOException $e) {
        error_log("Errore in db_fetch_row: " . $e->getMessage());
        return false;
    }
}

/**
 * Recupera tutte le righe dal database
 * @param string $sql La query SQL da eseguire
 * @param array $params I parametri per la query
 * @param int $fetch_style Lo stile di fetch (default: PDO::FETCH_ASSOC)
 * @return array L'array delle righe recuperate
 */
function db_fetch_all($sql, $params = [], $fetch_style = PDO::FETCH_ASSOC) {
    try {
        $stmt = db_query($sql, $params);
        return $stmt->fetchAll($fetch_style);
    } catch (PDOException $e) {
        error_log("Errore in db_fetch_all: " . $e->getMessage());
        return [];
    }
}

/**
 * Inserisce una riga nel database
 * @param string $table La tabella in cui inserire
 * @param array $data I dati da inserire (chiave => valore)
 * @return int|bool L'ID dell'ultima riga inserita, o false in caso di errore
 */
function db_insert($table, $data) {
    try {
        $pdo = db_connect();
        
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Errore in db_insert: " . $e->getMessage());
        return false;
    }
}

/**
 * Aggiorna una riga nel database
 * @param string $table La tabella da aggiornare
 * @param array $data I dati da aggiornare (chiave => valore)
 * @param string $where La condizione WHERE
 * @param array $where_params I parametri per la condizione WHERE
 * @return bool True se l'aggiornamento ha avuto successo, altrimenti false
 */
function db_update($table, $data, $where, $where_params = []) {
    try {
        $pdo = db_connect();
        
        $set_parts = [];
        foreach (array_keys($data) as $column) {
            $set_parts[] = "$column = ?";
        }
        $set_clause = implode(', ', $set_parts);
        
        $sql = "UPDATE $table SET $set_clause WHERE $where";
        
        $params = array_merge(array_values($data), $where_params);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Errore in db_update: " . $e->getMessage());
        return false;
    }
}

/**
 * Elimina righe dal database
 * @param string $table La tabella da cui eliminare
 * @param string $where La condizione WHERE
 * @param array $where_params I parametri per la condizione WHERE
 * @return bool True se l'eliminazione ha avuto successo, altrimenti false
 */
function db_delete($table, $where, $where_params = []) {
    try {
        $pdo = db_connect();
        
        $sql = "DELETE FROM $table WHERE $where";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($where_params);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Errore in db_delete: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica che una tabella esista nel database
 * @param string $table_name Il nome della tabella da verificare
 * @return bool True se la tabella esiste, altrimenti false
 */
function db_table_exists($table_name) {
    try {
        $pdo = db_connect();
        $stmt = $pdo->query("SHOW TABLES LIKE '$table_name'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Errore in db_table_exists: " . $e->getMessage());
        return false;
    }
}

/**
 * Inizia una transazione
 * @return PDO L'oggetto PDO con la transazione iniziata
 */
function db_transaction_begin() {
    try {
        $pdo = db_connect();
        $pdo->beginTransaction();
        return $pdo;
    } catch (PDOException $e) {
        error_log("Errore nell'avvio della transazione: " . $e->getMessage());
        throw new PDOException('Errore nell\'avvio della transazione: ' . $e->getMessage());
    }
}

/**
 * Esegue il commit di una transazione
 * @param PDO $pdo L'oggetto PDO con la transazione in corso
 * @return bool True se il commit ha avuto successo
 */
function db_transaction_commit($pdo) {
    try {
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        error_log("Errore nel commit della transazione: " . $e->getMessage());
        return false;
    }
}

/**
 * Esegue il rollback di una transazione
 * @param PDO $pdo L'oggetto PDO con la transazione in corso
 * @return bool True se il rollback ha avuto successo
 */
function db_transaction_rollback($pdo) {
    try {
        $pdo->rollBack();
        return true;
    } catch (PDOException $e) {
        error_log("Errore nel rollback della transazione: " . $e->getMessage());
        return false;
    }
}