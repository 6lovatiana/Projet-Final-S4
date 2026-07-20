<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Mobile Money') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?= site_url('/') ?>">Mobile Money</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto">
                <?php if (session()->get('client_id')): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Client</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= site_url('client') ?>">Solde</a></li>
                        <li><a class="dropdown-item" href="<?= site_url('client/depot') ?>">Depot</a></li>
                        <li><a class="dropdown-item" href="<?= site_url('client/retrait') ?>">Retrait</a></li>
                        <li><a class="dropdown-item" href="<?= site_url('client/transfert') ?>">Transfert</a></li>
                        <li><a class="dropdown-item" href="<?= site_url('client/historique') ?>">Historique</a></li>
                    </ul>
                </li>
                <?php endif; ?>
                <?php if (session()->get('is_operateur')): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Operateur</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= site_url('operateur/prefixes') ?>">Prefixes</a></li>
                        <li><a class="dropdown-item" href="<?= site_url('operateur/types-operation') ?>">Types &amp; Frais</a></li>
                        <li><a class="dropdown-item" href="<?= site_url('operateur/comptes') ?>">Comptes clients</a></li>
                        <li><a class="dropdown-item" href="<?= site_url('operateur/gains') ?>">Gains</a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
            <div class="d-flex gap-2">
                <?php if (session()->get('client_id')): ?>
                    <a class="btn btn-outline-danger btn-sm" href="<?= site_url('logout') ?>">Logout (client)</a>
                <?php elseif (session()->get('is_operateur')): ?>
                    <a class="btn btn-outline-danger btn-sm" href="<?= site_url('logout') ?>">Logout (operateur)</a>
                <?php else: ?>
                    <a class="btn btn-outline-light btn-sm" href="<?= site_url('login') ?>">Login client</a>
                    <a class="btn btn-outline-light btn-sm" href="<?= site_url('operateur/login') ?>">Login operateur</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<main class="container py-4">
    <?= $this->renderSection('content') ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
