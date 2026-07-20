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

## Lot 1 — Côté Opérateur ✅ Termine

**Fichiers créés (propres à ce lot, aucun croisement avec le Lot 2) :**

```
app/Models/PrefixModel.php          (nom reel : PrefixModel, sans "e" - deja cree)
app/Models/TypeOperationModel.php
app/Models/FraisModel.php
app/Controllers/OperateurController.php
app/Views/operateur/prefixes.php
app/Views/operateur/types_operation.php
app/Views/operateur/frais.php       (partial inclus par types_operation.php)
app/Views/operateur/comptes.php
app/Views/operateur/gains.php
app/Views/operateur/_flash.php      (partial messages succes/erreur, reutilise partout)
```

**Checklist et code :**

- [x] `PrefixModel` — CRUD simple sur `prefixes` (table: `id`, `prefixe`)

  `app/Models/PrefixModel.php`
  ```php
  <?php

  namespace App\Models;

  use CodeIgniter\Model;

  class PrefixModel extends Model
  {
      protected $table = 'prefixes';
      protected $primaryKey = 'id';

      protected $allowedFields = ['prefixe'];
      protected $useTimestamps = false;
  }
  ```

- [x] `TypeOperationModel` — CRUD sur `types_operation` (table: `id`, `code`, `libelle`)

  `app/Models/TypeOperationModel.php`
  ```php
  <?php

  namespace App\Models;

  use CodeIgniter\Model;

  class TypeOperationModel extends Model
  {
      protected $table = 'types_operation';
      protected $primaryKey = 'id';

      protected $allowedFields = ['code', 'libelle'];
      protected $useTimestamps = false;
  }
  ```

- [x] `FraisModel` — CRUD + `pourType($typeOperationId)` sur `frais` (table: `id`,
      `type_operation_id`, `min`, `max`, `valeur`)

  `app/Models/FraisModel.php`
  ```php
  <?php

  namespace App\Models;

  use CodeIgniter\Model;

  class FraisModel extends Model
  {
      protected $table = 'frais';
      protected $primaryKey = 'id';

      protected $allowedFields = ['type_operation_id', 'min', 'max', 'valeur'];
      protected $useTimestamps = false;

      /**
       * Bareme de frais d'un type d'operation, tri par tranche croissante.
       */
      public function pourType(int $typeOperationId): array
      {
          return $this->where('type_operation_id', $typeOperationId)
              ->orderBy('min', 'ASC')
              ->findAll();
      }
  }
  ```

- [x] `OperateurController` — `prefixes()` / `storePrefixe()` / `deletePrefixe()` (GET liste +
      formulaire, POST ajout avec validation format+unicite, POST suppression),
      `typesOperation()` / `updateFrais()` (GET barème par type, POST modification d'une
      tranche), `comptes()` / `gains()` (lecture directe des tables `clients` et
      `transactions` via `Database::connect()`, sans créer `ClientModel`/`TransactionModel`
      pour ne pas empiéter sur le Lot 2)

  `app/Controllers/OperateurController.php`
  ```php
  <?php

  namespace App\Controllers;

  use App\Models\FraisModel;
  use App\Models\PrefixModel;
  use App\Models\TypeOperationModel;
  use Config\Database;

  class OperateurController extends BaseController
  {
      protected PrefixModel $prefixeModel;
      protected TypeOperationModel $typeOperationModel;
      protected FraisModel $fraisModel;

      public function __construct()
      {
          $this->prefixeModel       = new PrefixModel();
          $this->typeOperationModel = new TypeOperationModel();
          $this->fraisModel         = new FraisModel();
      }

      // ------------------------------------------------------------------
      // Prefixes
      // ------------------------------------------------------------------

      public function prefixes()
      {
          return view('operateur/prefixes', [
              'prefixes' => $this->prefixeModel->orderBy('prefixe', 'ASC')->findAll(),
          ]);
      }

      public function storePrefixe()
      {
          $rules = [
              'prefixe' => 'required|regex_match[/^[0-9]{2,5}$/]|is_unique[prefixes.prefixe]',
          ];

          if (! $this->validate($rules)) {
              return redirect()->to('operateur/prefixes')->withInput()->with('errors', $this->validator->getErrors());
          }

          $this->prefixeModel->insert(['prefixe' => $this->request->getPost('prefixe')]);

          return redirect()->to('operateur/prefixes')->with('success', 'Prefixe ajoute.');
      }

      public function deletePrefixe(int $id)
      {
          $this->prefixeModel->delete($id);

          return redirect()->to('operateur/prefixes')->with('success', 'Prefixe supprime.');
      }

      // ------------------------------------------------------------------
      // Types d'operation & bareme de frais
      // ------------------------------------------------------------------

      public function typesOperation()
      {
          $types = $this->typeOperationModel->findAll();

          foreach ($types as &$type) {
              $type['frais'] = $this->fraisModel->pourType((int) $type['id']);
          }
          unset($type);

          return view('operateur/types_operation', ['types' => $types]);
      }

      public function updateFrais(int $id)
      {
          $rules = [
              'min'    => 'required|numeric',
              'max'    => 'required|numeric|greater_than[{min}]',
              'valeur' => 'required|numeric',
          ];

          if (! $this->validate($rules)) {
              return redirect()->to('operateur/types-operation')->with('errors', $this->validator->getErrors());
          }

          $this->fraisModel->update($id, [
              'min'    => $this->request->getPost('min'),
              'max'    => $this->request->getPost('max'),
              'valeur' => $this->request->getPost('valeur'),
          ]);

          return redirect()->to('operateur/types-operation')->with('success', 'Bareme mis a jour.');
      }

      // ------------------------------------------------------------------
      // Situation des comptes clients
      // Lecture seule sur `clients` (table geree par le Lot 2, pas de Model
      // partage ici pour ne pas empieter sur ClientModel.php du Lot 2).
      // ------------------------------------------------------------------

      public function comptes()
      {
          $clients = Database::connect()->table('clients')
              ->orderBy('solde', 'DESC')
              ->get()
              ->getResultArray();

          return view('operateur/comptes', ['clients' => $clients]);
      }

      // ------------------------------------------------------------------
      // Situation des gains via les frais (retrait / transfert)
      // Lecture seule sur `transactions` (table geree par le Lot 2).
      // ------------------------------------------------------------------

      public function gains()
      {
          $db = Database::connect();

          $gains = $db->table('transactions')
              ->select('types_operation.libelle AS libelle, SUM(transactions.frais) AS total_frais, COUNT(transactions.id) AS nb_operations')
              ->join('types_operation', 'types_operation.id = transactions.type_operation_id')
              ->groupBy('types_operation.id')
              ->get()
              ->getResultArray();

          $totalGeneral = array_sum(array_column($gains, 'total_frais'));

          return view('operateur/gains', [
              'gains'        => $gains,
              'totalGeneral' => $totalGeneral,
          ]);
      }
  }
  ```

