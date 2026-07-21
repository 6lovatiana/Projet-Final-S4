<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h1 class="h4 mb-4">Promotions sur frais de transfert interne</h1>
<?= $this->include('operateur/_flash') ?>

<div class="row">
    <div class="col-md-6">
        <table class="table table-bordered bg-white">
            <thead>
                <tr>
                    <th>Pourcentage</th>
                    <th>Statut</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($promotions as $promo) : ?>
                    <tr>
                        <td><?= esc($promo['pourcentage']) ?> %</td>
                        <td>
                            <?php if ((int) $promo['actif'] === 1) : ?>
                                <span class="badge bg-success">Active</span>
                            <?php else : ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ((int) $promo['actif'] === 1) : ?>
                                <form method="post" action="<?= site_url('operateur/promotions/' . $promo['id'] . '/deactivate') ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Desactiver</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($promotions)) : ?>
                    <tr>
                        <td colspan="3" class="text-muted">Aucune promotion configuree.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h2 class="h6">Activer une nouvelle promotion</h2>
                <p class="text-muted small">
                    S'applique uniquement sur le frais de transfert vers un client
                    de notre operateur (jamais sur les depots, retraits ou
                    transferts externes).
                </p>
                <form method="post" action="<?= site_url('operateur/promotions') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="pourcentage">Pourcentage de reduction (ex: 10)</label>
                        <input type="number" step="0.01" min="0.01" max="100" class="form-control"
                               id="pourcentage" name="pourcentage"
                               value="<?= esc(old('pourcentage')) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Activer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>