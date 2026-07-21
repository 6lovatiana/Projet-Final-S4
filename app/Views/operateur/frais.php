<div class="card mb-4">
    <div class="card-header">
        <strong><?= esc($type['libelle'] ?? '') ?></strong>
        <span class="text-muted">(<?= esc($type['code'] ?? '') ?>)</span>
    </div>
    <div class="card-body">
        <?php if (empty($type['frais'] ?? [])) : ?>
            <p class="text-muted mb-0">Aucun frais applicable (operation gratuite).</p>
        <?php else : ?>
            <div class="row fw-bold small text-muted mb-2">
                <div class="col-3">Montant min</div>
                <div class="col-3">Montant max</div>
                <div class="col-3">Frais</div>
                <div class="col-3"></div>
            </div>
            <?php foreach (($type['frais'] ?? []) as $ligne) : ?>
                <form method="post" action="<?= site_url('operateur/frais/' . $ligne['id']) ?>" class="row g-2 align-items-center mb-2">
                    <?= csrf_field() ?>
                    <div class="col-3">
                        <input type="number" step="0.01" class="form-control form-control-sm" name="min" value="<?= esc($ligne['min']) ?>">
                    </div>
                    <div class="col-3">
                        <input type="number" step="0.01" class="form-control form-control-sm" name="max" value="<?= esc($ligne['max']) ?>">
                    </div>
                    <div class="col-3">
                        <input type="number" step="0.01" class="form-control form-control-sm" name="valeur" value="<?= esc($ligne['valeur']) ?>">
                    </div>
                    <div class="col-3 text-end">
                        <button type="submit" class="btn btn-sm btn-outline-primary">Enregistrer</button>
                    </div>
                </form>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
