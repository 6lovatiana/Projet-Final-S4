<?php

namespace App\Models;

use CodeIgniter\Model;

class FraisModel extends Model
{
    protected $table         = 'frais';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['type_operation_id', 'min', 'max', 'valeur'];

    /**
     * Retourne les lignes de frais pour un type d'operation donné.
     */
    public function pourType(int $typeOperationId): array
    {
        return $this->where('type_operation_id', $typeOperationId)
            ->orderBy('min', 'ASC')
            ->findAll();
    }
}
