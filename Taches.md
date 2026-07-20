# Taches - Suivi du projet Mobile Money

Ce fichier sert de suivi d'avancement du projet (a completer au fur et a mesure).
Le detail technique (arborescence, TODO complete, extraits de code) est dans
[GUIDE_TECHNIQUE.md](GUIDE_TECHNIQUE.md).

Repartition du travail (voir [todo.md](todo.md)) : Lot 1 "Cote Operateur" = misaina,
Lot 2 "Cote Client" = binome.

## Journal

| Date       | Etudiant | Tache realisee                                                       |
|------------|----------|------------------------------------------------------------------------|
| 2026-07-20 | misaina  | Verification environnement (PHP 8.2.31, extension SQLite3)             |
| 2026-07-20 | misaina  | Installation des dependances Composer (CodeIgniter 4.7)                |
| 2026-07-20 | misaina  | Configuration `.env` (SQLite3, `writable/database.db`)                 |
| 2026-07-20 | misaina  | Nettoyage des fichiers d'exemple CI4 (welcome_message)                 |
| 2026-07-20 | misaina  | Creation de `base.sql` (clients, prefixes, types_operation, frais, transactions) |
| 2026-07-20 | misaina  | Generation de la base `writable/database.db` depuis `base.sql`         |
| 2026-07-20 | misaina  | Redaction de `GUIDE_TECHNIQUE.md`                                       |
| 2026-07-20 | misaina  | Socle commun : `Routes.php` (toutes routes des 2 lots), layout Bootstrap `layouts/main.php`, `todo.md` de repartition |
| 2026-07-20 | misaina  | Lot 1 complet (Cote Operateur) : `PrefixModel`, `TypeOperationModel`, `FraisModel`, `OperateurController`, vues prefixes/types-operation/comptes/gains |

## Etat d'avancement

- [x] Environnement pret (PHP, SQLite3, CodeIgniter installe)
- [x] Base de donnees conçue et generee
- [x] Socle commun (routes, layout Bootstrap, helpers)
- [ ] Login automatique par numero de telephone (Lot 2)
- [ ] Depot (Lot 2)
- [ ] Retrait (Lot 2)
- [ ] Transfert (Lot 2)
- [ ] Historique des operations (Lot 2)
- [x] Ecran operateur : gestion des prefixes (Lot 1)
- [x] Ecran operateur : gestion des types d'operation / bareme de frais (Lot 1)
- [x] Ecran operateur : situation des gains (frais percus) (Lot 1)
- [x] Ecran operateur : situation des comptes clients (Lot 1)
- [ ] Tag `v1`
