<?php
/**
 * Copyright © QuarryTeam
 */
declare(strict_types=1);

namespace QuarryTeam\CustomerUuid\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddCustomerUuidAttribute implements DataPatchInterface
{
    public const ATTRIBUTE_CODE = 'uuid';
    private const ATTRIBUTE_LABEL = 'UUID';
    private const BACKEND_TABLE = 'quarryteam_customer_uuid';

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        /**
         *
         * @see \Magento\Customer\Setup\CustomerSetupFactory
         */
        private readonly \Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory
    ) {
    }

    public function apply(): self
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $connection = $this->moduleDataSetup->getConnection();
        $entityTypeId = (int)$customerSetup->getEavConfig()->getEntityType(Customer::ENTITY)->getId();
        $eavAttributeTable = $this->moduleDataSetup->getTable('eav_attribute');

        $existingAttributeId = (int)$connection->fetchOne(
            $connection->select()->from($eavAttributeTable, ['attribute_id'])->where('entity_type_id = ?', $entityTypeId)->where('attribute_code = ?', self::ATTRIBUTE_CODE)->limit(1)
        );

        if ($existingAttributeId === 0) {
            $customerSetup->addAttribute(
                Customer::ENTITY,
                self::ATTRIBUTE_CODE,
                [
                    'type' => 'varchar',
                    'label' => self::ATTRIBUTE_LABEL,
                    'input' => 'text',
                    'backend_table' => $this->moduleDataSetup->getTable(self::BACKEND_TABLE),
                    'required' => false,
                    'visible' => true,
                    'is_visible' => 1,
                    'user_defined' => false,
                    'is_user_defined' => 0,
                    'system' => 1,
                    'is_system' => 1,
                    'position' => 999,
                    'sort_order' => 999,
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'unique' => true,
                    'default' => null,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => true
                ]
            );
        }

        $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, self::ATTRIBUTE_CODE);

        $attributeSetId = (int)$customerSetup->getDefaultAttributeSetId(Customer::ENTITY);
        $attributeGroupId = (int)$customerSetup->getDefaultAttributeGroupId(Customer::ENTITY, $attributeSetId);
        if ($attributeSetId > 0 && $attributeGroupId > 0) {
            $attribute->setData('attribute_set_id', $attributeSetId);
            $attribute->setData('attribute_group_id', $attributeGroupId);
            $customerSetup->addAttributeToSet(Customer::ENTITY, $attributeSetId, $attributeGroupId, self::ATTRIBUTE_CODE);
        }

        $attribute->setData('used_in_forms', ['adminhtml_customer']);
        $attribute->setData('backend_table', $this->moduleDataSetup->getTable(self::BACKEND_TABLE));
        $attribute->setData('is_user_defined', 0);
        $attribute->setData('is_system', 1);
        $attribute->setData('is_visible', 1);
        $attribute->setData('is_used_in_grid', 1);
        $attribute->setData('is_visible_in_grid', 1);
        $attribute->setData('is_filterable_in_grid', 1);
        $attribute->setData('is_searchable_in_grid', 1);
        $attribute->save();

        $this->moduleDataSetup->getConnection()->endSetup();
        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}

