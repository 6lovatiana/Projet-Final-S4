# Plan : Auth (AuthController + ClientAuthFilter + Config)

## Contexte

Le projet est une app Mobile Money CI4. Les routes login/logout sont déjà définies dans `Routes.php` (lignes 12-14). Le layout `main.php` a déjà un bouton "Login" dans la navbar. La vue `auth/login.php` n'existe pas encore. Le `ClientAuthFilter.php` est vide. `BaseController` charge les helpers `form` et `url`.

---

## Fichiers à modifier/créer

| Fichier | Action |
|---------|--------|
| `app/Controllers/AuthController.php` | **Écrire** (vide actuellement) |
| `app/Filters/ClientAuthFilter.php` | **Écrire** (vide actuellement) |
| `app/Views/auth/login.php` | **Créer** |
| `app/Config/Filters.php` | **Éditer** — ajouter alias `clientAuth` |
| `app/Config/Routes.php` | **Éditer** — activer le filtre sur le groupe `client` |

---

## 1. `AuthController` (`app/Controllers/AuthController.php`)

3 méthodes, une responsabilité chacune.

### `login()` — GET
- Retourne la vue `auth/login` (formulaire avec champ `numero`).

### `attempt()` — POST
1. Récupérer `numero` depuis `$this->request->getPost()`.
2. Valider que `numero` n'est pas vide.
3. Extraire le préfixe : `substr($numero, 0, 3)`.
4. Vérifier le préfixe dans la table `prefixes` via Query Builder (`$this->db->table('prefixes')->where('prefixe', $prefixe)->countAllResults() > 0`). Pas de besoin de créer un `PrefixeModel` pour une simple lecture — KISS.
5. Si préfixe invalide → revenir au formulaire avec message d'erreur.
6. Chercher le client via `ClientModel::findByNumero()`.
7. Si introuvable → créer le client avec `solde = 0` via `ClientModel::insert()`.
8. Stocker `client_id` en session : `session()->set('client_id', $client->id)`.
9. Rediriger vers `site_url('client')`.

### `logout()` — GET
1. `session()->destroy()`.
2. Rediriger vers `site_url('/')`.

---

## 2. `ClientAuthFilter` (`app/Filters/ClientAuthFilter.php`)

Implémente `CodeIgniter\Filters\FilterInterface`.

### `before()`
- Vérifier `session()->get('client_id')`.
- Si absent → `return redirect()->to(site_url('login'));`.

### `after()`
- Ne rien faire (retourne `null` ou rien).

---

## 3. Vue `auth/login.php` (`app/Views/auth/login.php`)

Formulaire Bootstrap 5 simple :
- Extend `layouts/main`.
- Section `content` : carte centrée avec champ `numero` (texte, requis) + bouton "Se connecter".
- Afficher les messages d'erreur (`session()->getFlashdata('error')`) s'ils existent.

---

## 4. `app/Config/Filters.php`

Ajouter dans `$aliases` :
```php
'clientAuth' => \App\Filters\ClientAuthFilter::class,
```

---

## 5. `app/Config/Routes.php`

Changer la ligne 37 de :
```php
$routes->group('client', static function ($routes): void {
```
en :
```php
$routes->group('client', ['filter' => 'clientAuth'], static function ($routes): void {
```

---

## Vérification

1. `php -l app/Controllers/AuthController.php`
2. `php -l app/Filters/ClientAuthFilter.php`
3. `php -l app/Config/Filters.php`
4. `php -l app/Config/Routes.php`
