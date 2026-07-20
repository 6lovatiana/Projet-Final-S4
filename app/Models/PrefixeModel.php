<?php

namespace App\Models;

use CodeIgniter\Model;

class PrefixeModel extends Model
{
    protected $table         = 'prefixes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['prefixe'];

    protected $validationRules = [
        'prefixe' => 'required|is_unique[prefixes.prefixe,,id]',
    ];

    protected $validationMessages = [
        'prefixe' => [
            'required'  => 'Le prefixe est obligatoire.',
            'is_unique' => 'Ce prefixe existe deja.',
        ],
    ];
}
