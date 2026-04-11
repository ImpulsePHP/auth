# Utilisation

## Récupérer le service

```php
use Impulse\Auth\Contracts\AuthInterface;
use Impulse\Core\App;

$auth = App::get(AuthInterface::class);
```

## Tenter une connexion

```php
if (!$auth->attempt($email, $password)) {
    // message générique
}
```

## Connecter explicitement une entité

```php
$auth->login($user);
```

L’entité n’a pas besoin d’implémenter une interface spécifique. Le provider lit les valeurs à partir des champs configurés dans `impulse.php`.

## Tester l’état courant

```php
$auth->check();
$auth->guest();
$auth->user();
$auth->id();
```

## Déconnexion

```php
$auth->logout();
```

## Protéger une page

```php
use Impulse\Auth\Middleware\RequireAuthMiddleware;
use Impulse\Core\Attributes\PageProperty;
use Impulse\Core\Component\AbstractPage;

#[PageProperty(
    route: '/dashboard',
    middlewares: [RequireAuthMiddleware::class]
)]
final class DashboardPage extends AbstractPage
{
    public function template(): string
    {
        return '...';
    }
}
```

## Configuration détaillée

```php
'auth' => [
    'entity' => App\Entity\User::class,
    'identifier_field' => 'email',
    'password_field' => 'password',
    'id_field' => 'id',
    'session_key' => 'auth.user_id',
    'login_path' => '/',
    'provider' => App\Auth\UserProvider::class,
],
'session' => [
    'cookie' => 'impulse_session',
    'lifetime' => 1200,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'http_only' => true,
    'same_site' => 'Lax',
],
```

Seule `auth.entity` est obligatoire.
