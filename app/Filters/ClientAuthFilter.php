<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ClientAuthFilter implements FilterInterface
{
    /**
     * Verifie que le client est connecte avant d'acceder aux routes protegees.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('client_id')) {
            return redirect()->to(site_url('login'));
        }
    }

    /**
     * Aucune action apres la requete.
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
