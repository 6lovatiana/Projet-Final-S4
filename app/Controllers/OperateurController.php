<?php

namespace App\Controllers;

use App\Models\PromotionModel;
use App\Models\FraisModel;
use App\Models\PrefixeModel;
use App\Models\TypeOperationModel;
use Config\Database;

class OperateurController extends BaseController
{
    protected PrefixeModel $prefixeModel;
    protected TypeOperationModel $typeOperationModel;
    protected FraisModel $fraisModel;
    protected PromotionModel $promotionModel;

    public function __construct()
    {
        $this->prefixeModel       = new PrefixeModel();
        $this->typeOperationModel = new TypeOperationModel();
        $this->fraisModel         = new FraisModel();
        $this->promotionModel     = new PromotionModel();
    }

    // ------------------------------------------------------------------
    // Authentification operateur
    // ------------------------------------------------------------------


    public function promotions()
    {
        return view('operateur/promotions', [
            'promotions' => $this->promotionModel->orderBy('created_at', 'DESC')->findAll(),
        ]);
    }

    public function storePromotion()
    {
        $rules = [
            'pourcentage' => 'required|numeric|greater_than[0]|less_than_equal_to[100]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('operateur/promotions')->withInput()->with('errors', $this->validator->getErrors());
        }

        // Une seule promotion active a la fois : on desactive les precedentes.
        $this->promotionModel->where('actif', 1)->set(['actif' => 0])->update();

        $this->promotionModel->insert([
            'pourcentage' => $this->request->getPost('pourcentage'),
            'actif'       => 1,
        ]);

        return redirect()->to('operateur/promotions')->with('success', 'Promotion activee.');
    }

    public function deactivatePromotion(int $id)
    {
        $this->promotionModel->update($id, ['actif' => 0]);

        return redirect()->to('operateur/promotions')->with('success', 'Promotion desactivee.');
    }
    public function login()
    {
        return view('operateur/login');
    }

    public function attempt()
    {
        $motDePasse = env('operateur.password', 'operateur123');

        if ($this->request->getPost('password') !== $motDePasse) {
            return redirect()->back()->with('error', 'Mot de passe incorrect.');
        }

        session()->set('is_operateur', true);

        return redirect()->to(site_url('operateur/prefixes'));
    }

    // ------------------------------------------------------------------
    // Prefixes
    // ------------------------------------------------------------------

    public function prefixes()
    {
        return view('operateur/prefixes', [
            'prefixes' => $this->prefixeModel->orderBy('status', 'ASC')->orderBy('prefixe', 'ASC')->findAll(),
        ]);
    }

    public function storePrefixe()
    {
        $rules = [
            'prefixe'                => 'required|regex_match[/^[0-9]{2,5}$/]|is_unique[prefixes.prefixe]',
            'status'                 => 'permit_empty|in_list[principal,autre]',
            'pourcentage_commission' => 'permit_empty|numeric|greater_than_equal_to[0]|less_than_equal_to[100]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('operateur/prefixes')->withInput()->with('errors', $this->validator->getErrors());
        }

        $status     = $this->request->getPost('status') === 'autre' ? 'autre' : 'principal';
        $commission = $status === 'autre' ? (float) $this->request->getPost('pourcentage_commission') : 0;

        $this->prefixeModel->insert([
            'prefixe'                => $this->request->getPost('prefixe'),
            'status'                 => $status,
            'pourcentage_commission' => $commission,
        ]);

        return redirect()->to('operateur/prefixes')->with('success', 'Prefixe ajoute.');
    }

    public function updateCommission(int $id)
    {
        $rules = [
            'pourcentage_commission' => 'required|numeric|greater_than_equal_to[0]|less_than_equal_to[100]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('operateur/prefixes')->with('errors', $this->validator->getErrors());
        }

        $this->prefixeModel->update($id, [
            'pourcentage_commission' => $this->request->getPost('pourcentage_commission'),
        ]);

        return redirect()->to('operateur/prefixes')->with('success', 'Commission mise a jour.');
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

        // Gains de notre operateur (frais standard, interne ET externe confondus :
        // le frais de transfert nous revient toujours, seule la commission part ailleurs)
        $gains = $db->table('transactions')
            ->select('types_operation.libelle AS libelle, SUM(transactions.frais) AS total_frais, COUNT(transactions.id) AS nb_operations')
            ->join('types_operation', 'types_operation.id = transactions.type_operation_id')
            ->groupBy('types_operation.id')
            ->get()
            ->getResultArray();

        $totalGeneral = array_sum(array_column($gains, 'total_frais'));

        // Montants dus aux autres operateurs : montant brut (a transmettre au
        // destinataire) + commission (leur part), groupes par prefixe externe.
        $situationOperateurs = $db->table('transactions')
            ->select('prefixes.prefixe AS prefixe, SUM(transactions.montant) AS total_montant, SUM(transactions.commission) AS total_commission, COUNT(transactions.id) AS nb_operations')
            ->join('prefixes', 'prefixes.prefixe = SUBSTR(transactions.numero_externe, 1, 3)', 'inner', false)
            ->where('transactions.numero_externe IS NOT NULL')
            ->groupBy('prefixes.prefixe')
            ->get()
            ->getResultArray();

        return view('operateur/gains', [
            'gains'               => $gains,
            'totalGeneral'        => $totalGeneral,
            'situationOperateurs' => $situationOperateurs,
        ]);
    }
}
