<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<h1 class="h4 mb-4">Prefixes valables</h1>

<?= $this->include('operateur/_flash') ?>

<div class="row">
    <div class="col-md-7">
        <table class="table table-bordered bg-white align-middle">
            <thead>
                <tr>
                    <th>Prefixe</th>
                    <th>Status</th>
                    <th>Commission</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prefixes as $prefixe) : ?>
                    <tr>
                        <td><?= esc($prefixe['prefixe']) ?></td>
                        <td>
                            <?php if ($prefixe['status'] === 'autre') : ?>
                                <span class="badge bg-warning text-dark">Autre operateur</span>
                            <?php else : ?>
                                <span class="badge bg-primary">Principal</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($prefixe['status'] === 'autre') : ?>
                                <form method="post" action="<?= site_url('operateur/prefixes/' . $prefixe['id'] . '/commission') ?>" class="d-flex align-items-center gap-1">
                                    <?= csrf_field() ?>
                                    <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm" style="width: 80px;" name="pourcentage_commission" value="<?= esc($prefixe['pourcentage_commission']) ?>">
                                    <span>%</span>
                                    <button type="submit" class="btn btn-sm btn-outline-primary">OK</button>
                                </form>
                            <?php else : ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
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
                        <td colspan="4" class="text-muted">Aucun prefixe configure.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="col-md-5">
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
                    <div class="mb-3">
                        <label class="form-label" for="status">Type</label>
                        <select class="form-select" id="status" name="status">
                            <option value="principal">Principal (notre operateur)</option>
                            <option value="autre">Autre operateur</option>
                        </select>
                        <div class="form-text">Par defaut, un prefixe ajoute est "Principal".</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="pourcentage_commission">% Commission (si "Autre operateur")</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control" id="pourcentage_commission" name="pourcentage_commission" value="<?= esc(old('pourcentage_commission') ?: 0) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
