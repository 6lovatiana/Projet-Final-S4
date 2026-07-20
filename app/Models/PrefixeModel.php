<?php

namespace App\Models;

use CodeIgniter\Model;

class PrefixeModel extends Model
{
    protected $table         = 'prefixes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['prefixe', 'status', 'pourcentage_commission'];

    protected $validationRules = [
        'prefixe' => 'required|is_unique[prefixes.prefixe,,id]',
        'status'  => 'permit_empty|in_list[principal,autre]',
    ];

    protected $validationMessages = [
        'prefixe' => [
            'required'  => 'Le prefixe est obligatoire.',
            'is_unique' => 'Ce prefixe existe deja.',
        ],
    ];

    /**
     * Prefixes des autres operateurs uniquement (pour identifier un numero de
     * destination externe lors d'un transfert).
     */
    public function findAutrePrefixe(string $numero): ?array
    {
        return $this->where('prefixe', substr($numero, 0, 3))
            ->where('status', 'autre')
            ->first();
    }
}
