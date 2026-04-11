# Installation

## Prérequis

- PHP 8.2 ou supérieur
- `impulsephp/core`

## Installation avec Composer

```bash
composer require impulsephp/auth
```

Le package déclare son provider via `extra.impulse-provider`. Si l’auto-découverte n’est pas utilisée, ajoutez manuellement :

```php
Impulse\Auth\AuthProvider::class
```

dans la clé `providers` de `impulse.php`.

## Première configuration

Le minimum requis est :

```php
<?php

return [
    'auth' => [
        'entity' => App\Entity\User::class,
    ],
];
```

Si votre application utilise `impulsephp/db`, rien d’autre n’est nécessaire pour la résolution de l’utilisateur.

Sinon, configurez `auth.provider` avec une classe implémentant `Impulse\Auth\Contracts\UserProviderInterface`.
