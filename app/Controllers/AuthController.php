<?php

namespace App\Controllers;

use App\Models\ClientModel;

class AuthController extends BaseController
{
    /**
     * GET /login — Affiche le formulaire de saisie du numero.
     */
    public function login(): string
    {
        return view('auth/login');
    }

    /**
     * POST /login — Verifie le prefixe, cree le client si necessaire,
     *               stocke l'id en session, redirige vers client/.
     */
    public function attempt()
    {
        $numero = trim($this->request->getPost('numero') ?? '');

        if ($numero === '') {
            return redirect()->back()->withInput()->with('error', 'Veuillez saisir un numero de telephone.');
        }

        $prefixe = substr($numero, 0, 3);

        $prefixeValide = $this->db->table('prefixes')
            ->where('prefixe', $prefixe)
            ->countAllResults() > 0;

        if (! $prefixeValide) {
            return redirect()->back()->withInput()->with('error', 'Le prefixe "' . esc($prefixe) . '" n\'est pas valable.');
        }

        $clientModel = new ClientModel();
        $client = $clientModel->findByNumero($numero);

        if ($client === null) {
            $clientModel->insert([
                'numero' => $numero,
                'solde'  => 0,
            ]);
            $client = $clientModel->findByNumero($numero);
        }

        session()->set('client_id', $client->id);

        return redirect()->to(site_url('client'));
    }

    /**
     * GET /logout — Detruit la session et redirige vers l'accueil.
     */
    public function logout()
    {
        session()->destroy();

        return redirect()->to(site_url('/'));
    }
}
