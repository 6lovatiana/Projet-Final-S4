<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-md-6">

        <?php $success = session()->getFlashdata('success'); ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= esc($success) ?></div>
        <?php endif; ?>

        <?php $errors = session()->getFlashdata('errors'); ?>
        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error) : ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-3">
            <div class="card-body text-center p-4">
                <h1 class="h4 mb-3">Mon epargne</h1>
                <p class="display-6 fw-bold text-primary">
                    <?= number_format($client->solde_epargne, 2, ',', ' ') ?> Ar
                </p>
                <p class="text-muted mb-0">
                    Pourcentage actuel : <?= esc($client->pourcentage_epargne) ?> %
                </p>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h6 mb-3">Modifier mon pourcentage d'epargne</h2>
                <p class="text-muted small">
                    A chaque depot, ce pourcentage du montant depose sera mis de cote automatiquement dans votre epargne.
                </p>
                <form method="post" action="<?= site_url('client/epargne') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="pourcentage_epargne" class="form-label">Pourcentage (0 a 100)</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control"
                               id="pourcentage_epargne" name="pourcentage_epargne"
                               value="<?= esc(old('pourcentage_epargne') ?? $client->pourcentage_epargne) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Enregistrer</button>
                </form>
            </div>
        </div>

    </div>
</div>
<?= $this->endSection() ?>