<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-md-7">

        <?php $error = session()->getFlashdata('error'); ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= esc($error) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Envoi multiple</h1>
                <p class="text-muted small">Le montant total est divise a parts egales entre tous les destinataires
                    (le dernier recoit le reliquat d'arrondi).</p>

                <form method="post" action="<?= site_url('client/transfert-multiple') ?>">
                    <?= csrf_field() ?>

                    <div id="destinataires">
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" name="numeros[]" placeholder="Numero destinataire" required>
                            <button type="button" class="btn btn-outline-danger" onclick="retirerDestinataire(this)">&times;</button>
                        </div>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" name="numeros[]" placeholder="Numero destinataire" required>
                            <button type="button" class="btn btn-outline-danger" onclick="retirerDestinataire(this)">&times;</button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm mb-3" onclick="ajouterDestinataire()">+ Ajouter un destinataire</button>

                    <div class="mb-3">
                        <label for="montant" class="form-label">Montant total (Ar)</label>
                        <input type="number" class="form-control" id="montant" name="montant" min="1" step="1" placeholder="Ex : 30000" required>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="inclure_frais_retrait" name="inclure_frais_retrait" value="1">
                        <label class="form-check-label" for="inclure_frais_retrait">
                            Inclure les frais de retrait pour chaque destinataire
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Envoyer</button>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
function ajouterDestinataire() {
    const container = document.getElementById('destinataires');
    const row = document.createElement('div');
    row.className = 'input-group mb-2';
    row.innerHTML = '<input type="text" class="form-control" name="numeros[]" placeholder="Numero destinataire" required>'
        + '<button type="button" class="btn btn-outline-danger" onclick="retirerDestinataire(this)">&times;</button>';
    container.appendChild(row);
}

function retirerDestinataire(bouton) {
    const container = document.getElementById('destinataires');
    if (container.children.length > 2) {
        bouton.closest('.input-group').remove();
    }
}
</script>
<?= $this->endSection() ?>
