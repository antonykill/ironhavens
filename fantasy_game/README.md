# Fantasy Resource Game

Un gioco web fantasy con sistema di gestione risorse dove i giocatori possono costruire edifici per raccogliere risorse e sviluppare il proprio villaggio.

## Caratteristiche

- Sistema di registrazione e login degli utenti
- Gestione di 4 tipi di risorse: acqua, cibo, legno e pietra
- Vari edifici da costruire con tempi di costruzione realistici
- Produzione automatica di risorse basata sugli edifici costruiti
- Interfaccia utente moderna e responsive

## Requisiti di Sistema

- PHP 7.4 o superiore
- MySQL 5.7 o superiore
- Webserver (Apache, Nginx, ecc.)
- Browser moderno con JavaScript abilitato

## Installazione

1. Clona o scarica questo repository nella directory del tuo webserver

2. Crea un database MySQL per il gioco:

   ```sql
   CREATE DATABASE fantasy_game;
   ```

3. Importa lo schema del database:

   ```
   mysql -u username -p fantasy_game < setup/database_setup.sql
   ```

   Oppure usa phpMyAdmin o un altro strumento per importare il file `setup/database_setup.sql`

4. Modifica il file `backend.php` con le tue credenziali di accesso al database:

   ```php
   define('DB_HOST', 'localhost');     // Host del database
   define('DB_NAME', 'fantasy_game');  // Nome del database
   define('DB_USER', 'username');      // Username MySQL
   define('DB_PASS', 'password');      // Password MySQL
   ```

5. Assicurati che la directory `images/buildings` abbia i permessi di scrittura per il webserver

6. Accedi al gioco dal tuo browser:

   ```
   http://localhost/fantasy_game/
   ```

## Struttura del Progetto

- `index.php`: Pagina principale del gioco
- `backend.php`: API backend e funzioni del gioco
- `css/style.css`: Stili CSS
- `js/game.js`: JavaScript per l'interfaccia utente
- `images/buildings/`: Immagini degli edifici
- `setup/database_setup.sql`: Script SQL per creare il database

## Personalizzazione

### Aggiungere Nuovi Edifici

Per aggiungere nuovi tipi di edifici, aggiungi le relative informazioni alla tabella `building_types` nel database:

```sql
INSERT INTO building_types (
    name, 
    description, 
    level_required, 
    water_production, 
    food_production, 
    wood_production, 
    stone_production, 
    water_cost, 
    food_cost, 
    wood_cost, 
    stone_cost, 
    build_time_minutes, 
    image_url
) VALUES (
    'Nome Edificio', 
    'Descrizione', 
    2,  -- Livello giocatore richiesto
    5,  -- Produzione acqua
    0,  -- Produzione cibo
    0,  -- Produzione legno
    0,  -- Produzione pietra
    10, -- Costo acqua
    5,  -- Costo cibo
    20, -- Costo legno
    15, -- Costo pietra
    30, -- Tempo di costruzione in minuti
    'images/buildings/nome_edificio.png'
);
```

Poi aggiungi l'immagine corrispondente nella cartella `images/buildings/`.

### Modificare le Formule di Produzione

Per modificare il modo in cui le risorse vengono generate, puoi aggiornare la funzione `update_player_resources()` nel file `backend.php`. Ad esempio, puoi aggiungere moltiplicatori basati sul livello del giocatore o applicare bonus/malus in base ad altri fattori.

### Aggiungere Nuove Risorse

Per aggiungere un nuovo tipo di risorsa (ad esempio "oro" o "cristalli"):

1. Aggiungi una nuova colonna alla tabella `player_resources` nel database:

```sql
ALTER TABLE player_resources ADD COLUMN gold INT DEFAULT 0;
```

2. Aggiungi nuove colonne per la produzione e il costo nella tabella `building_types`:

```sql
ALTER TABLE building_types ADD COLUMN gold_production INT DEFAULT 0;
ALTER TABLE building_types ADD COLUMN gold_cost INT DEFAULT 0;
```

3. Aggiorna le funzioni nel file `backend.php` per gestire la nuova risorsa.

4. Aggiungi un nuovo contatore nell'interfaccia utente (index.php) e aggiorna il CSS e JavaScript.

## Suggerimenti per l'Estensione

Ecco alcune idee per estendere il gioco:

1. **Sistema di livelli e punti esperienza**: Aggiungi un meccanismo per far salire di livello i giocatori completando determinate attività.

2. **Sistema di missioni**: Implementa missioni e obiettivi che i giocatori possono completare per ottenere ricompense.

3. **Sistema di commercio**: Permetti ai giocatori di scambiare risorse tra loro o con NPC.

4. **Eventi casuali**: Aggiungi eventi casuali come tempeste, siccità o attacchi che influenzano le risorse dei giocatori.

5. **Miglioramento degli edifici**: Consenti ai giocatori di potenziare gli edifici esistenti per aumentare la produzione.

6. **Sistema di combattimento**: Aggiungi unità militari e sistemi di difesa per proteggere il villaggio.

## Licenza

Questo progetto è rilasciato sotto licenza MIT. Sentiti libero di utilizzarlo e modificarlo per i tuoi scopi.

## Supporto

Se hai domande o problemi con l'installazione, apri un issue nella pagina del repository o contatta il team di sviluppo.