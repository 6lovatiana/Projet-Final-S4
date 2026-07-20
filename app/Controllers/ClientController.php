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
     * POST /client/transfert — Execute le transfert (vers un client existant
     * ou vers un numero dont le prefixe est reconnu comme "autre operateur").
     */
    public function storeTransfert()
    {
        $destinataire         = trim($this->request->getPost('destinataire') ?? '');
        $montant              = (float) $this->request->getPost('montant');
        $inclureFraisRetrait  = (bool) $this->request->getPost('inclure_frais_retrait');

        if ($destinataire === '') {
            return redirect()->back()->with('error', 'Veuillez saisir le numero du destinataire.');
        }

        if ($montant <= 0) {
            return redirect()->back()->with('error', 'Le montant doit etre superieur a 0.');
        }

        $clientModel = new ClientModel();
        $moi         = $clientModel->find($this->clientId());

        if ($destinataire === $moi->numero) {
            return redirect()->back()->with('error', 'Vous ne pouvez pas vous transférer a vous-meme.');
        }

        $transactionModel = new TransactionModel();

        try {
            $resultat = $transactionModel->transfert($this->clientId(), $destinataire, $montant, $inclureFraisRetrait);
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $message = 'Transfert de ' . number_format($montant, 0, ',', ' ') . ' effectue (frais : ' . number_format($resultat['frais'], 0, ',', ' ') . ')';

        if ($resultat['commission'] > 0) {
            $message .= ', commission autre operateur : ' . number_format($resultat['commission'], 0, ',', ' ');
        }

        return redirect()->to(site_url('client'))->with('success', $message . '.');
    }

    /**
     * GET /client/transfert-multiple — Formulaire d'envoi multiple.
     */
    public function transfertMultiple()
    {
        return view('client/transfert_multiple');
    }

    /**
     * POST /client/transfert-multiple — Divise le montant total entre tous
     * les destinataires et execute un transfert distinct pour chacun.
     */
    public function storeTransfertMultiple()
    {
        $numeros             = array_values(array_filter(array_map('trim', $this->request->getPost('numeros') ?? [])));
        $montant             = (float) $this->request->getPost('montant');
        $inclureFraisRetrait = (bool) $this->request->getPost('inclure_frais_retrait');

        if (count($numeros) < 2) {
            return redirect()->back()->with('error', 'Veuillez saisir au moins 2 destinataires.');
        }

        if ($montant <= 0) {
            return redirect()->back()->with('error', 'Le montant total doit etre superieur a 0.');
        }

        $clientModel = new ClientModel();
        $moi         = $clientModel->find($this->clientId());

        if (in_array($moi->numero, $numeros, true)) {
            return redirect()->back()->with('error', 'Vous ne pouvez pas vous transférer a vous-meme.');
        }

        $transactionModel = new TransactionModel();

        try {
            $transactionModel->transfertMultiple($this->clientId(), $numeros, $montant, $inclureFraisRetrait);
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to(site_url('client'))->with(
            'success',
            'Envoi multiple de ' . number_format($montant, 0, ',', ' ') . ' reparti entre ' . count($numeros) . ' destinataires effectue.'
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
