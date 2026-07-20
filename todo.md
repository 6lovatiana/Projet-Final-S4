# TODO V1 — Répartition du travail (binôme)

Base commune déjà en place, à ne pas retoucher : schéma DB ([base.sql](base.sql)),
`.env` (SQLite), squelette CI4 nettoyé, **routes** (`app/Config/Routes.php`), **layout**
Bootstrap commun (`app/Views/layouts/main.php`) et helpers CI4 (`form`, `url`) actives
dans `app/Controllers/BaseController.php`. Détails techniques complets :
[GUIDE_TECHNIQUE.md](GUIDE_TECHNIQUE.md).

Toutes les routes des deux lots sont déjà déclarées dans `Routes.php` (vérifiable avec
`php spark routes`) et pointent vers des controllers/methodes qui n'existent pas encore
— normal, chaque lot doit juste créer le controller avec exactement les noms de methode
utilises dans les routes ci-dessous. Tant qu'un controller n'existe pas, sa route renvoie
une 404 (déjà vérifié), ça ne casse rien pour l'autre lot.

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
- [ ] `OperateurController::prefixes()` — GET, liste des préfixes + formulaire d'ajout
- [ ] `OperateurController::storePrefixe()` — POST `operateur/prefixes`, ajoute un préfixe
- [ ] `OperateurController::deletePrefixe($id)` — POST `operateur/prefixes/(:num)/delete`
- [ ] `OperateurController::typesOperation()` — GET, liste des types + barème de frais
      par tranche pour chaque type d'opération (valeurs initiales déjà en base)
- [ ] `OperateurController::updateFrais($id)` — POST `operateur/frais/(:num)`, modifie
      une ligne du barème (`min`/`max`/`valeur`)
- [ ] `OperateurController::comptes()` — GET, tableau de tous les clients (`clients.numero`,
      `clients.solde`), tri par solde
- [ ] `OperateurController::gains()` — GET, somme des `transactions.frais` perçus, groupée
      par `type_operation_id` (lecture seule sur `transactions`, table déjà créée)
- [ ] Vues Bootstrap (tableaux + formulaires) qui étendent `layouts/main` :
      `<?= $this->extend('layouts/main') ?>` / `<?= $this->section('content') ?>` / `<?= $this->endSection() ?>`

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
- [ ] `AuthController::login()` — GET, formulaire de saisie du numéro
- [ ] `AuthController::attempt()` — POST `login`, vérifie le préfixe (table `prefixes`,
      en lecture seule), crée le client s'il n'existe pas encore (login auto, aucune
      inscription), stocke `client_id` en session, redirige vers `client`
- [ ] `AuthController::logout()` — GET, détruit la session, redirige vers `/`
- [ ] `ClientAuthFilter` — protège les routes `/client/*` (redirige vers login si pas
      de session). Une fois créé, l'activer dans `Routes.php` en changeant :
      `$routes->group('client', static function ($routes) {...})` en
      `$routes->group('client', ['filter' => 'clientAuth'], static function ($routes) {...})`
      et enregistrer l'alias `'clientAuth' => \App\Filters\ClientAuthFilter::class`
      dans `app/Config/Filters.php` (`$aliases`)
- [ ] `TransactionModel::calculerFrais()` — lit le barème dans `frais` (lecture seule,
      la table est deja remplie par le Lot 1 ou par `base.sql`), voir l'exemple de code
      dans [GUIDE_TECHNIQUE.md §5](GUIDE_TECHNIQUE.md#5-extrait-de-code-cle--calcul-automatique-des-frais)
- [ ] `ClientController::dashboard()` — GET `client`, affiche le solde du client connecté
- [ ] `ClientController::depot()` / `storeDepot()` — GET formulaire / POST `client/depot`,
      crédite le solde, frais = 0, insère dans `transactions`
- [ ] `ClientController::retrait()` / `storeRetrait()` — GET formulaire / POST `client/retrait`,
      vérifie solde suffisant (montant + frais), débite, insère dans `transactions`
- [ ] `ClientController::transfert()` / `storeTransfert()` — GET formulaire / POST `client/transfert`,
      vérifie solde suffisant, débite l'émetteur, crédite le destinataire
      (`client_destination_id`), insère dans `transactions`
- [ ] `ClientController::historique()` — GET `client/historique`, liste des `transactions`
      du client connecté
- [ ] Vues Bootstrap (formulaires + tableaux) qui étendent `layouts/main` :
      `<?= $this->extend('layouts/main') ?>` / `<?= $this->section('content') ?>` / `<?= $this->endSection() ?>`

---

## Coordination (fichiers partagés déjà faits)

- **`app/Config/Routes.php`** — toutes les routes des deux lots sont déjà déclarées
  (groupes `operateur/*` et `client/*` + `login`/`logout`). Chacun crée uniquement son
  controller avec les noms de méthode exacts listés ci-dessus ; en principe plus besoin
  de toucher ce fichier (sauf activation du filtre `clientAuth` par le Lot 2).
- **`app/Views/layouts/main.php`** — layout Bootstrap commun avec navbar (liens vers
  toutes les pages des deux lots) déjà créé. Chaque vue doit l'étendre, ne pas le
  modifier sans prévenir l'autre :
  ```php
  <?= $this->extend('layouts/main') ?>
  <?= $this->section('content') ?>
      ... contenu de la page ...
  <?= $this->endSection() ?>
  ```
- **`app/Controllers/BaseController.php`** — helpers `form` et `url` déjà actives pour
  tous les controllers (utilisables directement : `site_url()`, `form_open()`, etc.)
- **`app/Config/App.php`** — `indexPage` vidé pour des URLs propres (`/client` au lieu
  de `/index.php/client`)

## Definition of Done — V1

- [ ] Les deux lots fusionnés dans `main`
- [ ] Parcours complet testable : login par numéro → dépôt → retrait → transfert →
      historique (côté client) + consultation gains/comptes (côté opérateur)
- [ ] Tag Git `v1` posé sur `main`
