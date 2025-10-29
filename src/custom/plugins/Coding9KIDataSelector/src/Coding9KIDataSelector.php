<?php declare(strict_types=1);

namespace Coding9\KIDataSelector;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

/**
 * Main plugin class for KI Data Selector
 *
 * Provides AI-powered SQL query generation from natural language
 * with read-only validation, pagination and CSV export.
 *
 * @package Coding9\KIDataSelector
 */
class Coding9KIDataSelector extends Plugin
{
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