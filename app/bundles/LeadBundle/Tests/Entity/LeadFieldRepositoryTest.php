<?php

namespace Mautic\LeadBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Portability\Statement;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder as OrmQueryBuilder;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LeadFieldRepositoryTest extends TestCase
{
    /**
     * @var EntityManager|MockObject
     */
    private $entityManager;

    /**
     * @var Connection|MockObject
     */
    private $connection;

    /**
     * @var LeadFieldRepository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManager::class);
        $this->connection    = $this->createMock(Connection::class);

        /** @var ClassMetadata<LeadFieldRepository>|MockObject $classMetadata */
        $classMetadata    = $this->createMock(ClassMetadata::class);
        $this->repository = new LeadFieldRepository($this->entityManager, $classMetadata);
    }

    public function testCompareDateValueForContactField(): void
    {
        $contactId        = 12;
        $fieldAlias       = 'date_field';
        $value            = '2019-04-30';
        $builderAlias     = $this->createMock(QueryBuilder::class);
        $builderCompare   = $this->createMock(QueryBuilder::class);
        $statementAlias   = $this->createMock(Statement::class);
        $statementCompare = $this->createMock(Statement::class);
        $exprCompare      = $this->createMock(ExpressionBuilder::class);

        $this->entityManager->method('getConnection')->willReturn($this->connection);
        $builderAlias->method('expr')->willReturn(new ExpressionBuilder($this->connection));
        $builderCompare->method('expr')->willReturn($exprCompare);

        $this->connection->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->will($this->onConsecutiveCalls($builderCompare, $builderAlias));

        $builderAlias->expects($this->once())
            ->method('select')
            ->with('f.alias, f.is_unique_identifer as is_unique, f.type, f.object')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('from')
            ->with(MAUTIC_TABLE_PREFIX.'lead_fields', 'f')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('setParameter')
            ->with('object', 'company')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('orderBy')
            ->with('f.field_order', 'ASC')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('execute')
            ->willReturn($statementAlias);

        // No company column found. Therefore it's a contact field.
        $statementAlias->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);

        $exprCompare->expects($this->exactly(2))
            ->method('eq')
            ->withConsecutive(
                ['l.id', ':lead'],
                ['l.date_field', ':value'] // See? It's a contact column.
            );

        $builderCompare->expects($this->once())
            ->method('select')
            ->with('l.id')
            ->willReturnSelf();

        $builderCompare->expects($this->once())
            ->method('from')
            ->with(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->willReturnSelf();

        $builderCompare->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $builderCompare->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['lead', $contactId],
                ['value', $value]
            )
            ->willReturnSelf();

        $builderCompare->expects($this->once())
            ->method('execute')
            ->willReturn($statementCompare);

        // No contact ID was found by the value so the result should be false.
        $statementCompare->expects($this->once())
            ->method('fetch')
            ->willReturn([]);

        $this->assertFalse($this->repository->compareDateValue($contactId, $fieldAlias, $value));
    }

    public function testCompareDateValueForCompanyField(): void
    {
        $contactId        = 12;
        $fieldAlias       = 'date_field';
        $value            = '2019-04-30';
        $builderAlias     = $this->createMock(QueryBuilder::class);
        $builderCompare   = $this->createMock(QueryBuilder::class);
        $statementAlias   = $this->createMock(Statement::class);
        $statementCompare = $this->createMock(Statement::class);
        $exprCompare      = $this->createMock(ExpressionBuilder::class);

        $this->entityManager->method('getConnection')->willReturn($this->connection);
        $builderAlias->method('expr')->willReturn(new ExpressionBuilder($this->connection));
        $builderCompare->method('expr')->willReturn($exprCompare);

        $this->connection->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->will($this->onConsecutiveCalls($builderCompare, $builderAlias));

        $builderAlias->expects($this->once())
            ->method('select')
            ->with('f.alias, f.is_unique_identifer as is_unique, f.type, f.object')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('from')
            ->with(MAUTIC_TABLE_PREFIX.'lead_fields', 'f')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('setParameter')
            ->with('object', 'company')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('orderBy')
            ->with('f.field_order', 'ASC')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('execute')
            ->willReturn($statementAlias);

        // A company column found. Therefore it's a company field.
        $statementAlias->expects($this->once())
            ->method('fetchAll')
            ->willReturn([['alias' => $fieldAlias]]);

        $exprCompare->expects($this->exactly(2))
            ->method('eq')
            ->withConsecutive(
                ['l.id', ':lead'],
                ['company.date_field', ':value'] // See? It's a company column.
            );

        $builderCompare->expects($this->exactly(2))
            ->method('leftJoin')
            ->withConsecutive(
                ['l', MAUTIC_TABLE_PREFIX.'companies_leads', 'companies_lead', 'l.id = companies_lead.lead_id'],
                ['companies_lead', MAUTIC_TABLE_PREFIX.'companies', 'company', 'companies_lead.company_id = company.id']
            );

        $builderCompare->expects($this->once())
            ->method('select')
            ->with('l.id')
            ->willReturnSelf();

        $builderCompare->expects($this->once())
            ->method('from')
            ->with(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->willReturnSelf();

        $builderCompare->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $builderCompare->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['lead', $contactId],
                ['value', $value]
            )
            ->willReturnSelf();

        $builderCompare->expects($this->once())
            ->method('execute')
            ->willReturn($statementCompare);

        // A contact ID was found by the value so the result should be true.
        $statementCompare->expects($this->once())
            ->method('fetch')
            ->willReturn(['id' => 456]);

        $this->assertTrue($this->repository->compareDateValue($contactId, $fieldAlias, $value));
    }

    public function testGetListablePublishedFields(): void
    {
        $query = $this->createQueryMock();
        $this->entityManager->expects($this->once())
            ->method('createQuery')
            ->with('SELECT f FROM  f INDEX BY f.id WHERE f.isListable = 1 AND f.isPublished = 1 ORDER BY f.object ASC')
            ->willReturn($query);

        $query->method('execute')->willReturn([]);

        $this->assertInstanceOf(ArrayCollection::class, $this->repository->getListablePublishedFields());
    }

    public function testGetFieldSchemaData(): void
    {
        $query = $this->createQueryMock();
        $this->entityManager->expects($this->once())
            ->method('createQuery')
            ->with('SELECT f.alias, f.label, f.type, f.isUniqueIdentifer FROM  f INDEX BY f.alias WHERE f.object = :object')
            ->willReturn($query);

        $result = [];
        $query->method('execute')->willReturn($result);

        $this->assertSame($result, $this->repository->getFieldSchemaData('lead'));
    }

    public function testGetFieldThatIsMissingColumnWhenMutlipleColumsMissing(): void
    {
        $queryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);

        $this->entityManager->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects(self::once())
            ->method('select')
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('from')
            ->willReturnSelf();

        $expr = $this->createMock(Query\Expr::class);
        $queryBuilder->expects(self::once())
            ->method('expr')
            ->willReturn($expr);

        $comparison = $this->createMock(Query\Expr\Comparison::class);
        $expr->expects(self::once())
            ->method('eq')
            ->willReturn($comparison);

        $queryBuilder->expects(self::once())
            ->method('where')
            ->with($comparison)
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('orderBy')
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();

        $query = $this->createMock(AbstractQuery::class);
        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $leadField = $this->createMock(LeadField::class);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn($leadField);

        self::assertSame(
            $leadField,
            $this->repository->getFieldThatIsMissingColumn()
        );
    }

    private function createQueryMock(): MockObject
    {
        // This is terrible, but the Query class is final and AbstractQuery doesn't have some methods used.
        $query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setParameters',
                'setFirstResult',
                'setMaxResults',
                'getSingleResult',
                'getSQL',
                '_doExecute',
                'execute',
            ])
            ->getMock();

        $ormBuilder = new OrmQueryBuilder($this->entityManager);
        $this->entityManager->method('createQueryBuilder')->willReturn($ormBuilder);
        $this->entityManager->method('createQuery')->willReturn($query);
        $query->method('setParameters')->willReturnSelf();
        $query->method('setFirstResult')->willReturnSelf();
        $query->method('setMaxResults')->willReturnSelf();

        return $query;
    }
}
