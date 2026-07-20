# TODO V1 — Répartition du travail (binôme:ETU004311; ETU003968)


## Principe d'organisation


- **Branche:dev_misaina_login_auto 
— Côté Opérateur** (admin/back-office)
- **Brache:dev_toavina_client 
— Côté Client** (login + opérations)

Chacun lit dans les memes tables (`clients`, `prefixes`, `types_operation`, `frais`,
`transactions`), deja creees par `base.sql` — aucun besoin de se coordonner sur le
schema, il est fige.

Workflow git suggere :
- Une branche par partie (ex: `feature/operateur`, `feature/client`), partant de `main` a jour
- Chacun push sa branche + PR vers `dev` quand sa partie est testee
- Seuls `app/Config/Routes.php` et `app/Views/layouts/main.php` sont partages (voir
  section Coordination) : petits fichiers, conflits faciles a resoudre a la main

---

## Lot 1 — Côté Opérateur (ETU003968)Termine

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

       /** tri croissant **/

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

      
      public function comptes()
      {
          $clients = Database::connect()->table('clients')
              ->orderBy('solde', 'DESC')
              ->get()
              ->getResultArray();

          return view('operateur/comptes', ['clients' => $clients]);
      }

      

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

## Lot 2 — Côté Client (ETU004311) Termine

**Fichiers créés (propres à ce lot, aucun croisement avec le Lot 1) :**

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

**Checklist et code :**

- [x] `ClientModel` — CRUD sur `clients` (`id`, `numero`, `solde`), validation integree
      (numero unique, solde decimal) + `findByNumero()`

  `app/Models/ClientModel.php`
  ```php
  <?php

  namespace App\Models;

  use CodeIgniter\Model;

  class ClientModel extends Model
  {
      protected $table         = 'clients';
      protected $primaryKey    = 'id';
      protected $returnType    = 'object';
      protected $useTimestamps = true;
      protected $createdField  = 'created_at';
      protected $updatedField  = 'updated_at';

      protected $allowedFields = [
          'numero',
          'solde',
      ];

      protected $validationRules = [
          'numero' => 'required|is_unique[clients.numero,id]',
          'solde'  => 'required|decimal',
      ];

      protected $validationMessages = [
          'numero' => [
              'required'  => 'Le numero de telephone est obligatoire.',
              'is_unique' => 'Ce numero de telephone est deja utilise.',
          ],
          'solde' => [
              'required' => 'Le solde est obligatoire.',
              'decimal'  => 'Le solde doit etre un nombre decimal.',
          ],
      ];


      public function findByNumero(string $numero): ?object
      {
          return $this->where('numero', $numero)->first();
      }

      /**
       * Tous les clients sauf celui donne (suggestions de destinataire pour un transfert).
       */
      public function findAllExcept(int $excludeId): array
      {
          return $this->where('id !=', $excludeId)->findAll();
      }
  }
  ```

- [x] `AuthController::login()` — GET, formulaire de saisie du numéro
- [x] `AuthController::attempt()` — POST `login`, vérifie le préfixe (table `prefixes`,
      en lecture seule via `db_connect()`), crée le client s'il n'existe pas encore
      (login auto, aucune inscription), stocke `client_id` en session, redirige vers `client`
- [x] `AuthController::logout()` — GET, détruit la session, redirige vers `/`

  `app/Controllers/AuthController.php`
  ```php
  <?php

  namespace App\Controllers;

  use App\Models\ClientModel;

  class AuthController extends BaseController
  {
      
      public function login(): string
      {
          return view('auth/login');
      }

      
      public function attempt()
      {
          $numero = trim($this->request->getPost('numero') ?? '');

          if ($numero === '') {
              return redirect()->back()->withInput()->with('error', 'Veuillez saisir un numero de telephone.');
          }

          $prefixe = substr($numero, 0, 3);

          $prefixeValide = db_connect()->table('prefixes')
              ->where('prefixe', $prefixe)
              ->countAllResults() > 0;

          if (! $prefixeValide) {
              return redirect()->back()->withInput()->with('error', 'Le prefixe "' . esc($prefixe) . '" n\'est pas valable.');
          }

          $clientModel = new ClientModel();
          $client = $clientModel->findByNumero($numero);

          if ($client === null) {
              $clientModel->insert([
                  'numero' => $numero,
                  'solde'  => 0,
              ]);
              $client = $clientModel->findByNumero($numero);
          }

          session()->set('client_id', $client->id);

          return redirect()->to(site_url('client'));
      }

      
      public function logout()
      {
          session()->destroy();

          return redirect()->to(site_url('/'));
      }
  }
  ```

