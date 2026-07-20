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

    /**
     * Lit le bareme dans la table `frais` pour un type d'operation et un montant donnes.
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
     * Depot : crédite le solde, frais = 0, journalise l'operation.
     */
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

    /**
     * Retrait : vérifie le solde, débite, journalise l'operation.
     */
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

    /**
     * Transfert : vérifie le solde de l'émetteur, débite, crédite le destinataire,
     * journalise l'operation.
     */
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

    /**
     * Retourne l'historique des transactions d'un client, de la plus récente à la plus ancienne.
     */
    public function getHistorique(int $clientId): array
    {
        return $this->select('transactions.*, types_operation.libelle AS type_libelle')
            ->join('types_operation', 'types_operation.id = transactions.type_operation_id')
            ->where('client_id', $clientId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Résout un code de type d'opération (depot, retrait, transfert) en son id.
     */
    private function getCodeId(string $code): int
    {
        $row = $this->db->table('types_operation')
            ->where('code', $code)
            ->get()
            ->getRow();

        return (int) $row->id;
    }
}
