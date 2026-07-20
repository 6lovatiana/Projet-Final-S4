<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex flex-column justify-content-center align-items-center" style="min-height: 60vh;">
    <div class="card shadow-sm" style="max-width: 480px; width: 100%;">
        <div class="card-body p-4 text-center">
            <h1 class="h4 mb-3">Mobile Money</h1>
            <p class="text-muted mb-4">Simulation d'opérateur Mobile Money. Qui êtes-vous ?</p>
            <div class="d-flex flex-column gap-2">
                <a href="<?= site_url('login') ?>" class="btn btn-primary">Je suis client</a>
                <a href="<?= site_url('operateur/login') ?>" class="btn btn-dark">Je suis l'opérateur</a>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
