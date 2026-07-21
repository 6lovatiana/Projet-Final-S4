<?php

namespace App\Models;

use CodeIgniter\Model;

class PromotionModel extends Model
{
    protected $table         = 'promotions';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['pourcentage', 'actif'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Pourcentage de la promotion actuellement active, 0 si aucune.
     */
    public function pourcentageActif(): float
    {
        $promo = $this->where('actif', 1)->first();

        return $promo ? (float) $promo['pourcentage'] : 0.0;
    }
}