- [x] `ClientAuthFilter` — protège les routes `/client/*` (redirige vers login si pas
      de session), activé dans `Routes.php`
      (`$routes->group('client', ['filter' => 'clientAuth'], ...)`) et enregistré dans
      `app/Config/Filters.php` (`'clientAuth' => \App\Filters\ClientAuthFilter::class`)

  `app/Filters/ClientAuthFilter.php`
  ```php
  <?php

  namespace App\Filters;

  use CodeIgniter\Filters\FilterInterface;
  use CodeIgniter\HTTP\RequestInterface;
  use CodeIgniter\HTTP\ResponseInterface;

  class ClientAuthFilter implements FilterInterface
  {
     
      public function before(RequestInterface $request, $arguments = null)
      {
          if (! session()->get('client_id')) {
              return redirect()->to(site_url('login'));
          }
      }

      /**
       * Aucune action apres la requete.
       */
      public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
      {
      }
  }
  ```

- [x] `TransactionModel::calculerFrais()` — lit le barème dans `frais` (lecture seule),
      voir aussi l'exemple de code dans
      [GUIDE_TECHNIQUE.md §5](GUIDE_TECHNIQUE.md#5-extrait-de-code-cle--calcul-automatique-des-frais).
      `depot()` / `retrait()` / `transfert()` encapsulent chacune la mise a jour du/des
      solde(s) + l'insertion dans `transactions` dans une transaction SQL
      (`transStart()`/`transComplete()`), avec verification du solde et `throw` en cas
      de solde insuffisant. `getHistorique()` liste les operations d'un client.

  `app/Models/TransactionModel.php`
  ```php
  <?php

  namespace App\Models;

  use CodeIgniter\Model;

  class TransactionModel extends Model
  {
      protected $table      = 'transactions';
      protected $returnType = 'object';

      protected $allowedFields = [
          'type_operation_id',
          'client_id',
          'client_destination_id',
          'montant',
          'frais',
          'solde_avant',
          'solde_apres',
      ];

      
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

      
      public function depot(int $clientId, float $montant): array
      {
          $clientModel = model(ClientModel::class);

          $this->db->transStart();

          $client     = $clientModel->find($clientId);
          $soldeApres = $client->solde + $montant;

          $clientModel->update($clientId, ['solde' => $soldeApres]);

          $this->insert([
              'type_operation_id' => $this->getCodeId('depot'),
              'client_id'         => $clientId,
              'montant'           => $montant,
              'frais'             => 0.0,
              'solde_avant'       => $client->solde,
              'solde_apres'       => $soldeApres,
          ]);

          $this->db->transComplete();

          return ['frais' => 0.0, 'solde' => $soldeApres];
      }

      
      public function retrait(int $clientId, float $montant): array
      {
          $clientModel = model(ClientModel::class);

          $this->db->transStart();

          $client = $clientModel->find($clientId);
          $typeId = $this->getCodeId('retrait');
          $frais  = $this->calculerFrais($typeId, $montant);
          $total  = $montant + $frais;

          if ($client->solde < $total) {
              $this->db->transRollback();
              throw new \RuntimeException('Solde insuffisant.');
          }

          $soldeApres = $client->solde - $total;

          $clientModel->update($clientId, ['solde' => $soldeApres]);

          $this->insert([
              'type_operation_id' => $typeId,
              'client_id'         => $clientId,
              'montant'           => $montant,
              'frais'             => $frais,
              'solde_avant'       => $client->solde,
              'solde_apres'       => $soldeApres,
          ]);

          $this->db->transComplete();

          return ['frais' => $frais, 'solde' => $soldeApres];
      }

      
      public function transfert(int $clientId, int $clientDestinationId, float $montant): array
      {
          $clientModel = model(ClientModel::class);

          $this->db->transStart();

          $emetteur    = $clientModel->find($clientId);
          $destinataire = $clientModel->find($clientDestinationId);
          $typeId      = $this->getCodeId('transfert');
          $frais       = $this->calculerFrais($typeId, $montant);
          $total       = $montant + $frais;

          if ($emetteur->solde < $total) {
              $this->db->transRollback();
              throw new \RuntimeException('Solde insuffisant pour le transfert.');
          }

          $soldeEmetteur    = $emetteur->solde - $total;
          $soldeDestinataire = $destinataire->solde + $montant;

          $clientModel->update($clientId, ['solde' => $soldeEmetteur]);
          $clientModel->update($clientDestinationId, ['solde' => $soldeDestinataire]);

          $this->insert([
              'type_operation_id'       => $typeId,
              'client_id'               => $clientId,
              'client_destination_id'   => $clientDestinationId,
              'montant'                 => $montant,
              'frais'                   => $frais,
              'solde_avant'             => $emetteur->solde,
              'solde_apres'             => $soldeEmetteur,
          ]);

          $this->db->transComplete();

          return ['frais' => $frais, 'solde' => $soldeEmetteur];
      }

      
      public function getHistorique(int $clientId): array
      {
          return $this->select('transactions.*, types_operation.libelle AS type_libelle')
              ->join('types_operation', 'types_operation.id = transactions.type_operation_id')
              ->where('client_id', $clientId)
              ->orderBy('created_at', 'DESC')
              ->findAll();
      }

      /**
       * Numeros des destinataires deja utilises par ce client dans ses transferts,
       * du plus recent au plus ancien (sans doublon).
       */
      public function getDestinatairesRecents(int $clientId): array
      {
          $rows = $this->select('clients.numero')
              ->join('clients', 'clients.id = transactions.client_destination_id')
              ->where('transactions.client_id', $clientId)
              ->where('transactions.client_destination_id IS NOT NULL')
              ->orderBy('transactions.created_at', 'DESC')
              ->findAll();

          return array_values(array_unique(array_map(static fn ($row) => $row->numero, $rows)));
      }

      private function getCodeId(string $code): int
      {
          $row = $this->db->table('types_operation')
              ->where('code', $code)
              ->get()
              ->getRow();

          return (int) $row->id;
      }
  }
  ```

- [x] `ClientController::dashboard()` — GET `client`, affiche le solde du client connecté
- [x] `ClientController::depot()` / `storeDepot()` — GET formulaire / POST `client/depot`,
      crédite le solde, frais = 0, insère dans `transactions`
- [x] `ClientController::retrait()` / `storeRetrait()` — GET formulaire / POST `client/retrait`,
      vérifie solde suffisant (montant + frais), débite, insère dans `transactions`
- [x] `ClientController::transfert()` / `storeTransfert()` — GET formulaire / POST `client/transfert`,
      vérifie solde suffisant, débite l'émetteur, crédite le destinataire
      (`client_destination_id`), insère dans `transactions`
- [x] `ClientController::historique()` — GET `client/historique`, liste des `transactions`
      du client connecté

  `app/Controllers/ClientController.php`
  ```php
  <?php

  namespace App\Controllers;

  use App\Models\ClientModel;
  use App\Models\TransactionModel;

  class ClientController extends BaseController
  {
      
      private function clientId(): int
      {
          return (int) session()->get('client_id');
      }

      
      public function dashboard()
      {
          $clientModel = new ClientModel();
          $client = $clientModel->find($this->clientId());

          return view('client/dashboard', ['client' => $client]);
      }

      
      public function depot()
      {
          return view('client/depot');
      }

      
      public function storeDepot()
      {
          $montant = (float) $this->request->getPost('montant');

          if ($montant <= 0) {
              return redirect()->back()->with('error', 'Le montant doit etre superieur a 0.');
          }

          $transactionModel = new TransactionModel();
          $transactionModel->depot($this->clientId(), $montant);

          return redirect()->to(site_url('client'))->with('success', 'Depot de ' . number_format($montant, 0, ',', ' ') . ' effectue.');
      }

      
      public function retrait()
      {
          return view('client/retrait');
      }

      
      public function storeRetrait()
      {
          $montant = (float) $this->request->getPost('montant');

          if ($montant <= 0) {
              return redirect()->back()->with('error', 'Le montant doit etre superieur a 0.');
          }

          $transactionModel = new TransactionModel();

          try {
              $resultat = $transactionModel->retrait($this->clientId(), $montant);
          } catch (\RuntimeException $e) {
              return redirect()->back()->with('error', $e->getMessage());
          }

          return redirect()->to(site_url('client'))->with(
              'success',
              'Retrait de ' . number_format($montant, 0, ',', ' ') . ' effectue (frais : ' . number_format($resultat['frais'], 0, ',', ' ') . ').'
          );
      }

      /**
       * Suggestions de numeros : destinataires deja utilises par ce client en priorite,
       * puis les autres clients de la base.
       */
      public function transfert()
      {
          $clientModel      = new ClientModel();
          $transactionModel = new TransactionModel();

          $recents = $transactionModel->getDestinatairesRecents($this->clientId());
          $autres  = array_map(
              static fn ($client) => $client->numero,
              $clientModel->findAllExcept($this->clientId())
          );

          $suggestions = array_values(array_unique(array_merge($recents, $autres)));

          return view('client/transfert', ['suggestions' => $suggestions]);
      }

      
      public function storeTransfert()
      {
          $destinataire = trim($this->request->getPost('destinataire') ?? '');
          $montant      = (float) $this->request->getPost('montant');

          if ($destinataire === '') {
              return redirect()->back()->with('error', 'Veuillez saisir le numero du destinataire.');
          }

          if ($montant <= 0) {
              return redirect()->back()->with('error', 'Le montant doit etre superieur a 0.');
          }

          $clientModel = new ClientModel();
          $dest = $clientModel->findByNumero($destinataire);

          if ($dest === null) {
              return redirect()->back()->with('error', 'Le numero "' . esc($destinataire) . '" est introuvable.');
          }

          if ((int) $dest->id === $this->clientId()) {
              return redirect()->back()->with('error', 'Vous ne pouvez pas vous transférer a vous-meme.');
          }

          $transactionModel = new TransactionModel();

          try {
              $resultat = $transactionModel->transfert($this->clientId(), (int) $dest->id, $montant);
          } catch (\RuntimeException $e) {
              return redirect()->back()->with('error', $e->getMessage());
          }

          return redirect()->to(site_url('client'))->with(
              'success',
              'Transfert de ' . number_format($montant, 0, ',', ' ') . ' effectue (frais : ' . number_format($resultat['frais'], 0, ',', ' ') . ').'
          );
      }

      
      public function historique()
      {
          $transactionModel = new TransactionModel();
          $transactions = $transactionModel->getHistorique($this->clientId());

          return view('client/historique', ['transactions' => $transactions]);
      }
  }
  ```

- [x] Vues Bootstrap (formulaires + tableaux) qui étendent `layouts/main`, testées en HTTP
      (login → dépôt → retrait → transfert → historique vérifiés de bout en bout, calcul
      des frais correct)

  `app/Views/auth/login.php`
  ```php
  <?= $this->extend('layouts/main') ?>

  <?= $this->section('content') ?>
  <div class="d-flex flex-column justify-content-center align-items-center" style="min-height: 60vh;">
      <div class="card shadow-sm" style="max-width: 400px; width: 100%;">
          <div class="card-body p-4">
              <h1 class="h4 mb-3 text-center">Connexion</h1>

              <?php $error = session()->getFlashdata('error'); ?>
              <?php if ($error): ?>
                  <div class="alert alert-danger"><?= esc($error) ?></div>
              <?php endif; ?>

              <form method="post" action="<?= site_url('login') ?>">
                  <div class="mb-3">
                      <label for="numero" class="form-label">Numero de telephone</label>
                      <input
                          type="text"
                          class="form-control"
                          id="numero"
                          name="numero"
                          placeholder="Ex : 0331234567"
                          value="<?= esc(old('numero')) ?>"
                          required
                      >
                  </div>
                  <button type="submit" class="btn btn-primary w-100">Se connecter</button>
              </form>
          </div>
      </div>
  </div>
  <?= $this->endSection() ?>
  ```

  `app/Views/client/dashboard.php`
  ```php
  <?= $this->extend('layouts/main') ?>

  <?= $this->section('content') ?>
  <div class="row justify-content-center">
      <div class="col-md-6">

          <?php $success = session()->getFlashdata('success'); ?>
          <?php if ($success): ?>
              <div class="alert alert-success"><?= esc($success) ?></div>
          <?php endif; ?>

          <div class="card shadow-sm">
              <div class="card-body text-center p-4">
                  <h1 class="h4 mb-3">Mon solde</h1>
                  <p class="text-muted">Numero : <?= esc($client->numero) ?></p>
                  <p class="display-5 fw-bold text-success">
                      <?= number_format($client->solde, 2, ',', ' ') ?> Ar
                  </p>
              </div>
          </div>

      </div>
  </div>
  <?= $this->endSection() ?>
  ```

  `app/Views/client/depot.php`
  ```php
  <?= $this->extend('layouts/main') ?>

  <?= $this->section('content') ?>
  <div class="row justify-content-center">
      <div class="col-md-6">

          <?php $error = session()->getFlashdata('error'); ?>
          <?php if ($error): ?>
              <div class="alert alert-danger"><?= esc($error) ?></div>
          <?php endif; ?>

          <div class="card shadow-sm">
              <div class="card-body p-4">
                  <h1 class="h4 mb-3">Depot</h1>
                  <form method="post" action="<?= site_url('client/depot') ?>">
                      <?= csrf_field() ?>
                      <div class="mb-3">
                          <label for="montant" class="form-label">Montant (Ar)</label>
                          <input
                              type="number"
                              class="form-control"
                              id="montant"
                              name="montant"
                              min="1"
                              step="1"
                              placeholder="Ex : 5000"
                              value="<?= esc(old('montant')) ?>"
                              required
                          >
                      </div>
                      <button type="submit" class="btn btn-success w-100">Deposer</button>
                  </form>
              </div>
          </div>

      </div>
  </div>
  <?= $this->endSection() ?>
  ```

  `app/Views/client/retrait.php`
  ```php
  <?= $this->extend('layouts/main') ?>

  <?= $this->section('content') ?>
  <div class="row justify-content-center">
      <div class="col-md-6">

          <?php $error = session()->getFlashdata('error'); ?>
          <?php if ($error): ?>
              <div class="alert alert-danger"><?= esc($error) ?></div>
          <?php endif; ?>

          <div class="card shadow-sm">
              <div class="card-body p-4">
                  <h1 class="h4 mb-3">Retrait</h1>
                  <form method="post" action="<?= site_url('client/retrait') ?>">
                      <?= csrf_field() ?>
                      <div class="mb-3">
                          <label for="montant" class="form-label">Montant (Ar)</label>
                          <input
                              type="number"
                              class="form-control"
                              id="montant"
                              name="montant"
                              min="1"
                              step="1"
                              placeholder="Ex : 5000"
                              value="<?= esc(old('montant')) ?>"
                              required
                          >
                      </div>
                      <button type="submit" class="btn btn-warning w-100">Retirer</button>
                  </form>
              </div>
          </div>

      </div>
  </div>
  <?= $this->endSection() ?>
  ```

  `app/Views/client/transfert.php` (avec suggestion de numeros, voir ci-dessous)
  ```php
  <?= $this->extend('layouts/main') ?>

  <?= $this->section('content') ?>
  <div class="row justify-content-center">
      <div class="col-md-6">

          <?php $error = session()->getFlashdata('error'); ?>
          <?php if ($error): ?>
              <div class="alert alert-danger"><?= esc($error) ?></div>
          <?php endif; ?>

          <div class="card shadow-sm">
              <div class="card-body p-4">
                  <h1 class="h4 mb-3">Transfert</h1>
                  <form method="post" action="<?= site_url('client/transfert') ?>">
                      <?= csrf_field() ?>
                      <div class="mb-3">
                          <label for="destinataire" class="form-label">Numero du destinataire</label>
                          <input
                              type="text"
                              class="form-control"
                              id="destinataire"
                              name="destinataire"
                              list="destinataires-suggestions"
                              autocomplete="off"
                              placeholder="Ex : 0331234567"
                              value="<?= esc(old('destinataire')) ?>"
                              required
                          >
                          <datalist id="destinataires-suggestions">
                              <?php foreach ($suggestions as $numero) : ?>
                                  <option value="<?= esc($numero) ?>"></option>
                              <?php endforeach; ?>
                          </datalist>
                      </div>
                      <div class="mb-3">
                          <label for="montant" class="form-label">Montant (Ar)</label>
                          <input
                              type="number"
                              class="form-control"
                              id="montant"
                              name="montant"
                              min="1"
                              step="1"
                              placeholder="Ex : 5000"
                              value="<?= esc(old('montant')) ?>"
                              required
                          >
                      </div>
                      <button type="submit" class="btn btn-primary w-100">Transférer</button>
                  </form>
              </div>
          </div>

      </div>
  </div>
  <?= $this->endSection() ?>
  ```

  `app/Views/client/historique.php`
  ```php
  <?= $this->extend('layouts/main') ?>

  <?= $this->section('content') ?>
  <div class="row justify-content-center">
      <div class="col-md-10">
          <h1 class="h4 mb-3">Historique des operations</h1>

          <?php if (empty($transactions)): ?>
              <div class="alert alert-info">Aucune transaction.</div>
          <?php else: ?>
              <div class="table-responsive">
                  <table class="table table-striped table-hover">
                      <thead class="table-dark">
                          <tr>
                              <th>Date</th>
                              <th>Type</th>
                              <th class="text-end">Montant</th>
                              <th class="text-end">Frais</th>
                              <th class="text-end">Solde avant</th>
                              <th class="text-end">Solde apres</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php foreach ($transactions as $t): ?>
                              <tr>
                                  <td><?= esc($t->created_at) ?></td>
                                  <td><?= esc($t->type_libelle) ?></td>
                                  <td class="text-end"><?= number_format($t->montant, 2, ',', ' ') ?></td>
                                  <td class="text-end"><?= number_format($t->frais, 2, ',', ' ') ?></td>
                                  <td class="text-end"><?= number_format($t->solde_avant, 2, ',', ' ') ?></td>
                                  <td class="text-end"><?= number_format($t->solde_apres, 2, ',', ' ') ?></td>
                              </tr>
                          <?php endforeach; ?>
                      </tbody>
                  </table>
              </div>
          <?php endif; ?>
      </div>
  </div>
  <?= $this->endSection() ?>
  ```

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

---

# TODO Version 2

Énoncé :

> Coté opérateur
> - Configuration des préfixes valable pour les autres opérateurs (ex: 032 et 031, …)
> - Configuration % en plus de commissions pour les transferts vers les autres opérateurs
> - Sur la page "Situation gain via les différents frais", séparer opérateur et autres opérateurs
> - Situation des montants à envoyer à chaque opérateur
>
> Coté client
> - Option inclure frais de retrait lors de l'envoi
> - Envoi multiple vers plusieurs numéros (divisé le montant pour chaque numéro)

## Analyse & schéma DB (commun, à faire avant les 2 lots — comme pour la V1)

La V1 distingue déjà nos préfixes (table `prefixes`, utilisée pour valider le login) des
numéros non reconnus. La V2 ajoute une deuxième categorie : les préfixes des **autres**
opérateurs, valables uniquement comme **destination** d'un transfert (jamais pour se
logger — ce ne sont pas nos clients, on ne connait pas leur solde).

