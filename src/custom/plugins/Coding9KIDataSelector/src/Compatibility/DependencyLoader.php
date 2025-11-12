<?php

namespace Coding9\KIDataSelector\Compatibility;

use Composer\Autoload\ClassLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class DependencyLoader
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var VersionCompare
     */
    private $versionCompare;

    /**
     * @param Container $container
     */
    public function __construct(ContainerInterface $container, VersionCompare $versionCompare)
    {
        $this->container = $container;
        $this->versionCompare = $versionCompare;
    }

    /**
     * Load version-specific services
     * @throws \Exception
     */
    public function loadServices(): void
    {
        /** @var ContainerBuilder $containerBuilder */
        $containerBuilder = $this->container;

        $loader = new XmlFileLoader($containerBuilder, new FileLocator(__DIR__ . '/../Resources/config'));

        // Load base services (all versions)
        $loader->load('compatibility/all_versions.xml');

        // Load version-specific services
        if ($this->versionCompare->gte('6.5.0.0')) {
            $loader->load('compatibility/6.5.0.0.xml');
        }

        if ($this->versionCompare->gte('6.6.0.0')) {
            $loader->load('compatibility/6.6.0.0.xml');
        }

        if ($this->versionCompare->gte('6.7.0.0')) {
            $loader->load('compatibility/6.7.0.0.xml');
        }
    }

    /**
     * Register dependencies like polyfills
     */
    public function registerDependencies(): void
    {
        $classLoader = new ClassLoader();

        $this->registerPolyfillsAutoloader($classLoader);

        $classLoader->register();
    }

    /**
     * Register polyfills for backward compatibility
     */
    private function registerPolyfillsAutoloader(ClassLoader $classLoader): void
    {
        $polyfillPath = __DIR__ . '/../../polyfill/Shopware/Core';

        if (is_dir($polyfillPath)) {
            $classLoader->addPsr4("Shopware\\Core\\", $polyfillPath, true);
        }
    }

    /**
     * Prepare administration build based on Shopware version
     */
    public function prepareAdministrationBuild(): void
    {
        $pluginRoot = __DIR__ . '/../..';
        $distFileFolder = $pluginRoot . '/src/Resources/public/administration/js';

        if (!file_exists($distFileFolder)) {
            mkdir($distFileFolder, 0777, true);
        }

        // Determine which JS file to use based on version
        $sourceFile = $this->getVersionSpecificAdministrationFile($pluginRoot);
        $targetFile = $distFileFolder . '/coding9-k-i-data-selector.js';

        // Copy version-specific file if it exists and target doesn't
        if ($sourceFile && file_exists($sourceFile) && !file_exists($targetFile)) {
            copy($sourceFile, $targetFile);
        }
    }

    /**
     * Get the version-specific administration JS file
     */
    private function getVersionSpecificAdministrationFile(string $pluginRoot): ?string
    {
        $distPath = $pluginRoot . '/src/Resources/app/administration/dist';

        // Check for version-specific builds
        if ($this->versionCompare->gte('6.7.0.0') && file_exists($distPath . '/coding9-k-i-data-selector-6.7.js')) {
            return $distPath . '/coding9-k-i-data-selector-6.7.js';
        }

        if ($this->versionCompare->gte('6.6.0.0') && file_exists($distPath . '/coding9-k-i-data-selector-6.6.js')) {
            return $distPath . '/coding9-k-i-data-selector-6.6.js';
        }

        if ($this->versionCompare->gte('6.5.0.0') && file_exists($distPath . '/coding9-k-i-data-selector-6.5.js')) {
            return $distPath . '/coding9-k-i-data-selector-6.5.js';
        }

        // Fallback to 6.4 version
        if (file_exists($distPath . '/coding9-k-i-data-selector-6.4.js')) {
            return $distPath . '/coding9-k-i-data-selector-6.4.js';
        }

        // Fallback to default build
        if (file_exists($distPath . '/coding9-k-i-data-selector.js')) {
            return $distPath . '/coding9-k-i-data-selector.js';
        }

        return null;
    }
}
