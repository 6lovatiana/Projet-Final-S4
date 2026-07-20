<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class OperateurAuthFilter implements FilterInterface
{
    /**
     * Verifie que l'operateur est connecte avant d'acceder aux routes protegees.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('is_operateur')) {
            return redirect()->to(site_url('operateur/login'));
        }
    }

    /**
     * Aucune action apres la requete.
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
