-- =====================================================================
-- base.sql - Simulation Operateur Mobile Money
-- SGBD cible : SQLite3 (writable/database.db)
-- =====================================================================

PRAGMA foreign_keys = ON;

-- ---------------------------------------------------------------------
-- Table : prefixes
-- Prefixes telephoniques valables. `status` distingue :
--   - 'principal' : notre operateur (ex: 033, 037) - utilise pour le login client
--   - 'autre'     : un autre operateur (ex: 032, 031) - reconnu uniquement comme
--                   destination de transfert, jamais pour se logger. `pourcentage_commission`
--                   n'est utilise que pour les prefixes 'autre'.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS prefixes;
CREATE TABLE prefixes (
    id                      INTEGER PRIMARY KEY AUTOINCREMENT,
    prefixe                 VARCHAR(5) NOT NULL UNIQUE,
    status                  VARCHAR(10) NOT NULL DEFAULT 'principal',
    pourcentage_commission  DECIMAL(5,2) NOT NULL DEFAULT 0
);

INSERT INTO prefixes (prefixe, status, pourcentage_commission) VALUES
    ('033', 'principal', 0),
    ('037', 'principal', 0),
    ('032', 'autre', 2),
    ('031', 'autre', 2);

-- ---------------------------------------------------------------------
-- Table : promotions
-- Promotion en pourcentage appliquee uniquement sur les frais de
-- transfert interne.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS promotions;
CREATE TABLE promotions (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    pourcentage  DECIMAL(5,2) NOT NULL,
    actif        INTEGER NOT NULL DEFAULT 0,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------------------
-- Table : clients
-- Un client = un numero de telephone (login automatique, pas d'inscription)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS clients;
CREATE TABLE clients (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    numero      VARCHAR(20) NOT NULL UNIQUE,
    solde       DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE clients ADD COLUMN pourcentage_epargne DECIMAL(5,2) NOT NULL DEFAULT 0;
ALTER TABLE clients ADD COLUMN solde_epargne DECIMAL(15,2) NOT NULL DEFAULT 0;

-- ---------------------------------------------------------------------
-- Table : types_operation
-- depot, retrait, transfert
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS types_operation;
CREATE TABLE types_operation (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    code     VARCHAR(20) NOT NULL UNIQUE,
    libelle  VARCHAR(50) NOT NULL
);

INSERT INTO types_operation (code, libelle) VALUES
    ('depot', 'Depot'),
    ('epargne', 'Mise en epargne'),
    ('retrait', 'Retrait'),
    ('transfert', 'Transfert');

-- ---------------------------------------------------------------------
-- Table : frais
-- Bareme de frais par tranche de montant, propre a chaque type
-- d'operation (modifiable). Le depot est gratuit (aucune ligne).
-- Le retrait et le transfert partagent le meme bareme initial ci-dessous,
-- mais peuvent etre modifies independamment puisqu'ils sont rattaches
-- a type_operation_id.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS frais;
CREATE TABLE frais (
    id                 INTEGER PRIMARY KEY AUTOINCREMENT,
    type_operation_id  INTEGER NOT NULL,
    min                DECIMAL(15,2) NOT NULL,
    max                DECIMAL(15,2) NOT NULL,
    valeur             DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (type_operation_id) REFERENCES types_operation(id)
);

-- Bareme applique au RETRAIT
INSERT INTO frais (type_operation_id, min, max, valeur)
SELECT id, 100,     1000,    50   FROM types_operation WHERE code = 'retrait'
UNION ALL SELECT id, 1001,    5000,    50   FROM types_operation WHERE code = 'retrait'
UNION ALL SELECT id, 5001,    10000,   100  FROM types_operation WHERE code = 'retrait'
UNION ALL SELECT id, 10001,   25000,   200  FROM types_operation WHERE code = 'retrait'
UNION ALL SELECT id, 25001,   50000,   400  FROM types_operation WHERE code = 'retrait'
UNION ALL SELECT id, 50001,   100000,  800  FROM types_operation WHERE code = 'retrait'
UNION ALL SELECT id, 100001,  250000,  1500 FROM types_operation WHERE code = 'retrait'
UNION ALL SELECT id, 250001,  500000,  1500 FROM types_operation WHERE code = 'retrait'
UNION ALL SELECT id, 500001,  1000000, 2500 FROM types_operation WHERE code = 'retrait'
UNION ALL SELECT id, 1000001, 2000000, 3000 FROM types_operation WHERE code = 'retrait';

-- Bareme applique au TRANSFERT
INSERT INTO frais (type_operation_id, min, max, valeur)
SELECT id, 100,     1000,    50   FROM types_operation WHERE code = 'transfert'
UNION ALL SELECT id, 1001,    5000,    50   FROM types_operation WHERE code = 'transfert'
UNION ALL SELECT id, 5001,    10000,   100  FROM types_operation WHERE code = 'transfert'
UNION ALL SELECT id, 10001,   25000,   200  FROM types_operation WHERE code = 'transfert'
UNION ALL SELECT id, 25001,   50000,   400  FROM types_operation WHERE code = 'transfert'
UNION ALL SELECT id, 50001,   100000,  800  FROM types_operation WHERE code = 'transfert'
UNION ALL SELECT id, 100001,  250000,  1500 FROM types_operation WHERE code = 'transfert'
UNION ALL SELECT id, 250001,  500000,  1500 FROM types_operation WHERE code = 'transfert'
UNION ALL SELECT id, 500001,  1000000, 2500 FROM types_operation WHERE code = 'transfert'
UNION ALL SELECT id, 1000001, 2000000, 3000 FROM types_operation WHERE code = 'transfert';

-- ---------------------------------------------------------------------
-- Table : transactions
-- Historique des operations (necessaire pour "voir les historiques",
-- "situation des comptes clients" et "situation gain via les frais").
-- client_destination_id est utilise uniquement pour un transfert interne.
-- numero_externe est utilise a la place pour un transfert vers un prefixe
-- 'autre' (pas de client chez nous a crediter) ; frais = notre gain,
-- commission = part due a l'autre operateur (transferts externes
-- uniquement), frais_retrait_inclus = majoration ajoutee au montant
-- credite si l'emetteur a coche l'option "frais de retrait inclus".
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS transactions;
CREATE TABLE transactions (
    id                      INTEGER PRIMARY KEY AUTOINCREMENT,
    type_operation_id       INTEGER NOT NULL,
    client_id               INTEGER NOT NULL,
    client_destination_id   INTEGER,
    numero_externe          VARCHAR(20),
    montant                 DECIMAL(15,2) NOT NULL,
    frais                   DECIMAL(15,2) NOT NULL DEFAULT 0,
    commission              DECIMAL(15,2) NOT NULL DEFAULT 0,
    frais_retrait_inclus    DECIMAL(15,2) NOT NULL DEFAULT 0,
    solde_avant             DECIMAL(15,2) NOT NULL,
    solde_apres             DECIMAL(15,2) NOT NULL,
    created_at              DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (type_operation_id) REFERENCES types_operation(id),
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (client_destination_id) REFERENCES clients(id)
);
