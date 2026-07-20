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
