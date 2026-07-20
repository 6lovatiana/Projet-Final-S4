<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<h1 class="h4 mb-4">Situation gain via les differents frais</h1>

<h2 class="h6">Gains de l'operateur (frais)</h2>
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

<h2 class="h6 mt-5">Montants dus aux autres operateurs</h2>
<table class="table table-bordered bg-white">
    <thead>
        <tr>
            <th>Prefixe</th>
            <th class="text-end">Nb transferts</th>
            <th class="text-end">Montant brut (destinataires)</th>
            <th class="text-end">Commission due</th>
            <th class="text-end">Total a reverser</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($situationOperateurs as $op) : ?>
            <tr>
                <td><?= esc($op['prefixe']) ?></td>
                <td class="text-end"><?= (int) $op['nb_operations'] ?></td>
                <td class="text-end"><?= number_format((float) $op['total_montant'], 2, ',', ' ') ?> Ar</td>
                <td class="text-end"><?= number_format((float) $op['total_commission'], 2, ',', ' ') ?> Ar</td>
                <td class="text-end fw-bold"><?= number_format((float) $op['total_montant'] + (float) $op['total_commission'], 2, ',', ' ') ?> Ar</td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($situationOperateurs)) : ?>
            <tr>
                <td colspan="5" class="text-muted">Aucun transfert vers un autre operateur pour le moment.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<?= $this->endSection() ?>
