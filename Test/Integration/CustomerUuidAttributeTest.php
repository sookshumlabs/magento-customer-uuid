<?php
declare(strict_types=1);

namespace QuarryTeam\CustomerUuid\Test\Integration;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class CustomerUuidAttributeTest extends TestCase
{
    public function testAttributeExistsAndIsStatic(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var EavConfig $eavConfig */
        $eavConfig = $objectManager->get(EavConfig::class);
        $attribute = $eavConfig->getAttribute(Customer::ENTITY, 'uuid');

        $this->assertNotEmpty($attribute->getAttributeId());
        $this->assertSame('varchar', (string)$attribute->getBackendType());
        $this->assertTrue((bool)$attribute->getIsUsedInGrid());
    }

    public function testUuidIsAutoAssignedOnSaveAndUnique(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var CustomerInterfaceFactory $customerFactory */
        $customerFactory = $objectManager->get(CustomerInterfaceFactory::class);
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $objectManager->get(CustomerRepositoryInterface::class);

        $customer1 = $customerFactory->create();
        $customer1->setFirstname('Anu');
        $customer1->setLastname('Kumari');
        $customer1->setEmail('anu@sookshum-labs.com');
        $customer1->setWebsiteId(1);
        $customer1->setGroupId(1);
        $customer1 = $customerRepository->save($customer1, 'anu@sookshum');

        $customer2 = $customerFactory->create();
        $customer2->setFirstname('Anu');
        $customer2->setLastname('Kumari 1');
        $customer2->setEmail('anu2@sookshum-labs.com');
        $customer2->setWebsiteId(1);
        $customer2->setGroupId(1);
        $customer2 = $customerRepository->save($customer2, 'anu2@sookshum');

        /** @var EavConfig $eavConfig */
        $eavConfig = $objectManager->get(EavConfig::class);
        $attribute = $eavConfig->getAttribute(Customer::ENTITY, 'uuid');
        $attributeId = (int)$attribute->getAttributeId();
        $valueTable = trim((string)$attribute->getBackendTable());
        if ($valueTable === '') {
            $valueTable = 'customer_entity_varchar';
        }

        /** @var ResourceConnection $resourceConnection */
        $resourceConnection = $objectManager->get(ResourceConnection::class);
        $connection = $resourceConnection->getConnection();
        $valueTable = $resourceConnection->getTableName($valueTable);

        $uuid1 = (string)$connection->fetchOne(
            $connection->select()
                ->from($valueTable, ['value'])
                ->where('entity_id = ?', (int)$customer1->getId())
                ->where('attribute_id = ?', $attributeId)
                ->limit(1)
        );
        $uuid2 = (string)$connection->fetchOne(
            $connection->select()
                ->from($valueTable, ['value'])
                ->where('entity_id = ?', (int)$customer2->getId())
                ->where('attribute_id = ?', $attributeId)
                ->limit(1)
        );

        $this->assertNotSame('', trim($uuid1));
        $this->assertNotSame('', trim($uuid2));
        $this->assertNotSame($uuid1, $uuid2);
    }
}

