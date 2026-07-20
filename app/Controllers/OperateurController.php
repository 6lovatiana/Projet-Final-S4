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
