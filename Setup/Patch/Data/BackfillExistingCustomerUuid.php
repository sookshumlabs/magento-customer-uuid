<?php
/**
 * Copyright © QuarryTeam
 */
declare(strict_types=1);

namespace QuarryTeam\CustomerUuid\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use QuarryTeam\CustomerUuid\Model\Uuid\GeneratorInterface;

class BackfillExistingCustomerUuid implements DataPatchInterface
{
    private const VALUE_TABLE = 'quarryteam_customer_uuid';

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly ResourceConnection $resourceConnection,
        private readonly GeneratorInterface $uuidGenerator
    ) {
    }

    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        $customerEntityTable = $this->resourceConnection->getTableName('customer_entity');
        $entityTypeTable = $this->resourceConnection->getTableName('eav_entity_type');
        $attributeTable = $this->resourceConnection->getTableName('eav_attribute');
        $valueTable = $this->resourceConnection->getTableName(self::VALUE_TABLE);

        $entityTypeId = (int)$connection->fetchOne(
            $connection->select()->from($entityTypeTable, ['entity_type_id'])->where('entity_type_code = ?', Customer::ENTITY)->limit(1)
        );
        if ($entityTypeId === 0) {
            $connection->endSetup();
            return $this;
        }

        $attributeId = (int)$connection->fetchOne(
            $connection->select()->from($attributeTable, ['attribute_id'])->where('entity_type_id = ?', $entityTypeId)->where('attribute_code = ?', AddCustomerUuidAttribute::ATTRIBUTE_CODE)->limit(1)
        );
        if ($attributeId === 0) {
            $connection->endSetup();
            return $this;
        }

        $select = $connection->select()
            ->from(['ce' => $customerEntityTable], ['entity_id'])
            ->joinLeft(
                ['cev' => $valueTable],
                'cev.entity_id = ce.entity_id AND cev.attribute_id = ' . $attributeId,
                []
            )
            ->where('cev.value_id IS NULL OR cev.value = ?', '');

        $ids = $connection->fetchCol($select);
        foreach ($ids as $entityId) {
            for ($i = 0; $i < 5; $i++) {
                $uuid = $this->uuidGenerator->generate();
                try {
                    $valueId = (int)$connection->fetchOne(
                        $connection->select()->from($valueTable, ['value_id'])->where('attribute_id = ?', $attributeId)->where('entity_id = ?', (int)$entityId)->limit(1)
                    );
                    if ($valueId > 0) {
                        $connection->update($valueTable, ['value' => $uuid], ['value_id = ?' => $valueId]);
                    } else {
                        $connection->insert($valueTable, [
                            'attribute_id' => $attributeId,
                            'entity_id' => (int)$entityId,
                            'value' => $uuid
                        ]);
                    }
                    break;
                } catch (\Throwable $e) {
                    if ($i === 4) {
                        throw $e;
                    }
                }
            }
        }

        $connection->endSetup();
        return $this;
    }

    public static function getDependencies(): array
    {
        return [
            AddCustomerUuidAttribute::class
        ];
    }

    public function getAliases(): array
    {
        return [];
    }
}
