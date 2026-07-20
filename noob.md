Critere d'ecriture :
1-KISS (Keep It Simple, Stupid) : Éviter l'over-engineering.
2-Lisibilité : Code auto-explicatif.
3-Modulaire : Une seule responsabilité par fichier.
4-Explicite : Pas de syntaxe cachée ou magique.

Tache :
1-Model du client :
[X] `ClientModel` — CRUD sur `clients` (`id`, `numero`, `solde`)

2- Auth :
- [X] `AuthController::login()` — GET, formulaire de saisie du numéro
- [X] `AuthController::attempt()` — POST `login`, vérifie le préfixe (table `prefixes`,
      en lecture seule), crée le client s'il n'existe pas encore (login auto, aucune
      inscription), stocke `client_id` en session, redirige vers `client`
- [X] `AuthController::logout()` — GET, détruit la session, redirige vers `/`
- [X] `ClientAuthFilter` — protège les routes `/client/*` (redirige vers login si pas
      de session). Une fois créé, l'activer dans `Routes.php` en changeant :
      `$routes->group('client', static function ($routes) {...})` en
      `$routes->group('client', ['filter' => 'clientAuth'], static function ($routes) {...})`
      et enregistrer l'alias `'clientAuth' => \App\Filters\ClientAuthFilter::class`
      dans `app/Config/Filters.php` (`$aliases`)