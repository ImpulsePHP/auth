# ImpulsePHP Auth

`impulsephp/auth` fournit la brique d’authentification d’ImpulsePHP avec une intégration simple dans le conteneur, une session PHP comme stockage d’état, et un middleware prêt à l’emploi pour protéger des pages.

## Ce que fait le package

- expose un service d’authentification avec `attempt()`, `login()`, `logout()`, `check()`, `guest()`, `user()` et `id()` ;
- stocke uniquement l’identifiant utilisateur en session ;
- recharge l’utilisateur courant à partir d’une entité configurée dans `impulse.php` ;
- fonctionne nativement avec `impulsephp/db` si `Cycle\ORM\ORMInterface` est disponible ;
- permet un chargement personnalisé des utilisateurs via un contrat optionnel ;
- fournit `Impulse\Auth\Middleware\RequireAuthMiddleware` pour protéger une page.

## Installation

```bash
composer require impulsephp/auth
```

Le package déclare son provider via `extra.impulse-provider`. Si votre application ne gère pas l’auto-découverte, ajoutez `Impulse\Auth\AuthProvider::class` à la liste `providers` de `impulse.php`.

## Configuration minimale

```php
<?php

use App\Entity\User;
use Impulse\Auth\AuthProvider;

return [
    'providers' => [
        AuthProvider::class,
    ],
    'auth' => [
        'entity' => User::class,
    ],
];
```

Options disponibles :

- `auth.entity` : classe de l’entité utilisateur. Obligatoire.
- `auth.identifier_field` : champ utilisé pour la connexion. Défaut `email`.
- `auth.password_field` : champ contenant le hash du mot de passe. Défaut `password`.
- `auth.id_field` : champ identifiant persistant. Défaut `id`.
- `auth.session_key` : clé de session utilisée par le provider. Défaut `auth.user_id`.
- `auth.login_path` : URL de redirection pour `RequireAuthMiddleware`. Défaut `/`.
- `auth.provider` : classe optionnelle implémentant `Impulse\Auth\Contracts\UserProviderInterface`.

Le provider réutilise aussi la configuration racine `session` si elle existe :

- `session.cookie`
- `session.lifetime`
- `session.path`
- `session.domain`
- `session.secure`
- `session.http_only`
- `session.same_site`

## API principale

```php
use Impulse\Auth\Contracts\AuthInterface;
use Impulse\Core\App;

$auth = App::get(AuthInterface::class);

$auth->attempt($email, $password);
$auth->login($user);
$auth->check();
$auth->guest();
$auth->user();
$auth->id();
$auth->logout();
```

## Protéger une page

Le mécanisme de protection réellement consommé aujourd’hui par le routeur est la liste `middlewares` de `PageProperty`.

```php
use Impulse\Auth\Middleware\RequireAuthMiddleware;
use Impulse\Core\Attributes\PageProperty;
use Impulse\Core\Component\AbstractPage;

#[PageProperty(
    route: '/account',
    middlewares: [RequireAuthMiddleware::class]
)]
final class AccountPage extends AbstractPage
{
    public function template(): string
    {
        return '...';
    }
}
```

Le middleware redirige les invités vers `auth.login_path`, ou renvoie un JSON `401` pour les requêtes AJAX / JSON.

## Contrat optionnel pour le chargement utilisateur

Si votre projet n’utilise pas `impulsephp/db`, ou si vous voulez une stratégie spécifique, vous pouvez fournir votre propre chargeur d’utilisateurs :

```php
use Impulse\Auth\Contracts\UserProviderInterface;

final class UserProvider implements UserProviderInterface
{
    public function findByIdentifier(string $identifier): ?object
    {
        // ...
    }

    public function findById(int|string $id): ?object
    {
        // ...
    }
}
```

Puis dans `impulse.php` :

```php
'auth' => [
    'entity' => App\Entity\User::class,
    'provider' => App\Auth\UserProvider::class,
],
```

Ce contrat reste optionnel. Avec `impulsephp/db`, le provider utilise directement l’ORM à partir de `auth.entity`.

## Sécurité prise en charge

- stockage du seul identifiant utilisateur en session ;
- régénération de l’identifiant de session après connexion réussie ;
- régénération de l’identifiant de session à la déconnexion ;
- vérification des mots de passe via `password_verify()` ;
- échec de connexion sobre sans exposer la cause exacte ;
- purge de la session si l’utilisateur stocké n’existe plus.

## Limite actuelle

Le cœur du framework démarre déjà la session dans certains flux via `LocalStorage::ingestRequestPayload()`. Le provider applique donc le durcissement des paramètres de cookie quand il démarre lui-même la session, mais ne peut pas réécrire ces paramètres si la session a déjà été ouverte plus tôt dans le bootstrap.

## Documentation

- `docs/installation.md`
- `docs/usage.md`

## Tests

```bash
php /Users/guillaume/Sites/ImpulsePHP/core/vendor/bin/phpunit -c /Users/guillaume/Sites/ImpulsePHP/auth/phpunit.xml
```