- [x] Vues Bootstrap (tableaux + formulaires) qui étendent `layouts/main`, testées en HTTP
      (200 sur les 4 pages, ajout/suppression préfixe + modification frais vérifiés)

  `app/Views/operateur/_flash.php` (partial messages succes/erreur, reutilise partout)
  ```php
  <?php if (session()->getFlashdata('success')) : ?>
      <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('errors')) : ?>
      <div class="alert alert-danger">
          <ul class="mb-0">
              <?php foreach (session()->getFlashdata('errors') as $error) : ?>
                  <li><?= esc($error) ?></li>
              <?php endforeach; ?>
          </ul>
      </div>
  <?php endif; ?>
  ```

  `app/Views/operateur/prefixes.php`
  ```php
  <?= $this->extend('layouts/main') ?>

  <?= $this->section('content') ?>
  <h1 class="h4 mb-4">Prefixes valables</h1>

  <?= $this->include('operateur/_flash') ?>

  <div class="row">
      <div class="col-md-6">
          <table class="table table-bordered bg-white">
              <thead>
                  <tr>
                      <th>Prefixe</th>
                      <th class="text-end">Action</th>
                  </tr>
              </thead>
              <tbody>
                  <?php foreach ($prefixes as $prefixe) : ?>
                      <tr>
                          <td><?= esc($prefixe['prefixe']) ?></td>
                          <td class="text-end">
                              <form method="post" action="<?= site_url('operateur/prefixes/' . $prefixe['id'] . '/delete') ?>"
                                    onsubmit="return confirm('Supprimer ce prefixe ?');">
                                  <?= csrf_field() ?>
                                  <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                              </form>
                          </td>
                      </tr>
                  <?php endforeach; ?>
                  <?php if (empty($prefixes)) : ?>
                      <tr>
                          <td colspan="2" class="text-muted">Aucun prefixe configure.</td>
                      </tr>
                  <?php endif; ?>
              </tbody>
          </table>
      </div>

      <div class="col-md-6">
          <div class="card">
              <div class="card-body">
                  <h2 class="h6">Ajouter un prefixe</h2>
                  <form method="post" action="<?= site_url('operateur/prefixes') ?>">
                      <?= csrf_field() ?>
                      <div class="mb-3">
                          <label class="form-label" for="prefixe">Prefixe (ex: 033)</label>
                          <input type="text" class="form-control" id="prefixe" name="prefixe"
                                 value="<?= esc(old('prefixe')) ?>" maxlength="5" required>
                      </div>
                      <button type="submit" class="btn btn-primary">Ajouter</button>
                  </form>
              </div>
          </div>
      </div>
  </div>
  <?= $this->endSection() ?>
  ```

  `app/Views/operateur/types_operation.php`
  ```php
  <?= $this->extend('layouts/main') ?>

  <?= $this->section('content') ?>
  <h1 class="h4 mb-4">Types d'operation & bareme de frais</h1>

  <?= $this->include('operateur/_flash') ?>

  <?php foreach ($types as $type) : ?>
      <?= $this->setVar('type', $type)->include('operateur/frais') ?>
  <?php endforeach; ?>
  <?= $this->endSection() ?>
  ```

  `app/Views/operateur/frais.php` (partial inclus par `types_operation.php`, un formulaire
  Bootstrap par ligne du barème — `<form>` en grille plutôt qu'imbriqué dans un `<table>`
  pour rester en HTML valide)
  ```php
  <div class="card mb-4">
      <div class="card-header">
          <strong><?= esc($type['libelle']) ?></strong>
          <span class="text-muted">(<?= esc($type['code']) ?>)</span>
      </div>
      <div class="card-body">
          <?php if (empty($type['frais'])) : ?>
              <p class="text-muted mb-0">Aucun frais applicable (operation gratuite).</p>
          <?php else : ?>
              <div class="row fw-bold small text-muted mb-2">
                  <div class="col-3">Montant min</div>
                  <div class="col-3">Montant max</div>
                  <div class="col-3">Frais</div>
                  <div class="col-3"></div>
              </div>
              <?php foreach ($type['frais'] as $ligne) : ?>
                  <form method="post" action="<?= site_url('operateur/frais/' . $ligne['id']) ?>" class="row g-2 align-items-center mb-2">
                      <?= csrf_field() ?>
                      <div class="col-3">
                          <input type="number" step="0.01" class="form-control form-control-sm" name="min" value="<?= esc($ligne['min']) ?>">
                      </div>
                      <div class="col-3">
                          <input type="number" step="0.01" class="form-control form-control-sm" name="max" value="<?= esc($ligne['max']) ?>">
                      </div>
                      <div class="col-3">
                          <input type="number" step="0.01" class="form-control form-control-sm" name="valeur" value="<?= esc($ligne['valeur']) ?>">
                      </div>
                      <div class="col-3 text-end">
                          <button type="submit" class="btn btn-sm btn-outline-primary">Enregistrer</button>
                      </div>
                  </form>
              <?php endforeach; ?>
          <?php endif; ?>
      </div>
  </div>
  ```

  `app/Views/operateur/comptes.php`
  ```php
  <?= $this->extend('layouts/main') ?>

  <?= $this->section('content') ?>
  <h1 class="h4 mb-4">Situation des comptes clients</h1>

  <table class="table table-bordered bg-white">
      <thead>
          <tr>
              <th>Numero</th>
              <th class="text-end">Solde</th>
          </tr>
      </thead>
      <tbody>
          <?php foreach ($clients as $client) : ?>
              <tr>
                  <td><?= esc($client['numero']) ?></td>
                  <td class="text-end"><?= number_format((float) $client['solde'], 2, ',', ' ') ?> Ar</td>
              </tr>
          <?php endforeach; ?>
          <?php if (empty($clients)) : ?>
              <tr>
                  <td colspan="2" class="text-muted">Aucun client pour le moment.</td>
              </tr>
          <?php endif; ?>
      </tbody>
  </table>
  <?= $this->endSection() ?>
  ```

  `app/Views/operateur/gains.php`
  ```php
  <?= $this->extend('layouts/main') ?>

  <?= $this->section('content') ?>
  <h1 class="h4 mb-4">Situation des gains (frais percus)</h1>

  <table class="table table-bordered bg-white">
      <thead>
          <tr>
              <th>Type d'operation</th>
              <th class="text-end">Nb operations</th>
              <th class="text-end">Total frais percus</th>
          </tr>
      </thead>
      <tbody>
          <?php foreach ($gains as $ligne) : ?>
              <tr>
                  <td><?= esc($ligne['libelle']) ?></td>
                  <td class="text-end"><?= (int) $ligne['nb_operations'] ?></td>
                  <td class="text-end"><?= number_format((float) $ligne['total_frais'], 2, ',', ' ') ?> Ar</td>
              </tr>
          <?php endforeach; ?>
          <?php if (empty($gains)) : ?>
              <tr>
                  <td colspan="3" class="text-muted">Aucune operation payante enregistree.</td>
              </tr>
          <?php endif; ?>
      </tbody>
      <tfoot>
          <tr class="fw-bold">
              <td colspan="2">Total general</td>
              <td class="text-end"><?= number_format((float) $totalGeneral, 2, ',', ' ') ?> Ar</td>
          </tr>
      </tfoot>
  </table>
  <?= $this->endSection() ?>
  ```

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
