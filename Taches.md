# Taches - Suivi du projet Mobile Money

Ce fichier sert de suivi d'avancement du projet (a completer au fur et a mesure).
Le detail technique (arborescence, TODO complete, extraits de code) est dans
[GUIDE_TECHNIQUE.md](GUIDE_TECHNIQUE.md).

## Journal

| Date       | Tache realisee                                                              |
|------------|-------------------------------------------------------------------------------|
| 2026-07-20 | Verification environnement (PHP 8.2.31, extension SQLite3)                    |
| 2026-07-20 | Installation des dependances Composer (CodeIgniter 4.7)                       |
| 2026-07-20 | Configuration `.env` (SQLite3, `writable/database.db`)                        |
| 2026-07-20 | Nettoyage des fichiers d'exemple CI4 (welcome_message)                        |
| 2026-07-20 | Creation de `base.sql` (clients, prefixes, types_operation, frais, transactions) |
| 2026-07-20 | Generation de la base `writable/database.db` depuis `base.sql`                |
| 2026-07-20 | Redaction de `GUIDE_TECHNIQUE.md`                                              |

## Etat d'avancement

- [x] Environnement pret (PHP, SQLite3, CodeIgniter installe)
- [x] Base de donnees conçue et generee
- [ ] Login automatique par numero de telephone
- [ ] Depot
- [ ] Retrait
- [ ] Transfert
- [ ] Historique des operations
- [ ] Ecran operateur : gestion des prefixes
- [ ] Ecran operateur : gestion des types d'operation / bareme de frais
- [ ] Ecran operateur : situation des gains (frais percus)
- [ ] Ecran operateur : situation des comptes clients
- [ ] Tag `v1`
