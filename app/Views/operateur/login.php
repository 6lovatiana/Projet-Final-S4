<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex flex-column justify-content-center align-items-center" style="min-height: 60vh;">
    <div class="card shadow-sm" style="max-width: 400px; width: 100%;">
        <div class="card-body p-4">
            <h1 class="h4 mb-3 text-center">Connexion Operateur</h1>

            <?php $error = session()->getFlashdata('error'); ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= esc($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= site_url('operateur/login') ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        required
                        autofocus
                    >
                </div>
                <button type="submit" class="btn btn-dark w-100">Se connecter</button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
