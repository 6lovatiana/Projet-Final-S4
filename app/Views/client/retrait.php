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
                <h1 class="h4 mb-3">Retrait</h1>
                <form method="post" action="<?= site_url('client/retrait') ?>">
                    <?= csrf_field() ?>
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
                    <button type="submit" class="btn btn-warning w-100">Retirer</button>
                </form>
            </div>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
