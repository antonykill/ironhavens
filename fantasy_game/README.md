# Ironhaven

Un gioco web fantasy con sistema di gestione risorse dove i giocatori possono costruire edifici per raccogliere risorse e sviluppare il proprio villaggio.

## Caratteristiche

- Sistema di registrazione e login degli utenti
- Gestione di 4 tipi di risorse: acqua, cibo, legno e pietra
- Vari edifici da costruire con tempi di costruzione realistici
- Produzione automatica di risorse basata sugli edifici costruiti
- Sistema di dipendenze tra edifici e albero tecnologico
- Interfaccia utente moderna e responsive
- Pannello di amministrazione

## Requisiti di Sistema

- PHP 7.4 o superiore
- MySQL 5.7 o superiore
- Webserver (Apache, Nginx, ecc.)
- Browser moderno con JavaScript abilitato

## Struttura del Progetto

```
ironhaven/
├── index.php                # Punto di ingresso principale
├── api.php                  # Endpoint per le API
├── config.php               # Configurazione generale
├── logout.php               # Gestione del logout
├── includes/                # File di inclusione
│   ├── functions.php        # Funzioni principali
│   ├── api.php              # Gestione delle API
│   ├── db.php               # Funzioni database
│   ├── auth.php             # Autenticazione e autorizzazione
│   ├── building.php         # Funzioni per gli edifici
│   ├── experience.php       # NUOVO: Gestione dell'esperienza
│   └── game_logging.php     # NUOVO: Funzioni per il logging del gioco
├── assets/                  # Risorse statiche
│   ├── css/                 # Fogli di stile
│   │   ├── style.css        # Stile principale
│   │   ├── admin.css        # Stile del pannello admin
│   │   ├── profile.css      # Stile della pagina profilo
│   │   ├── tech-tree.css    # Stile dell'albero tecnologico
│   │   └── admin_logs.css   # NUOVO: Stile per la pagina dei log di amministrazione
│   ├── js/                  # JavaScript 
│   │   ├── game.js          # Script principale del gioco
│   │   └── admin.js         # Script del pannello admin
│   └── images/              # Immagini
│       └── buildings/       # Immagini degli edifici
├── pages/                   # Pagine del gioco
│   ├── profile.php          # Pagina profilo
│   ├── admin.php            # Pannello admin
│   ├── tech-tree.php        # Albero tecnologico
│   └── admin_logs.php       # NUOVO: Pagina per visualizzare i log di amministrazione
├── templates/               # Template HTML
│   ├── footer.php           # Template footer
│   ├── game.php             # Template gioco
│   ├── header.php           # Template header
│   └── level_progress.php   # Template progresso livello
└── setup/                   # File di setup e installazione
    ├── database.sql         # Schema del database
    ├── edifici.sql          # NUOVO: Dati degli edifici
    ├── xd_constants.sql     # NUOVO: Costanti del gioco
    └── game-logs-table.sql  # NUOVO: Schema della tabella dei log di gioco
```

## Installazione

1. Clona o scarica questo repository nella directory del tuo webserver

2. Crea un database MySQL per il gioco:

   ```sql
   CREATE DATABASE ironhaven;
   ```

3. Importa lo schema del database:

   ```
   mysql -u username -p ironhaven < setup/database.sql
   ```

   Oppure usa phpMyAdmin o un altro strumento per importare il file `setup/database.sql`

4. Modifica il file `config.php` con le tue credenziali di accesso al database:

   ```php
   define('DB_HOST', 'localhost');  // Host del database
   define('DB_NAME', 'ironhaven');  // Nome del database
   define('DB_USER', 'username');  // Username MySQL
   define('DB_PASS', 'password');  // Password MySQL
   ```

5. Assicurati che tutte le cartelle abbiano i permessi di scrittura corretti:
   ```
   chmod -R 755 assets/images/
   chmod -R 755 backup/
   ```

6. Accedi al gioco dal tuo browser:
   ```
   http://localhost/ironhaven/
   ```

## Account amministratore predefinito

Per accedere alle funzionalità di amministrazione, puoi utilizzare l'account predefinito:

- Username: admin
- Password: admin123