**Nouvelle table `prefixes_externes`** (préfixe + commission, combinés dans un seul
ecran comme demandé par les 2 premiers points de l'enonce) :

```sql
CREATE TABLE prefixes_externes (
    id                     INTEGER PRIMARY KEY AUTOINCREMENT,
    prefixe                VARCHAR(5) NOT NULL UNIQUE,
    pourcentage_commission DECIMAL(5,2) NOT NULL DEFAULT 0
);
```

**Modification de `transactions`** — un envoi vers un autre opérateur n'a pas de
`client_destination_id` (le destinataire n'est pas un client chez nous), il faut donc
stocker son numéro brut. On separe aussi `commission` de `frais` pour pouvoir les
distinguer facilement sur la page "gains" :

```sql
ALTER TABLE transactions ADD COLUMN numero_externe VARCHAR(20);
ALTER TABLE transactions ADD COLUMN commission DECIMAL(15,2) NOT NULL DEFAULT 0;
```

- `numero_externe IS NULL` → operation interne (comme en V1)
- `numero_externe IS NOT NULL` → transfert vers un autre operateur (`client_destination_id`
  reste NULL dans ce cas)
- `frais` = barème habituel (table `frais`, comme en V1) ; `commission` = en plus,
  calculée uniquement pour les transferts externes (`montant * pourcentage_commission / 100`)

## Questions ouvertes à trancher en équipe avant de coder

Ces points ne sont pas précisés dans l'énoncé — à décider ensemble, les choix ci-dessous
sont juste des recommandations par défaut :

1. **Un numéro externe doit-il déjà exister quelque part chez nous ?**
   Non — on ne gère pas les clients des autres opérateurs, donc on ne peut vérifier que le
   **préfixe** (contre `prefixes_externes`), pas l'existence réelle du numéro. Contrairement
   au transfert interne (V1) qui exige un client déjà enregistré.
2. **La commission remplace-t-elle le frais de transfert standard ou s'ajoute-t-elle ?**
   Recommandation : elle s'ajoute (frais standard + commission externe), les deux sont un
   gain pour l'opérateur mais affichés séparément sur la page gains.
3. **"Montants à envoyer à chaque opérateur" : montant brut ou net de commission ?**
   Recommandation : montant brut envoyé (`SUM(transactions.montant)`), car c'est ce que le
   destinataire doit recevoir chez l'autre opérateur — la commission reste chez nous.
4. **"Frais de retrait inclus" (option côté client) : qui paie quoi ?**
   Recommandation : si coché, on ajoute au montant envoyé le frais de retrait estimé
   (`calculerFrais('retrait', montant)`) pour que le destinataire, après avoir lui-même
   retiré, se retrouve avec le montant net initialement voulu par l'émetteur.
5. **Envoi multiple : le frais est-il calculé sur le montant total ou sur la part de
   chaque destinataire ?**
   Recommandation : sur la part de chacun (chaque envoi est une transaction distincte dans
   `transactions`, donc son propre frais selon le barème), comme des transferts normaux
   effectués en série.

## Lot 1 — Côté Opérateur (V2)

**Fichiers à créer :**

```
app/Models/PrefixeExterneModel.php
app/Controllers/OperateurController.php   (methodes a ajouter)
app/Views/operateur/prefixes_externes.php
app/Views/operateur/montants_a_envoyer.php
app/Views/operateur/gains.php             (a modifier : separer interne/externe)
app/Config/Routes.php                     (routes a ajouter, cf Coordination V1)
```

**Checklist :**

- [ ] Migration `base.sql` : table `prefixes_externes` + colonnes `transactions.numero_externe`
      et `transactions.commission` (voir schema ci-dessus)
- [ ] `PrefixeExterneModel` — CRUD sur `prefixes_externes` (meme pattern que `PrefixeModel`
      du Lot 1 V1), avec validation du pourcentage (0-100)
- [ ] `OperateurController::prefixesExternes()` / `storePrefixeExterne()` /
      `deletePrefixeExterne()` / `updateCommission($id)` — meme pattern que
      `prefixes()`/`storePrefixe()`/`deletePrefixe()` de la V1, plus le champ commission
- [ ] Vue `operateur/prefixes_externes.php` — liste + formulaire d'ajout (prefixe + %),
      edition du % par ligne
- [ ] Modifier `OperateurController::gains()` : deux blocs distincts —
      "Nos clients" (`numero_externe IS NULL`, somme de `frais`) et
      "Autres operateurs" (`numero_externe IS NOT NULL`, somme de `frais` + `commission`
      separement)
- [ ] Nouvel ecran `OperateurController::montantsAEnvoyer()` — somme de `transactions.montant`
      groupee par prefixe externe (jointure sur les 3 premiers caracteres de
      `numero_externe` = `prefixes_externes.prefixe`), pour savoir combien reverser a
      chaque operateur
- [ ] Routes a ajouter dans `app/Config/Routes.php`, groupe `operateur` :
      `operateur/prefixes-externes` (GET/POST), `operateur/prefixes-externes/(:num)/delete`
      (POST), `operateur/commissions/(:num)` (POST), `operateur/montants-a-envoyer` (GET)

## Lot 2 — Côté Client (V2)

**Fichiers à créer / modifier :**

```
app/Controllers/ClientController.php   (storeTransfert a modifier + nouvelles methodes)
app/Views/client/transfert.php         (checkbox frais de retrait inclus)
app/Views/client/transfert_multiple.php
app/Config/Routes.php                  (routes a ajouter)
```

**Checklist :**

- [ ] Modifier `ClientController::storeTransfert()` : si le numero du destinataire ne
      correspond a aucun client existant, verifier son prefixe contre `prefixes_externes` ;
      si valide, calculer la commission (`montant * pourcentage / 100`) en plus du frais
      standard, debiter emetteur (montant + frais + commission), enregistrer la transaction
      avec `numero_externe` renseigne et `client_destination_id` = null (pas de credit
      cote destinataire, il n'est pas chez nous)
- [ ] Ajouter une checkbox "Inclure les frais de retrait" sur `client/transfert.php` ;
      si cochee, `storeTransfert()` ajoute au montant envoye le frais de retrait estime
      (voir question ouverte n°4)
- [ ] Nouveau formulaire `client/transfert_multiple.php` — liste dynamique de numeros
      (bouton "ajouter un destinataire", un peu de JS vanilla pour dupliquer une ligne) +
      un montant total ; affichage cote client du montant par destinataire avant validation
- [ ] `ClientController::transfertMultiple()` (GET) / `storeTransfertMultiple()` (POST) —
      diviser le montant total par le nombre de destinataires (le dernier recoit le
      reliquat d'arrondi), creer une transaction distincte par destinataire (reutilise
      `TransactionModel::transfert()` en boucle, ou nouvelle methode `transfertMultiple()`
      dans `TransactionModel` qui englobe toute la boucle dans une seule transaction SQL)
- [ ] Routes a ajouter dans `app/Config/Routes.php`, groupe `client` :
      `client/transfert-multiple` (GET), `client/transfert-multiple` (POST)

## Definition of Done — V2

- [ ] Schema DB V2 applique (`prefixes_externes`, colonnes `transactions`)
- [ ] Transfert vers un numero externe fonctionnel (commission + montant a envoyer)
- [ ] Page gains separee interne/externe
- [ ] Page "montants a envoyer par operateur"
- [ ] Option frais de retrait inclus fonctionnelle
- [ ] Envoi multiple fonctionnel (division + arrondi correct)
- [ ] Tag Git `v2` pose sur `main`
