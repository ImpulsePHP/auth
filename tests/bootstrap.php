<?php

declare(strict_types=1);

$packageRoot = dirname(__DIR__);
$monorepoRoot = dirname($packageRoot);

$localAutoload = $packageRoot . '/vendor/autoload.php';
if (is_file($localAutoload)) {
    require_once $localAutoload;
}

$coreAutoload = $monorepoRoot . '/core/vendor/autoload.php';
if (is_file($coreAutoload)) {
    require_once $coreAutoload;
}

spl_autoload_register(static function (string $class) use ($packageRoot): void {
    $prefixes = [
        'Impulse\\Auth\\Tests\\' => $packageRoot . '/tests/',
        'Impulse\\Auth\\' => $packageRoot . '/src/',
        'Cycle\\ORM\\' => $packageRoot . '/tests/Stubs/Cycle/ORM/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = substr($class, strlen($prefix));
        $path = $baseDir . str_replace('\\', '/', $relative) . '.php';

        if (is_file($path)) {
            require_once $path;
        }
    }
});
