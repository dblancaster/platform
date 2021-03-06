<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\ORM;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class InsertFromSelectQueryExecutorTest extends WebTestCase
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $queryExecutor;

    protected function setUp(): void
    {
        $this->initClient();
        $this->registry = $this->getContainer()->get('doctrine');
        $this->queryExecutor = $this->getContainer()->get('oro_entity.orm.insert_from_select_query_executor');
    }

    public function testInsert()
    {
        $decimalValue = 12345678.29;

        $group = $this->registry
            ->getManagerForClass('OroUserBundle:Group')
            ->getRepository('OroUserBundle:Group')
            ->findOneBy(['name' => 'Administrators']);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->registry
            ->getManagerForClass('OroUserBundle:User')
            ->getRepository('OroUserBundle:User')
            ->createQueryBuilder('u')
            ->select('u.email')
            ->addSelect('u.id')
            ->addSelect("$decimalValue")
            ->addSelect('(TRUE)')
            ->addSelect('u.createdAt')
            ->addSelect('u.id')
            ->addSelect('IDENTITY(u.organization)')
            ->innerJoin('u.groups', 'g')
            ->where('u.createdAt <= :datetime')
            ->andWhere('g = :group')
            ->setParameter('datetime', new \DateTime(), Types::DATETIME_MUTABLE)
            ->setParameter('group', $group)
        ;

        $affectedRecords = $this->queryExecutor->execute(
            'OroTestFrameworkBundle:Item',
            [
                'stringValue',
                'integerValue',
                'decimalValue',
                'booleanValue',
                'datetimeValue',
                'owner',
                'organization',
            ],
            $queryBuilder
        );
        $this->assertEquals(1, $affectedRecords);

        /** @var User[] $users */
        $users = $this->registry
            ->getManagerForClass(User::class)
            ->getRepository(User::class)
            ->findAll();

        /** @var Item[] $items */
        $items = $this->registry->getManagerForClass(Item::class)
            ->getRepository(Item::class)
            ->findAll();

        $this->assertNotEmpty($items);
        $this->assertCount(count($users), $items);

        foreach ($users as $index => $user) {
            $item = $items[$index];
            $this->assertEquals($user->getEmail(), $item->stringValue);
            $this->assertEquals($user->getId(), $item->integerValue);
            $this->assertEquals($decimalValue, $item->decimalValue);
            $this->assertTrue($item->booleanValue);
            $this->assertEquals($user->getCreatedAt(), $item->datetimeValue);
            $this->assertSame($user, $item->owner);
            $this->assertSame($user->getOrganization(), $item->organization);
        }
    }
}
