<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex flex-column justify-content-center align-items-center" style="min-height: 60vh;">
    <div class="card shadow-sm" style="max-width: 400px; width: 100%;">
        <div class="card-body p-4 text-center">
            <h1 class="h4 mb-3">Mobile Money</h1>
            <p class="text-muted">Simulation d'opérateur Mobile Money.</p>
            <p class="text-muted small">Le login par numéro de téléphone sera disponible ici.</p>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
