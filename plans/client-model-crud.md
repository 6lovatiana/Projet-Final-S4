# Plan : ClientModel — CRUD sur `clients`

## Contexte

Le projet est une application Mobile Money CodeIgniter 4 (PHP 8.2+, SQLite3). La table `clients` est définie dans `base.sql` :

```sql
CREATE TABLE clients (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    numero      VARCHAR(20) NOT NULL UNIQUE,
    solde       DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

Le fichier `app/Models/ClientModel.php` existe mais est vide (0 octet). Le `GUIDE_TECHNIQUE.md` fournit un `TransactionModel` de référence comme patron CI4.

---

## Fichier à modifier

`app/Models/ClientModel.php`

---

## Implémentation

### Propriétés CI4

| Propriété         | Valeur                                              | Raison |
|-------------------|-----------------------------------------------------|--------|
| `$table`          | `'clients'`                                         | Table cible |
| `$primaryKey`     | `'id'`                                              | Clé primaire auto-incrémentée |
| `$returnType`     | `'object'`                                          | Cohérent avec `TransactionModel` qui attend des objets (`$client->solde`) |
| `$useTimestamps`  | `true`                                              | Gestion automatique de `created_at` / `updated_at` |
| `$createdField`   | `'created_at'`                                      | Nom de la colonne |
| `$updatedField`   | `'updated_at'`                                      | Nom de la colonne |
| `$allowedFields`  | `['numero', 'solde']`                               | Champs modifiables (pas `id`, pas les timestamps) |

### Règles de validation

| Champ    | Règle              | Raison |
|----------|--------------------|--------|
| `numero` | `required\|is_unique[clients.numero,,id]` | Obligatoire + unique (exclut l'enregistrement courant en UPDATE) |
| `solde`  | `required\|decimal` | Doit être un nombre décimal |

### Méthodes

CI4 fournit déjà `find()`, `findAll()`, `insert()`, `update()`, `delete()`, `getWhere()` — pas besoin de les réécrire (KISS).

Une seule méthode custom est nécessaire :

#### `findByNumero(string $numero): ?object`

Recherche un client par son numéro de téléphone. Retourne l'objet client ou `null`.

**Utilité** : Le login client est "automatique par numéro de téléphone" (GUIDE_TECHNIQUE.md ligne 57-58). Cette méthode sera appelée par `AuthController::attempt()` pour :
1. Vérifier si le client existe déjà → le connecter
2. Sinon → créer le client puis le connecter

---

## Vérification

1. **Syntaxe PHP** : `php -l app/Models/ClientModel.php`
2. **Lint CI4** : vérifier que le fichier suit les conventions du projet (namespace `App\Models`, extends `CodeIgniter\Model`)
