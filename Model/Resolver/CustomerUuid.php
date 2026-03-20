<?php
/**
 * Copyright © QuarryTeam
 */
declare(strict_types=1);

namespace QuarryTeam\CustomerUuid\Model\Resolver;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use QuarryTeam\CustomerUuid\Model\Uuid\CustomerUuidManager;
use QuarryTeam\CustomerUuid\Setup\Patch\Data\AddCustomerUuidAttribute;

class CustomerUuid implements ResolverInterface
{
    public function __construct(
        private readonly CustomerUuidManager $customerUuidManager
    ) {
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ): ?string {
        $customer = $value['model'] ?? null;
        if (!$customer instanceof CustomerInterface) {
            return null;
        }

        $uuid = (string)($customer->getCustomAttribute(AddCustomerUuidAttribute::ATTRIBUTE_CODE)?->getValue() ?? '');
        if (trim($uuid) === '') {
            $uuid = $this->customerUuidManager->getExistingUuid((int)$customer->getId());
        }

        $uuid = trim($uuid);
        return $uuid !== '' ? $uuid : null;
    }
}

