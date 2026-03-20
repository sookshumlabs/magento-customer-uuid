<?php
/**
 * Copyright © QuarryTeam
 */
declare(strict_types=1);

namespace QuarryTeam\CustomerUuid\Model\Uuid;

use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use QuarryTeam\CustomerUuid\Setup\Patch\Data\AddCustomerUuidAttribute;

class CustomerUuidManager
{
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly EavConfig $eavConfig,
        private readonly GeneratorInterface $uuidGenerator
    ) {
    }

    /**
     * Resolves final UUID value for a customer save operation.
     *
     * UUID is immutable once persisted for a customer.
     *
     * @throws LocalizedException
     */
    public function resolveUuidForSave(int $customerId, ?string $incomingUuid): string
    {
        $uuid = trim((string)($incomingUuid ?? ''));
        if ($customerId > 0) {
            $existingUuid = trim($this->getExistingUuid($customerId));
            if ($existingUuid !== '') {
                if ($uuid === '' || $uuid === $existingUuid) {
                    return $existingUuid;
                }

                throw new LocalizedException(__('Customer UUID cannot be changed once set.'));
            }
        }

        if ($uuid !== '') {
            return $uuid;
        }

        return $this->generateUnique();
    }

    public function getExistingUuid(int $customerId): string
    {
        $attributeId = $this->getUuidAttributeId();
        if ($attributeId === 0 || $customerId <= 0) {
            return '';
        }

        $connection = $this->resourceConnection->getConnection();
        $table = $this->getUuidStorageTable();

        return (string)$connection->fetchOne(
            $connection->select()->from($table, ['value'])->where('entity_id = ?', $customerId)->where('attribute_id = ?', $attributeId)->limit(1)
        );
    }

    public function persistUuid(int $customerId, string $uuid): void
    {
        $attributeId = $this->getUuidAttributeId();
        $uuid = trim($uuid);
        if ($attributeId === 0 || $customerId <= 0 || $uuid === '') {
            return;
        }

        $connection = $this->resourceConnection->getConnection();
        $table = $this->getUuidStorageTable();
        $valueId = (int)$connection->fetchOne(
            $connection->select()->from($table, ['value_id'])->where('attribute_id = ?', $attributeId)->where('entity_id = ?', $customerId)->limit(1)
        );
        if ($valueId > 0) {
            $connection->update($table, ['value' => $uuid], ['value_id = ?' => $valueId]);
            return;
        }

        $connection->insert($table, [
            'attribute_id' => $attributeId,
            'entity_id' => $customerId,
            'value' => $uuid
        ]);
    }

    public function generateUnique(): string
    {
        return $this->uuidGenerator->generate();
    }

    private function getUuidAttributeId(): int
    {
        $attribute = $this->eavConfig->getAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            AddCustomerUuidAttribute::ATTRIBUTE_CODE
        );

        return (int)$attribute->getAttributeId();
    }

    private function getUuidStorageTable(): string
    {
        $attribute = $this->eavConfig->getAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            AddCustomerUuidAttribute::ATTRIBUTE_CODE
        );
        $backendTable = trim((string)$attribute->getBackendTable());
        if ($backendTable !== '') {
            return $backendTable;
        }

        return $this->resourceConnection->getTableName('customer_entity_varchar');
    }
}

