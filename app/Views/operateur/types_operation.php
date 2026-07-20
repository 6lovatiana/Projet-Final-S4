<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<h1 class="h4 mb-4">Types d'operation & bareme de frais</h1>

<?= $this->include('operateur/_flash') ?>

<?php foreach ($types as $type) : ?>
    <?= $this->setVar('type', $type)->include('operateur/frais') ?>
<?php endforeach; ?>
<?= $this->endSection() ?>
