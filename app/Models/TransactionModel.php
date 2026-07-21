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
        'numero_externe',
        'montant',
        'frais',
        'commission',
        'frais_retrait_inclus',
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

        $client = $clientModel->find($clientId);

        if ($client === null) {
            throw new \RuntimeException('Client introuvable.');
        }

        $soldeActuel       = (float) ($client->solde ?? 0);
        $soldeEpargneActuel = (float) ($client->solde_epargne ?? 0);
        $pourcentageEpargne = (float) ($client->pourcentage_epargne ?? 0);
        $soldeApresCredit  = $soldeActuel + $montant;
        $montantEpargne    = round($montant * ($pourcentageEpargne / 100), 2);
        $soldeFinal        = $soldeApresCredit - $montantEpargne;
        $soldeEpargneApres = $soldeEpargneActuel + $montantEpargne;

        $clientModel->update($clientId, [
            'solde'         => $soldeFinal,
            'solde_epargne' => $soldeEpargneApres,
        ]);

        $this->insert([
            'type_operation_id' => $this->getCodeId('depot'),
            'client_id'         => $clientId,
            'montant'           => $montant,
            'frais'             => 0.0,
            'solde_avant'       => $soldeActuel,
            'solde_apres'       => $soldeFinal,
        ]);

        if ($montantEpargne > 0) {
            $this->insert([
                'type_operation_id' => $this->getCodeId('epargne'),
                'client_id'         => $clientId,
                'montant'           => $montantEpargne,
                'frais'             => 0.0,
                'solde_avant'       => $soldeApresCredit,
                'solde_apres'       => $soldeFinal,
            ]);
        }

        $this->db->transComplete();

        return [
            'frais'           => 0.0,
            'solde'           => $soldeFinal,
            'montant_epargne' => $montantEpargne,
            'solde_epargne'   => $soldeEpargneApres,
        ];
    }

    /**
     * Retrait : vérifie le solde, débite, journalise l'operation.
     */
    public function retrait(int $clientId, float $montant): array
    {
        $clientModel = model(ClientModel::class);

        $this->db->transStart();

        $client = $clientModel->find($clientId);

        if ($client === null) {
            throw new \RuntimeException('Client introuvable.');
        }

        $soldeActuel = (float) ($client->solde ?? 0);
        $typeId = $this->getCodeId('retrait');
        $frais  = $this->calculerFrais($typeId, $montant);
        $total  = $montant + $frais;

        if ($soldeActuel < $total) {
            $this->db->transRollback();
            throw new \RuntimeException('Solde insuffisant.');
        }

        $soldeApres = $soldeActuel - $total;

        $clientModel->update($clientId, ['solde' => $soldeApres]);

        $this->insert([
            'type_operation_id' => $typeId,
            'client_id'         => $clientId,
            'montant'           => $montant,
            'frais'             => $frais,
            'solde_avant'       => $soldeActuel,
            'solde_apres'       => $soldeApres,
        ]);

        $this->db->transComplete();

        return ['frais' => $frais, 'solde' => $soldeApres];
    }

    /**
     * Transfert : vers un client existant (interne) ou vers un numero dont le
     * prefixe est reconnu comme appartenant a un "autre" operateur (externe -
     * pas de compte chez nous, juste une commission due a cet operateur).
     *
     * Si $inclureFraisRetrait est vrai, le destinataire recoit
     * montant + frais_de_retrait_estime (pour qu'apres son propre retrait, il
     * lui reste exactement le montant initialement voulu par l'emetteur).
     *
     * Debit emetteur = montant_credite (destinataire) + frais (notre gain)
     *                  + commission (due a l'autre operateur, externe uniquement)
     */
    public function transfert(int $clientId, string $numeroDestinataire, float $montantSaisi, bool $inclureFraisRetrait = false): array
    {
        $clientModel  = model(ClientModel::class);
        $prefixeModel = model(PrefixeModel::class);

        $destinataire          = $clientModel->findByNumero($numeroDestinataire);
        $estExterne            = $destinataire === null;
        $pourcentageCommission = 0.0;

        if ($estExterne) {
            $prefixeExterne = $prefixeModel->findAutrePrefixe($numeroDestinataire);

            if ($prefixeExterne === null) {
                throw new \RuntimeException('Numero ou prefixe non reconnu.');
            }

            $pourcentageCommission = (float) $prefixeExterne['pourcentage_commission'];
        }

        $this->db->transStart();

        $emetteur = $clientModel->find($clientId);
        $typeId   = $this->getCodeId('transfert');

        $fraisRetraitInclus = $inclureFraisRetrait
            ? $this->calculerFrais($this->getCodeId('retrait'), $montantSaisi)
            : 0.0;

        $montantCredite = $montantSaisi + $fraisRetraitInclus;
        $fraisTransfert = $this->calculerFrais($typeId, $montantSaisi);
        $commission     = $estExterne ? round($montantSaisi * $pourcentageCommission / 100, 2) : 0.0;

        $totalDebit = $montantCredite + $fraisTransfert + $commission;

        if ($emetteur->solde < $totalDebit) {
            $this->db->transRollback();
            throw new \RuntimeException('Solde insuffisant pour le transfert.');
        }

        $soldeEmetteurApres = $emetteur->solde - $totalDebit;
        $clientModel->update($clientId, ['solde' => $soldeEmetteurApres]);

        if (! $estExterne) {
            $clientModel->update($destinataire->id, ['solde' => $destinataire->solde + $montantCredite]);
        }

        $this->insert([
            'type_operation_id'     => $typeId,
            'client_id'             => $clientId,
            'client_destination_id' => $estExterne ? null : $destinataire->id,
            'numero_externe'        => $estExterne ? $numeroDestinataire : null,
            'montant'               => $montantCredite,
            'frais'                 => $fraisTransfert,
            'commission'            => $commission,
            'frais_retrait_inclus'  => $fraisRetraitInclus,
            'solde_avant'           => $emetteur->solde,
            'solde_apres'           => $soldeEmetteurApres,
        ]);

        $this->db->transComplete();

        return [
            'frais'       => $fraisTransfert,
            'commission'  => $commission,
            'solde'       => $soldeEmetteurApres,
            'est_externe' => $estExterne,
        ];
    }

    /**
     * Envoi multiple : divise $montantTotal a parts egales entre les numeros
     * donnes (le dernier recoit le reliquat d'arrondi), chaque envoi etant un
     * transfert independant (avec son propre frais selon le montant de sa part).
     * L'ensemble est englobe dans une seule transaction SQL : si un envoi
     * echoue (solde insuffisant en cours de route), tout est annule.
     */
    public function transfertMultiple(int $clientId, array $numeros, float $montantTotal, bool $inclureFraisRetrait = false): array
    {
        $nombreDestinataires = count($numeros);

        if ($nombreDestinataires === 0) {
            throw new \RuntimeException('Aucun destinataire.');
        }

        $part      = floor(($montantTotal / $nombreDestinataires) * 100) / 100;
        $resultats = [];

        $this->db->transStart();

        try {
            foreach ($numeros as $index => $numero) {
                $montantPart = ($index === $nombreDestinataires - 1)
                    ? round($montantTotal - ($part * ($nombreDestinataires - 1)), 2)
                    : $part;

                $resultats[] = $this->transfert($clientId, $numero, $montantPart, $inclureFraisRetrait);
            }
        } catch (\RuntimeException $e) {
            $this->db->transRollback();

            throw $e;
        }

        $this->db->transComplete();

        return $resultats;
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
