<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<h1 class="h4 mb-4">Prefixes valables</h1>

<?= $this->include('operateur/_flash') ?>

<div class="row">
    <div class="col-md-6">
        <table class="table table-bordered bg-white">
            <thead>
                <tr>
                    <th>Prefixe</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prefixes as $prefixe) : ?>
                    <tr>
                        <td><?= esc($prefixe['prefixe']) ?></td>
                        <td class="text-end">
                            <form method="post" action="<?= site_url('operateur/prefixes/' . $prefixe['id'] . '/delete') ?>"
                                  onsubmit="return confirm('Supprimer ce prefixe ?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($prefixes)) : ?>
                    <tr>
                        <td colspan="2" class="text-muted">Aucun prefixe configure.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h2 class="h6">Ajouter un prefixe</h2>
                <form method="post" action="<?= site_url('operateur/prefixes') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="prefixe">Prefixe (ex: 033)</label>
                        <input type="text" class="form-control" id="prefixe" name="prefixe"
                               value="<?= esc(old('prefixe')) ?>" maxlength="5" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
