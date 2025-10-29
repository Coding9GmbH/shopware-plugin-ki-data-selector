<?php declare(strict_types=1);

namespace ProductSetManager;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class ProductSetManager extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
        $this->createCustomFields($installContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);
        
        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->removeCustomFields($uninstallContext);
    }

    private function createCustomFields(InstallContext $context): void
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        
        $customFieldSetRepository->create([
            [
                'name' => 'product_set_manager',
                'config' => [
                    'label' => [
                        'de-DE' => 'Produkt Set Einstellungen',
                        'en-GB' => 'Product Set Settings'
                    ]
                ],
                'customFields' => [
                    [
                        'name' => 'product_set_code',
                        'type' => CustomFieldTypes::TEXT,
                        'config' => [
                            'label' => [
                                'de-DE' => 'Set-Code',
                                'en-GB' => 'Set Code'
                            ],
                            'helpText' => [
                                'de-DE' => 'Code zur Identifikation des Produkt-Sets',
                                'en-GB' => 'Code to identify the product set'
                            ],
                            'customFieldPosition' => 1
                        ]
                    ]
                ],
                'relations' => [
                    [
                        'entityName' => 'product'
                    ]
                ]
            ]
        ], $context->getContext());
    }

    private function removeCustomFields(UninstallContext $context): void
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'product_set_manager'));
        
        $result = $customFieldSetRepository->searchIds($criteria, $context->getContext());
        
        if ($result->getTotal() > 0) {
            $customFieldSetRepository->delete(
                array_values($result->getData()),
                $context->getContext()
            );
        }
    }
}