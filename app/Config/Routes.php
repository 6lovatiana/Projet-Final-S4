<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// --------------------------------------------------------------------
// Commun
// --------------------------------------------------------------------
$routes->get('/', 'Home::index');

$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::attempt');
$routes->get('logout', 'AuthController::logout');
// --------------------------------------------------------------------
// Lot 1 - Cote Operateur (app/Controllers/OperateurController.php)
// operateur/login reste hors filtre (sinon impossible de se connecter),
// tout le reste du groupe est protege par operateurAuth.
// --------------------------------------------------------------------
$routes->get('operateur/login', 'OperateurController::login');
$routes->post('operateur/login', 'OperateurController::attempt');

$routes->group('operateur', ['filter' => 'operateurAuth'], static function ($routes): void {
    $routes->get('promotions', 'OperateurController::promotions');
    $routes->post('promotions', 'OperateurController::storePromotion');
    $routes->post('promotions/(:num)/deactivate', 'OperateurController::deactivatePromotion/$1');

    $routes->get('prefixes', 'OperateurController::prefixes');
    $routes->post('prefixes', 'OperateurController::storePrefixe');
    $routes->post('prefixes/(:num)/delete', 'OperateurController::deletePrefixe/$1');
    $routes->post('prefixes/(:num)/commission', 'OperateurController::updateCommission/$1');

    $routes->get('types-operation', 'OperateurController::typesOperation');
    $routes->post('frais/(:num)', 'OperateurController::updateFrais/$1');

    $routes->get('comptes', 'OperateurController::comptes');
    $routes->get('gains', 'OperateurController::gains');
});

// --------------------------------------------------------------------
// Lot 2 - Cote Client (app/Controllers/ClientController.php)
// Routes a proteger par le filtre 'clientAuth' une fois ClientAuthFilter
// cree et enregistre dans app/Config/Filters.php, ex:
// $routes->group('client', ['filter' => 'clientAuth'], static function ($routes) { ... });
// --------------------------------------------------------------------
$routes->group('client', ['filter' => 'clientAuth'], static function ($routes): void {
    $routes->get('/', 'ClientController::dashboard');

    $routes->get('epargne', 'ClientController::epargne');
    $routes->post('epargne', 'ClientController::storeEpargne');

    $routes->get('depot', 'ClientController::depot');
    $routes->post('depot', 'ClientController::storeDepot');

    $routes->get('retrait', 'ClientController::retrait');
    $routes->post('retrait', 'ClientController::storeRetrait');

    $routes->get('transfert', 'ClientController::transfert');
    $routes->post('transfert', 'ClientController::storeTransfert');

    $routes->get('transfert-multiple', 'ClientController::transfertMultiple');
    $routes->post('transfert-multiple', 'ClientController::storeTransfertMultiple');

    $routes->get('historique', 'ClientController::historique');
});
