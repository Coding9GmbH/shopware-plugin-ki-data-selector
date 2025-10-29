<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Doctrine\DBAL\Connection;

class IntelligentSearchOptimizer extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);
        
        if ($uninstallContext->keepUserData()) {
            return;
        }

        $connection = $this->container->get(Connection::class);
        
        $connection->executeStatement('DROP TABLE IF EXISTS `search_query_log`');
        $connection->executeStatement('DROP TABLE IF EXISTS `search_synonym`');
        $connection->executeStatement('DROP TABLE IF EXISTS `search_redirect`');
    }

    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);
    }

    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        parent::deactivate($deactivateContext);
    }
}