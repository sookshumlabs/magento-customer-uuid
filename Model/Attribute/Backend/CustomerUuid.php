<?php
/**
 * Copyright © QuarryTeam
 */
declare(strict_types=1);

namespace QuarryTeam\CustomerUuid\Model\Attribute\Backend;

use Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend;
use Magento\Framework\DataObject;
use QuarryTeam\CustomerUuid\Model\Uuid\CustomerUuidManager;
use QuarryTeam\CustomerUuid\Setup\Patch\Data\AddCustomerUuidAttribute;

class CustomerUuid extends AbstractBackend
{
    public function __construct(
        private readonly CustomerUuidManager $customerUuidManager
    ) {
    }

    /**
     * Enforce immutable UUID and auto-generate when missing.
     */
    public function beforeSave($object): static
    {
        if (!$object instanceof DataObject) {
            return parent::beforeSave($object);
        }

        $customerId = (int)($object->getData('entity_id') ?: $object->getId() ?: 0);
        $incomingUuid = (string)($object->getData(AddCustomerUuidAttribute::ATTRIBUTE_CODE) ?? '');
        $resolvedUuid = $this->customerUuidManager->resolveUuidForSave($customerId, $incomingUuid);
        $object->setData(AddCustomerUuidAttribute::ATTRIBUTE_CODE, $resolvedUuid);

        return parent::beforeSave($object);
    }
}

