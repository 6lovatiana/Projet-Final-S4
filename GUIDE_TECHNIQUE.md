# Guide Technique - Simulation Operateur Mobile Money

## 1. Environnement verifie

| Composant       | Version / Etat                                   |
|-----------------|---------------------------------------------------|
| PHP             | 8.2.31 (>= 8.1 requis par CodeIgniter 4)           |
| Extension SQLite3 | Chargee (`sqlite3`, `pdo_sqlite`)                |
| CodeIgniter     | ^4.7 (installe via `composer.phar install`)        |
| Base de donnees | SQLite3, fichier `writable/database.db`            |

Commandes de verification (a rejouer si besoin sur un autre poste) :

```bash
php -v
php -m | grep -i sqlite
php -r "var_dump(extension_loaded('sqlite3'), extension_loaded('pdo_sqlite'));"
```

## 2. Configuration realisee

- `.env` cree a la racine (non versionne, voir `.gitignore`) avec :
  ```
  CI_ENVIRONMENT = development
  app.baseURL = 'http://localhost:8080/'
  database.default.DBDriver = SQLite3
  database.default.database = database.db
  database.default.foreignKeys = true
  ```
  > Important (specifique Windows) : le driver SQLite3 de CodeIgniter prefixe automatiquement
  > la valeur de `database` avec `WRITEPATH` **sauf** si elle contient deja un `DIRECTORY_SEPARATOR`
  > (`\` sous Windows). Comme `/` n'est pas reconnu comme separateur sous Windows, il faut laisser
  > `database.db` (sans chemin) pour que CodeIgniter resolve correctement vers `writable/database.db`.
  > Mettre `writable/database.db` provoque une erreur `unable to open database file` (chemin double).

- Nettoyage CI4 : suppression de `app/Views/welcome_message.php`, `Home::index()` pointe vers une
  vue `home.php` minimale (Bootstrap 5 via CDN) en attendant l'ecran de login.

- Base de donnees generee depuis `base.sql` :
  ```bash
  php -r '$db = new SQLite3("writable/database.db"); $db->exec(file_get_contents("base.sql"));'
  php spark db:table frais   # verification rapide
  ```

## 3. TODO List - Version 1

### Cote operateur
- [x] Table `prefixes` + seed (033, 037)
- [x] Table `types_operation` + seed (depot, retrait, transfert)
- [x] Table `frais` (bareme par tranche, rattache a `type_operation_id`, modifiable)
- [ ] CRUD prefixes (ajouter/supprimer un prefixe valable)
- [ ] CRUD types d'operation + bareme de frais (ecran d'admin, valeurs modifiables)
- [ ] Ecran "Situation gain" : somme des frais percus, filtrable par type d'operation (retrait / transfert)
- [ ] Ecran "Situation des comptes clients" : liste des clients + soldes

### Cote client
- [ ] Login automatique par numero de telephone (aucune inscription prealable, creation
      implicite du client en base au premier login si le prefixe est valide)
- [ ] Ecran "Voir le solde"
- [ ] Depot (execution automatique, sans validation manuelle, frais = 0)
- [ ] Retrait (execution automatique, calcul du frais via le bareme, solde suffisant requis)
- [ ] Transfert vers un autre numero (calcul du frais, solde suffisant requis, credite le destinataire)
- [ ] Historique des operations du client (table `transactions`)

### Transverse
- [x] `base.sql` (schema + donnees initiales)
- [ ] Models CodeIgniter (Client, Prefixe, TypeOperation, Frais, Transaction)
- [ ] Filtre de session pour proteger les routes client (redirection vers login si non connecte)
- [ ] Integration Bootstrap 5 (layout commun `app/Views/layouts/main.php`)
- [ ] Tag Git `v1` a la livraison

## 4. Arborescence des fichiers (chemins CI4)

```
Projet-Final-S4/
├── base.sql
├── GUIDE_TECHNIQUE.md
├── Taches.md
├── .env
├── app/
│   ├── Config/
│   │   ├── Database.php        (inchange, pilote par .env)
│   │   ├── Routes.php
│   │   └── Filters.php         (a completer : filtre auth client)
│   ├── Controllers/
│   │   ├── BaseController.php
│   │   ├── Home.php
│   │   ├── AuthController.php       (a creer : login par numero)
│   │   ├── ClientController.php     (a creer : solde, depot, retrait, transfert, historique)
│   │   └── OperateurController.php  (a creer : prefixes, types, frais, gains, comptes)
│   ├── Models/
│   │   ├── ClientModel.php          (a creer)
│   │   ├── PrefixeModel.php         (a creer)
│   │   ├── TypeOperationModel.php   (a creer)
│   │   ├── FraisModel.php           (a creer)
│   │   └── TransactionModel.php     (a creer)
│   ├── Filters/
│   │   └── ClientAuthFilter.php     (a creer)
│   └── Views/
│       ├── home.php
│       ├── layouts/main.php         (a creer)
│       ├── auth/login.php           (a creer)
│       ├── client/
│       │   ├── dashboard.php
│       │   ├── depot.php
│       │   ├── retrait.php
│       │   ├── transfert.php
│       │   └── historique.php
│       └── operateur/
│           ├── prefixes.php
│           ├── types_operation.php
│           ├── frais.php
│           ├── comptes.php
│           └── gains.php
└── writable/
    └── database.db
```

## 5. Extrait de code cle : calcul automatique des frais

Exemple d'implementation prevue pour `app/Models/TransactionModel.php`. Le calcul du frais
s'appuie sur la table `frais` filtree par `type_operation_id` et par tranche de montant
(`min` <= montant <= `max`).

```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class TransactionModel extends Model
{
    protected $table      = 'transactions';
    protected $allowedFields = [
        'type_operation_id', 'client_id', 'client_destination_id',
        'montant', 'frais', 'solde_avant', 'solde_apres',
    ];

    /**
     * Calcule le frais applicable pour un type d'operation et un montant donnes
     * a partir du bareme stocke dans la table `frais`.
     */
    public function calculerFrais(int $typeOperationId, float $montant): float
    {
        $bareme = $this->db->table('frais')
            ->where('type_operation_id', $typeOperationId)
            ->where('min <=', $montant)
            ->where('max >=', $montant)
            ->get()
            ->getRow();

        return $bareme->valeur ?? 0.0;
    }

    /**
     * Effectue un retrait : verifie le solde, calcule le frais, met a jour le
     * solde client et journalise l'operation. Tout est englobe dans une
     * transaction SQL pour garantir la coherence du solde.
     */
    public function retrait(int $clientId, int $typeOperationId, float $montant): array
    {
        $clientModel = model(ClientModel::class);

        $this->db->transStart();

        $client = $clientModel->find($clientId);
        $frais  = $this->calculerFrais($typeOperationId, $montant);
        $total  = $montant + $frais;

        if ($client->solde < $total) {
            $this->db->transRollback();
            throw new \RuntimeException('Solde insuffisant.');
        }

        $soldeApres = $client->solde - $total;

        $clientModel->update($clientId, ['solde' => $soldeApres]);

        $this->insert([
            'type_operation_id' => $typeOperationId,
            'client_id'         => $clientId,
            'montant'           => $montant,
            'frais'             => $frais,
            'solde_avant'       => $client->solde,
            'solde_apres'       => $soldeApres,
        ]);

        $this->db->transComplete();

        return ['frais' => $frais, 'solde' => $soldeApres];
    }
}
```

> Le meme principe (calcul du frais via `calculerFrais()`, puis mise a jour du/des solde(s)
> dans une transaction SQL) s'applique au depot (frais = 0, pas d'entree dans `frais`) et au
> transfert (debit de l'emetteur + credit du destinataire, `client_destination_id` renseigne).

## 6. Contraintes techniques rappelees

- CSS : Bootstrap (CDN, voir `app/Views/home.php`)
- Login client : automatique par numero de telephone, sans inscription prealable
- Livraison : tag Git `v1`
