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
