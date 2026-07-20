<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<h1 class="h4 mb-4">Situation des comptes clients</h1>

<table class="table table-bordered bg-white">
    <thead>
        <tr>
            <th>Numero</th>
            <th class="text-end">Solde</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($clients as $client) : ?>
            <tr>
                <td><?= esc($client['numero']) ?></td>
                <td class="text-end"><?= number_format((float) $client['solde'], 2, ',', ' ') ?> Ar</td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($clients)) : ?>
            <tr>
                <td colspan="2" class="text-muted">Aucun client pour le moment.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<?= $this->endSection() ?>
