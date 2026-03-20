<?php
/**
 * Copyright © QuarryTeam
 */
declare(strict_types=1);

namespace QuarryTeam\CustomerUuid\Plugin;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use QuarryTeam\CustomerUuid\Model\Uuid\CustomerUuidManager;
use QuarryTeam\CustomerUuid\Setup\Patch\Data\AddCustomerUuidAttribute;
use Throwable;

class CustomerRepositoryAssignUuidPlugin
{
    private const MAX_SAVE_ATTEMPTS = 5;

    public function __construct(
        private readonly CustomerUuidManager $customerUuidManager
    ) {
    }

    public function afterSave(
        CustomerRepositoryInterface $subject,
        CustomerInterface $result,
        CustomerInterface $customer,
        $passwordHash = null
    ): CustomerInterface {
        $customerId = (int)($result->getId() ?? 0);
        if ($customerId <= 0) {
            return $result;
        }

        $incomingUuid = trim((string)($customer->getCustomAttribute(AddCustomerUuidAttribute::ATTRIBUTE_CODE)?->getValue() ?? ''));
        $uuid = trim((string)($result->getCustomAttribute(AddCustomerUuidAttribute::ATTRIBUTE_CODE)?->getValue() ?? ''));
        if ($uuid === '') {
            $uuid = $incomingUuid;
        }

        for ($attempt = 0; $attempt < self::MAX_SAVE_ATTEMPTS; $attempt++) {
            $resolvedUuid = $this->customerUuidManager->resolveUuidForSave($customerId, $uuid);

            try {
                $this->customerUuidManager->persistUuid($customerId, $resolvedUuid);
                $result->setCustomAttribute(AddCustomerUuidAttribute::ATTRIBUTE_CODE, $resolvedUuid);
                return $result;
            } catch (Throwable $e) {
                if ($e instanceof LocalizedException && !$this->isDuplicateDbConflict($e)) {
                    throw $e;
                }

                if (!$this->isDuplicateDbConflict($e) || $attempt === self::MAX_SAVE_ATTEMPTS - 1) {
                    throw new CouldNotSaveException(__('Unable to persist customer UUID.'), $e);
                }

                // Duplicate UUID race: generate and retry.
                $uuid = $this->customerUuidManager->generateUnique();
            }
        }

        return $result;
    }

    private function isDuplicateDbConflict(Throwable $e): bool
    {
        $message = strtolower($e->getMessage());
        return str_contains($message, 'duplicate')
            || str_contains($message, 'unique constraint')
            || str_contains($message, 'integrity constraint')
            || str_contains($message, 'quarryteam_customer_uuid');
    }
}

