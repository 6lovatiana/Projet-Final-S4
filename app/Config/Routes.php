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
// --------------------------------------------------------------------
$routes->group('operateur', static function ($routes): void {
    $routes->get('prefixes', 'OperateurController::prefixes');
    $routes->post('prefixes', 'OperateurController::storePrefixe');
    $routes->post('prefixes/(:num)/delete', 'OperateurController::deletePrefixe/$1');

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
$routes->group('client', static function ($routes): void {
    $routes->get('/', 'ClientController::dashboard');

    $routes->get('depot', 'ClientController::depot');
    $routes->post('depot', 'ClientController::storeDepot');

    $routes->get('retrait', 'ClientController::retrait');
    $routes->post('retrait', 'ClientController::storeRetrait');

    $routes->get('transfert', 'ClientController::transfert');
    $routes->post('transfert', 'ClientController::storeTransfert');

    $routes->get('historique', 'ClientController::historique');
});
