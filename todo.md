# TODO V1 — Répartition du travail (binôme)

Base commune déjà en place, à ne pas retoucher : schéma DB ([base.sql](base.sql)),
`.env` (SQLite), squelette CI4 nettoyé. Détails techniques complets : [GUIDE_TECHNIQUE.md](GUIDE_TECHNIQUE.md).

## Principe d'organisation

Le travail est coupé en 2 lots **independants** qui ne touchent quasiment jamais les
memes fichiers, pour eviter les conflits git :

- **Lot 1 — Côté Opérateur** (admin/back-office)
- **Lot 2 — Côté Client** (login + opérations)

Chacun lit dans les memes tables (`clients`, `prefixes`, `types_operation`, `frais`,
`transactions`), deja creees par `base.sql` — aucun besoin de se coordonner sur le
schema, il est fige.

Workflow git suggere :
- Une branche par lot (ex: `feature/operateur`, `feature/client`), partant de `main` a jour
- Chacun push sa branche + PR vers `main` quand sa partie est testee
- Seuls `app/Config/Routes.php` et `app/Views/layouts/main.php` sont partages (voir
  section Coordination) : petits fichiers, conflits faciles a resoudre a la main

---

## Lot 1 — Côté Opérateur

**Fichiers à créer (propres à ce lot, aucun croisement avec le Lot 2) :**

```
app/Models/PrefixeModel.php
app/Models/TypeOperationModel.php
app/Models/FraisModel.php
app/Controllers/OperateurController.php
app/Views/operateur/prefixes.php
app/Views/operateur/types_operation.php
app/Views/operateur/frais.php
app/Views/operateur/comptes.php
app/Views/operateur/gains.php
```

**Checklist :**

- [ ] `PrefixeModel` — CRUD simple sur `prefixes` (table: `id`, `prefixe`)
- [ ] `TypeOperationModel` — CRUD sur `types_operation` (table: `id`, `code`, `libelle`)
- [ ] `FraisModel` — CRUD sur `frais`, filtrable par `type_operation_id` (table: `id`,
      `type_operation_id`, `min`, `max`, `valeur`)
- [ ] `OperateurController::prefixes()` — lister/ajouter/supprimer un préfixe valable
- [ ] `OperateurController::typesOperation()` — lister/modifier le barème de frais
      par tranche pour chaque type d'opération (formulaire éditable, valeurs
      initiales déjà en base)
- [ ] `OperateurController::comptes()` — tableau de tous les clients (`clients.numero`,
      `clients.solde`), tri par solde
- [ ] `OperateurController::gains()` — somme des `transactions.frais` perçus, groupée
      par `type_operation_id` (lecture seule sur `transactions`, table déjà créée)
- [ ] Vues Bootstrap (tableaux + formulaires), route group `/operateur/*`

---

## Lot 2 — Côté Client

**Fichiers à créer (propres à ce lot, aucun croisement avec le Lot 1) :**

```
app/Models/ClientModel.php
app/Models/TransactionModel.php
app/Controllers/AuthController.php
app/Controllers/ClientController.php
app/Filters/ClientAuthFilter.php
app/Views/auth/login.php
app/Views/client/dashboard.php
app/Views/client/depot.php
app/Views/client/retrait.php
app/Views/client/transfert.php
app/Views/client/historique.php
```

**Checklist :**

- [ ] `ClientModel` — CRUD sur `clients` (`id`, `numero`, `solde`)
- [ ] `AuthController::login()` — saisie du numéro, vérifie le préfixe (table
      `prefixes`, en lecture seule), crée le client s'il n'existe pas encore
      (login auto, aucune inscription), stocke `client_id` en session
- [ ] `ClientAuthFilter` — protège les routes `/client/*` (redirige vers login si pas
      de session), à enregistrer dans `app/Config/Filters.php` (voir Coordination)
- [ ] `TransactionModel::calculerFrais()` — lit le barème dans `frais` (lecture seule,
      la table est deja remplie par le Lot 1 ou par `base.sql`), voir l'exemple de code
      dans [GUIDE_TECHNIQUE.md §5](GUIDE_TECHNIQUE.md#5-extrait-de-code-cle--calcul-automatique-des-frais)
- [ ] `ClientController::dashboard()` — affiche le solde du client connecté
- [ ] `ClientController::depot()` — crédite le solde, frais = 0, insère dans `transactions`
- [ ] `ClientController::retrait()` — vérifie solde suffisant (montant + frais),
      débite, insère dans `transactions`
- [ ] `ClientController::transfert()` — vérifie solde suffisant, débite l'émetteur,
      crédite le destinataire (`client_destination_id`), insère dans `transactions`
- [ ] `ClientController::historique()` — liste des `transactions` du client connecté
- [ ] Vues Bootstrap (formulaires + tableaux), route group `/client/*`

---

## Coordination (fichiers partagés)

Ces 2 fichiers seront touchés par les deux lots — ajouter ses lignes sans supprimer
celles de l'autre, conflits attendus mais triviaux à résoudre :

- **`app/Config/Routes.php`** — chacun ajoute son propre groupe de routes :
  ```php
  $routes->group('operateur', static function ($routes) { /* Lot 1 */ });
  $routes->group('client', static function ($routes) { /* Lot 2 */ });
  ```
- **`app/Views/layouts/main.php`** — layout Bootstrap commun (header/nav/footer),
  a créer une seule fois par la personne qui commence en premier, puis réutilisé par
  l'autre via `<?= $this->extend('layouts/main') ?>` sans le modifier

## Definition of Done — V1

- [ ] Les deux lots fusionnés dans `main`
- [ ] Parcours complet testable : login par numéro → dépôt → retrait → transfert →
      historique (côté client) + consultation gains/comptes (côté opérateur)
- [ ] Tag Git `v1` posé sur `main`
