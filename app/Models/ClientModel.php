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
        'pourcentage_epargne',
        'solde_epargne',
    ];

    protected $validationRules = [
        'numero'             => 'required|is_unique[clients.numero,id]',
        'solde'              => 'required|decimal',
        'pourcentage_epargne' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
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
        'pourcentage_epargne' => [
            'decimal'               => 'Le pourcentage doit etre un nombre decimal.',
            'greater_than_equal_to' => 'Le pourcentage doit etre entre 0 et 100.',
            'less_than_equal_to'    => 'Le pourcentage doit etre entre 0 et 100.',
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
