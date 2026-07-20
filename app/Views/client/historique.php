<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-md-10">
        <h1 class="h4 mb-3">Historique des operations</h1>

        <?php if (empty($transactions)): ?>
            <div class="alert alert-info">Aucune transaction.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th class="text-end">Montant</th>
                            <th class="text-end">Frais</th>
                            <th class="text-end">Solde avant</th>
                            <th class="text-end">Solde apres</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?= esc($t->created_at) ?></td>
                                <td><?= esc($t->type_libelle) ?></td>
                                <td class="text-end"><?= number_format($t->montant, 2, ',', ' ') ?></td>
                                <td class="text-end"><?= number_format($t->frais, 2, ',', ' ') ?></td>
                                <td class="text-end"><?= number_format($t->solde_avant, 2, ',', ' ') ?></td>
                                <td class="text-end"><?= number_format($t->solde_apres, 2, ',', ' ') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
