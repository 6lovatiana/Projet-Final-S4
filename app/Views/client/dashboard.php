<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-md-6">

        <?php $success = session()->getFlashdata('success'); ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= esc($success) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body text-center p-4">
                <h1 class="h4 mb-3">Mon solde</h1>
                <p class="text-muted">Numero : <?= esc($client->numero) ?></p>
                <p class="display-5 fw-bold text-success">
                    <?= number_format($client->solde, 2, ',', ' ') ?> Ar
                </p>
                <p class="text-muted mb-0">
                    Epargne : <?= number_format($client->solde_epargne, 2, ',', ' ') ?> Ar
                    (<?= esc($client->pourcentage_epargne) ?> %)
                </p>
            </div>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
