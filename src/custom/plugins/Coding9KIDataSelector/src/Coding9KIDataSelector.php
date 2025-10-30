<?php declare(strict_types=1);

namespace Coding9\KIDataSelector;

use Coding9\KIDataSelector\Compatibility\DependencyLoader;
use Coding9\KIDataSelector\Compatibility\VersionCompare;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Kernel;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Main plugin class for KI Data Selector
 *
 * Provides AI-powered SQL query generation from natural language
 * with read-only validation, pagination and CSV export.
 *
 * Compatible with Shopware 6.4, 6.5, 6.6, and 6.7
 *
 * @package Coding9\KIDataSelector
 */
class Coding9KIDataSelector extends Plugin
{
    const PLUGIN_VERSION = '1.0.0';

    /**
     * Build container and load version-specific dependencies
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $this->container = $container;
        $shopwareVersion = $this->container->getParameter('kernel.shopware_version');

        if (!is_string($shopwareVersion)) {
            $shopwareVersion = Kernel::SHOPWARE_FALLBACK_VERSION;
        }

        // Load version-specific services
        $loader = new DependencyLoader($this->container, new VersionCompare($shopwareVersion));
        $loader->loadServices();
        $loader->prepareAdministrationBuild();
    }

    /**
     * Boot plugin and register dependencies
     */
    public function boot(): void
    {
        parent::boot();

        if ($this->container === null) {
            return;
        }

        $shopwareVersion = $this->container->getParameter('kernel.shopware_version');

        if (!is_string($shopwareVersion)) {
            $shopwareVersion = Kernel::SHOPWARE_FALLBACK_VERSION;
        }

        // Register polyfills and other dependencies
        $loader = new DependencyLoader($this->container, new VersionCompare($shopwareVersion));
        $loader->registerDependencies();
    }

    /**
     * Plugin installation
     */
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
    }

    /**
     * Plugin activation
     */
    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);
    }

    /**
     * Plugin deactivation
     */
    public function deactivate(DeactivateContext $deactivateContext): void
    {
        parent::deactivate($deactivateContext);
    }

    /**
     * Plugin uninstallation
     */
    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        // Remove plugin tables if user data should not be kept
        $connection = $this->container->get('Doctrine\DBAL\Connection');
        $connection->executeStatement('DROP TABLE IF EXISTS `kidata_query_log`');
    }
}