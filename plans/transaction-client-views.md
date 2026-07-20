# Plan : TransactionModel + ClientController + Vues Client

## Contexte

Le `TransactionModel` et `ClientController` sont vides (0 octet). Les 5 vues client (`dashboard.php`, `depot.php`, `retrait.php`, `transfert.php`, `historique.php`) sont vides. Les routes sont déjà définies dans `Routes.php` (lignes 37-50). Le `ClientModel` est opérationnel avec `findByNumero()`. Le layout `layouts/main` est prêt (Bootstrap 5.3.3).

---

## Fichiers

| Fichier | Action |
|---------|--------|
| `app/Models/TransactionModel.php` | **Écrire** |
| `app/Controllers/ClientController.php` | **Écrire** |
| `app/Views/client/dashboard.php` | **Écrire** |
| `app/Views/client/depot.php` | **Écrire** |
| `app/Views/client/retrait.php` | **Écrire** |
| `app/Views/client/transfert.php` | **Écrire** |
| `app/Views/client/historique.php` | **Écrire** |

Aucune modification de Routes.php ou Filters.php nécessaire — tout est déjà en place.

---

## 1. TransactionModel (`app/Models/TransactionModel.php`)

Suit le patron du GUIDE_TECHNIQUE.md §5.

### Propriétés
- `$table = 'transactions'`
- `$returnType = 'object'`
- `$allowedFields = ['type_operation_id', 'client_id', 'client_destination_id', 'montant', 'frais', 'solde_avant', 'solde_apres']`
- Pas de timestamps (`created_at` géré par SQLite par défaut, pas dans `$allowedFields`)

### Méthodes

#### `calculerFrais(int $typeOperationId, float $montant): float`
- Lit la table `frais` filtrée par `type_operation_id` et tranche `min <= montant <= max`
- Retourne `valeur` ou `0.0` si aucun barème trouvé (ex: depot)

#### `depot(int $clientId, float $montant): array`
- Transaction SQL : crédite le solde du client (solde + montant, frais = 0)
- Insère la ligne `transactions`
- Retourne `['frais' => 0.0, 'solde' => $nouveauSolde]`

#### `retrait(int $clientId, float $montant): array`
- Transaction SQL : vérifie solde >= montant + frais
- Débite le solde (solde - montant - frais)
- Insère la ligne `transactions`
- Retourne `['frais' => $frais, 'solde' => $nouveauSolde]`

#### `transfert(int $clientId, int $clientDestinationId, float $montant): array`
- Transaction SQL : vérifie solde émetteur >= montant + frais
- Débite l'émetteur, crédite le destinataire
- Insère la ligne `transactions` avec `client_destination_id`
- Retourne `['frais' => $frais, 'solde' => $nouveauSolde]`

#### `getHistorique(int $clientId): array`
- Retourne les transactions du client, triées par `created_at DESC`
- Jointure sur `types_operation` pour le `libelle` (affichage)

#### `getCodeId(string $code): int` (private)
- Resout le code (`'depot'`, `'retrait'`, `'transfert'`) en `type_operation_id`
- Évite de passer l'ID depuis le controller

---

## 2. ClientController (`app/Controllers/ClientController.php`)

### Helper privé `clientId(): int`
- Retourne `(int) session()->get('client_id')`
- Utilisé par toutes les méthodes

### Méthodes

#### `dashboard()` — GET `client/`
- Charge le client via `ClientModel::find(clientId())`
- Affiche la vue `client/dashboard` avec le solde

#### `depot()` / `storeDepot()` — GET/POST `client/depot`
- GET : affiche le formulaire (champ `montant`)
- POST : valide `montant > 0`, appelle `TransactionModel::depot()`, flashdata succès, redirige vers dashboard

#### `retrait()` / `storeRetrait()` — GET/POST `client/retrait`
- GET : affiche le formulaire (champ `montant`)
- POST : valide `montant > 0`, appelle `TransactionModel::retrait()`
  - Attrape `RuntimeException` (solde insuffisant) → flashdata erreur, redirige

#### `transfert()` / `storeTransfert()` — GET/POST `client/transfert`
- GET : affiche le formulaire (champs `destinataire` + `montant`)
- POST : valide les champs, cherche le destinataire par `numero` via `ClientModel::findByNumero()`
  - Vérifie que destinataire existe et n'est pas l'émetteur
  - Appelle `TransactionModel::transfert()`
  - Attrape `RuntimeException` → flashdata erreur

#### `historique()` — GET `client/historique`
- Charge les transactions via `TransactionModel::getHistorique(clientId())`
- Affiche la vue `client/historique`

---

## 3. Vues Bootstrap

Toutes étendent `layouts/main` via `$this->extend('layouts/main')`.

### `dashboard.php`
- Carte centrée affichant le numéro et le solde du client

### `depot.php`
- Carte avec formulaire : champ `montant` (number, min=1) + bouton "Deposer"

### `retrait.php`
- Carte avec formulaire : champ `montant` (number, min=1) + bouton "Retirer"

### `transfert.php`
- Carte avec formulaire : champ `destinataire` (numéro, placeholder "033...") + champ `montant` + bouton "Transférer"

### `historique.php`
- Tableau Bootstrap avec colonnes : Date, Type, Montant, Frais, Solde avant, Solde après
- Message "Aucune transaction" si vide

### Pattern flash messages (toutes les vues avec POST)
```php
<?php $success = session()->getFlashdata('success'); ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= esc($success) ?></div>
<?php endif; ?>
<?php $error = session()->getFlashdata('error'); ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= esc($error) ?></div>
<?php endif; ?>
```

---

## Vérification

```bash
php -l app/Models/TransactionModel.php
php -l app/Controllers/ClientController.php
php -l app/Views/client/dashboard.php
php -l app/Views/client/depot.php
php -l app/Views/client/retrait.php
php -l app/Views/client/transfert.php
php -l app/Views/client/historique.php
```
