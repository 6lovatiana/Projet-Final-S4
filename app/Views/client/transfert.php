<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-md-6">

        <?php $error = session()->getFlashdata('error'); ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= esc($error) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Transfert</h1>
                <form method="post" action="<?= site_url('client/transfert') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="destinataire" class="form-label">Numero du destinataire</label>
                        <input
                            type="text"
                            class="form-control"
                            id="destinataire"
                            name="destinataire"
                            list="destinataires-suggestions"
                            autocomplete="off"
                            placeholder="Ex : 0335555555"
                            value="<?= esc(old('destinataire')) ?>"
                            required
                        >
                        <datalist id="destinataires-suggestions">
                            <?php foreach ($suggestions as $numero) : ?>
                                <option value="<?= esc($numero) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="mb-3">
                        <label for="montant" class="form-label">Montant (Ar)</label>
                        <input
                            type="number"
                            class="form-control"
                            id="montant"
                            name="montant"
                            min="1"
                            step="1"
                            placeholder="Ex : 5000"
                            value="<?= esc(old('montant')) ?>"
                            required
                        >
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="inclure_frais_retrait" name="inclure_frais_retrait" value="1">
                        <label class="form-check-label" for="inclure_frais_retrait">
                            Inclure les frais de retrait (le destinataire recevra le montant + le frais qu'il paierait au retrait)
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Transférer</button>
                </form>
                <a href="<?= site_url('client/transfert-multiple') ?>" class="d-block text-center mt-2 small">Envoyer a plusieurs destinataires a la fois</a>
            </div>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
