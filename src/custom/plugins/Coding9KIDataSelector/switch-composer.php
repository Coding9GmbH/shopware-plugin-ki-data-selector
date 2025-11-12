<?php

/**
 * Composer Version Switcher
 *
 * This script allows switching between production and development Shopware version constraints
 * in composer.json. This is useful for development and testing across different Shopware versions.
 *
 * Usage:
 *   php switch-composer.php prod    - Set production version constraints (6.4.5.0 - 6.7.0.0)
 *   php switch-composer.php dev     - Set development version constraints (*)
 */

$env = (count($argv) >= 2) ? (string)$argv[1] : '';

$composerContent = file_get_contents(__DIR__ . '/composer.json');
$composerContent = json_decode($composerContent, true);

// Production: Specific version range for release
const SW_VERSIONS_RELEASE = '6.4.5.0 - 6.7.0.0';

// Development: Any version for local development
const SW_VERSIONS_DEV = '*';

if ($env === 'prod') {
    $composerContent = moveToProd($composerContent, SW_VERSIONS_RELEASE);
    echo "✓ Switched to PRODUCTION mode: Shopware {$composerContent['require']['shopware/core']}\n";
} elseif ($env === 'dev') {
    $composerContent = moveToDev($composerContent, SW_VERSIONS_DEV);
    echo "✓ Switched to DEVELOPMENT mode: Shopware {$composerContent['require']['shopware/core']}\n";
} else {
    echo "Usage: php switch-composer.php [prod|dev]\n";
    echo "  prod - Set production version constraints (6.4.5.0 - 6.7.0.0)\n";
    echo "  dev  - Set development version constraints (*)\n";
    exit(1);
}

file_put_contents(__DIR__ . '/composer.json', json_encode($composerContent, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n");

/**
 * Set development version constraints
 */
function moveToDev(array $composerContent, string $swVersion): array
{
    $composerContent['require']["shopware/core"] = $swVersion;
    $composerContent['require']["shopware/administration"] = $swVersion;

    return $composerContent;
}

/**
 * Set production version constraints
 */
function moveToProd(array $composerContent, string $swVersion): array
{
    $composerContent['require']["shopware/core"] = $swVersion;
    $composerContent['require']["shopware/administration"] = $swVersion;

    return $composerContent;
}
