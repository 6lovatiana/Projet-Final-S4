<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<h1 class="h4 mb-4">Situation des gains (frais percus)</h1>

<table class="table table-bordered bg-white">
    <thead>
        <tr>
            <th>Type d'operation</th>
            <th class="text-end">Nb operations</th>
            <th class="text-end">Total frais percus</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($gains as $ligne) : ?>
            <tr>
                <td><?= esc($ligne['libelle']) ?></td>
                <td class="text-end"><?= (int) $ligne['nb_operations'] ?></td>
                <td class="text-end"><?= number_format((float) $ligne['total_frais'], 2, ',', ' ') ?> Ar</td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($gains)) : ?>
            <tr>
                <td colspan="3" class="text-muted">Aucune operation payante enregistree.</td>
            </tr>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr class="fw-bold">
            <td colspan="2">Total general</td>
            <td class="text-end"><?= number_format((float) $totalGeneral, 2, ',', ' ') ?> Ar</td>
        </tr>
    </tfoot>
</table>
<?= $this->endSection() ?>