**Importante**: Cambia immediatamente questa password dopo il primo accesso!

## Personalizzazione

### Aggiungere Nuovi Edifici

È possibile aggiungere nuovi tipi di edifici dal pannello di amministrazione:

1. Accedi con un account amministratore
2. Vai alla sezione "Edifici" del pannello amministrativo
3. Clicca su "Aggiungi Nuovo Edificio"
4. Compila il form con i dettagli del nuovo edificio

Oppure puoi inserirli direttamente nel database:

```sql
INSERT INTO building_types (
    name, 
    description, 
    level_required, 
    water_production, 
    food_production, 
    wood_production, 
    stone_production, 
    capacity_increase,
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
    0,  -- Aumento capacità
    10, -- Costo acqua
    5,  -- Costo cibo
    20, -- Costo legno
    15, -- Costo pietra
    30, -- Tempo di costruzione in minuti
    'assets/images/buildings/nome_edificio.png'
);
```

### Modifica delle Dipendenze tra Edifici

Le dipendenze tra edifici possono essere gestite con la tabella `building_dependencies`:

```sql
INSERT INTO building_dependencies (
    building_type_id, 
    required_building_id, 
    required_building_level
) VALUES (
    8,  -- ID dell'edificio che richiede un prerequisito
    1,  -- ID dell'edificio prerequisito
    2   -- Livello minimo richiesto dell'edificio prerequisito
);
```

### Modificare le Formule di Produzione

Per modificare il modo in cui le risorse vengono generate, puoi aggiornare la funzione `update_player_resources()` nel file `includes/building.php`.

### Aggiungere Nuove Risorse

Per aggiungere un nuovo tipo di risorsa (ad esempio "oro" o "cristalli"):

1. Aggiungi una nuova colonna alla tabella `player_resources` nel database
2. Aggiungi nuove colonne per la produzione e il costo nella tabella `building_types`
3. Aggiorna le funzioni nel file `includes/building.php` per gestire la nuova risorsa
4. Aggiungi un nuovo contatore nell'interfaccia utente (index.php) e aggiorna il CSS e JavaScript

## Funzionalità avanzate

### Sistema dell'albero tecnologico

Il gioco include un sistema di albero tecnologico che mostra visivamente il percorso di progressione degli edifici. Per accedervi, gli utenti possono:

1. Cliccare sul link "Albero Tecnologico" nella barra di navigazione
2. Visualizzare quali edifici sono sbloccati e quali sono ancora bloccati
3. Vedere i requisiti necessari per sbloccare gli edifici più avanzati

### Pannello di amministrazione

Il pannello di amministrazione offre diverse funzionalità:

1. **Gestione Utenti**: visualizzazione, modifica e eliminazione degli account
2. **Gestione Edifici**: aggiunta, modifica e visualizzazione degli edifici
3. **Impostazioni di Gioco**: modifica delle costanti di gioco
4. **Statistiche**: visualizzazione delle statistiche di gioco
5. **Backup del Database**: creazione di backup manuali

## Sviluppo futuro

Ecco alcune idee per estendere il gioco:

1. **Sistema di eventi casuali**: tempeste, carestie o altri eventi che influenzano le risorse
2. **Sistema di combattimento**: unità militari e sistemi di difesa
3. **Commercio tra giocatori**: scambio di risorse
4. **Missioni e obiettivi**: sfide che offrono ricompense
5. **Chat e interazione sociale**: comunicazione tra giocatori

## Contribuire

Se desideri contribuire al progetto:

1. Fai un fork del repository
2. Crea un branch per la tua modifica (`git checkout -b feature/nuova-funzionalita`)
3. Fai commit delle tue modifiche (`git commit -am 'Aggiunta nuova funzionalità'`)
4. Pusha il branch (`git push origin feature/nuova-funzionalita`)
5. Crea una Pull Request

## Licenza

Questo progetto è rilasciato sotto licenza MIT. Sentiti libero di utilizzarlo e modificarlo per i tuoi scopi.

## Supporto

Per domande o problemi, apri un issue nella pagina del repository o contatta il team di sviluppo all'indirizzo support@ironhaven.online