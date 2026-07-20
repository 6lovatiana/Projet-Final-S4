<?php

namespace App\Controllers;

use App\Models\ClientModel;
use App\Models\TransactionModel;

class ClientController extends BaseController
{
    /**
     * ID du client connecte depuis la session.
     */
    private function clientId(): int
    {
        return (int) session()->get('client_id');
    }

    /**
     * GET /client — Affiche le solde du client connecte.
     */
    public function dashboard()
    {
        $clientModel = new ClientModel();
        $client = $clientModel->find($this->clientId());

        return view('client/dashboard', ['client' => $client]);
    }

    /**
     * GET /client/depot — Formulaire de depot.
     */
    public function depot()
    {
        return view('client/depot');
    }

    /**
     * POST /client/depot — Execute le depot.
     */
    public function storeDepot()
    {
        $montant = (float) $this->request->getPost('montant');

        if ($montant <= 0) {
            return redirect()->back()->with('error', 'Le montant doit etre superieur a 0.');
        }

        $transactionModel = new TransactionModel();
        $transactionModel->depot($this->clientId(), $montant);

        return redirect()->to(site_url('client'))->with('success', 'Depot de ' . number_format($montant, 0, ',', ' ') . ' effectue.');
    }

    /**
     * GET /client/retrait — Formulaire de retrait.
     */
    public function retrait()
    {
        return view('client/retrait');
    }

    /**
     * POST /client/retrait — Execute le retrait.
     */
    public function storeRetrait()
    {
        $montant = (float) $this->request->getPost('montant');

        if ($montant <= 0) {
            return redirect()->back()->with('error', 'Le montant doit etre superieur a 0.');
        }

        $transactionModel = new TransactionModel();

        try {
            $resultat = $transactionModel->retrait($this->clientId(), $montant);
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to(site_url('client'))->with(
            'success',
            'Retrait de ' . number_format($montant, 0, ',', ' ') . ' effectue (frais : ' . number_format($resultat['frais'], 0, ',', ' ') . ').'
        );
    }

    /**
     * GET /client/transfert — Formulaire de transfert.
     * Suggestions de numeros : destinataires deja utilises par ce client en priorite,
     * puis les autres clients de la base.
     */
    public function transfert()
    {
        $clientModel      = new ClientModel();
        $transactionModel = new TransactionModel();

        $recents = $transactionModel->getDestinatairesRecents($this->clientId());
        $autres  = array_map(
            static fn ($client) => $client->numero,
            $clientModel->findAllExcept($this->clientId())
        );

        $suggestions = array_values(array_unique(array_merge($recents, $autres)));

        return view('client/transfert', ['suggestions' => $suggestions]);
    }

    /**
     * POST /client/transfert — Execute le transfert.
     */
    public function storeTransfert()
    {
        $destinataire = trim($this->request->getPost('destinataire') ?? '');
        $montant      = (float) $this->request->getPost('montant');

        if ($destinataire === '') {
            return redirect()->back()->with('error', 'Veuillez saisir le numero du destinataire.');
        }

        if ($montant <= 0) {
            return redirect()->back()->with('error', 'Le montant doit etre superieur a 0.');
        }

        $clientModel = new ClientModel();
        $dest = $clientModel->findByNumero($destinataire);

        if ($dest === null) {
            return redirect()->back()->with('error', 'Le numero "' . esc($destinataire) . '" est introuvable.');
        }

        if ((int) $dest->id === $this->clientId()) {
            return redirect()->back()->with('error', 'Vous ne pouvez pas vous transférer a vous-meme.');
        }

        $transactionModel = new TransactionModel();

        try {
            $resultat = $transactionModel->transfert($this->clientId(), (int) $dest->id, $montant);
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to(site_url('client'))->with(
            'success',
            'Transfert de ' . number_format($montant, 0, ',', ' ') . ' effectue (frais : ' . number_format($resultat['frais'], 0, ',', ' ') . ').'
        );
    }

    /**
     * GET /client/historique — Liste des operations du client.
     */
    public function historique()
    {
        $transactionModel = new TransactionModel();
        $transactions = $transactionModel->getHistorique($this->clientId());

        return view('client/historique', ['transactions' => $transactions]);
    }
}
