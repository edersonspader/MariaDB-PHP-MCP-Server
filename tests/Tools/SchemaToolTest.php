<?php

declare(strict_types=1);

namespace Tests\Tools;

use App\Config;
use App\Database;
use App\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SchemaToolTest extends TestCase
{
	/** @var Database&MockObject */
	private Database $db;

	private SchemaTool $tool;

	private Config $config;

	protected function setUp(): void
	{
		putenv('DB_NAME=testdb');

		$this->config = Config::fromEnv();
		$this->db = $this->createMock(Database::class);
		$this->db->method('getConfig')->willReturn($this->config);

		$this->tool = new SchemaTool($this->db);
	}

	protected function tearDown(): void
	{
		putenv('DB_NAME');
	}

	public function testListTablesReturnsRows(): void
	{
		$rows = [['TABLE_NAME' => 'users', 'TABLE_TYPE' => 'BASE TABLE']];
		$stmt = $this->stmtReturning($rows);
		$this->db->method('prepare')->willReturn($stmt);

		$result = $this->tool->listTables();

		$this->assertSame($rows, $result);
	}

	public function testListTablesUsesDefaultDatabase(): void
	{
		$stmt = $this->createMock(\PDOStatement::class);
		$stmt->method('fetchAll')->willReturn([]);
		$stmt->expects($this->once())
			->method('execute')
			->with(['testdb']);

		$this->db->method('prepare')->willReturn($stmt);

		$this->tool->listTables();
	}

	public function testListTablesAcceptsExplicitDatabase(): void
	{
		$stmt = $this->createMock(\PDOStatement::class);
		$stmt->method('fetchAll')->willReturn([]);
		$stmt->expects($this->once())
			->method('execute')
			->with(['otherdb']);

		$this->db->method('prepare')->willReturn($stmt);

		$this->tool->listTables('otherdb');
	}

	public function testDescribeTableReturnsColumns(): void
	{
		$rows = [['COLUMN_NAME' => 'id', 'DATA_TYPE' => 'int']];
		$stmt = $this->stmtReturning($rows);
		$this->db->method('prepare')->willReturn($stmt);

		$result = $this->tool->describeTable('users');

		$this->assertSame($rows, $result);
	}

	public function testShowIndexesReturnsIndexData(): void
	{
		$rows = [['INDEX_NAME' => 'PRIMARY', 'COLUMN_NAME' => 'id']];
		$stmt = $this->stmtReturning($rows);
		$this->db->method('prepare')->willReturn($stmt);

		$result = $this->tool->showIndexes('users');

		$this->assertSame($rows, $result);
	}

	public function testListForeignKeysWithoutTablePassesSingleParam(): void
	{
		$stmt = $this->createMock(\PDOStatement::class);
		$stmt->method('fetchAll')->willReturn([]);
		$stmt->expects($this->once())
			->method('execute')
			->with(['testdb']);

		$this->db->method('prepare')->willReturn($stmt);

		$this->tool->listForeignKeys();
	}

	public function testListForeignKeysWithTablePassesTwoParams(): void
	{
		$stmt = $this->createMock(\PDOStatement::class);
		$stmt->method('fetchAll')->willReturn([]);
		$stmt->expects($this->once())
			->method('execute')
			->with(['testdb', 'orders']);

		$this->db->method('prepare')->willReturn($stmt);

		$this->tool->listForeignKeys('orders');
	}

	public function testShowCreateTableReturnsRow(): void
	{
		$row = ['Table' => 'users', 'Create Table' => 'CREATE TABLE `users` (...)'];
		$stmt = $this->createMock(\PDOStatement::class);
		$stmt->method('fetch')->willReturn($row);

		$this->db->method('escapeIdentifier')
			->willReturnCallback(fn(string $n): string => '`' . $n . '`');
		$this->db->method('executeRaw')->willReturn($stmt);

		$result = $this->tool->showCreateTable('users');

		$this->assertSame($row, $result);
	}

	public function testShowCreateTableReturnsEmptyArrayWhenNotFound(): void
	{
		$stmt = $this->createMock(\PDOStatement::class);
		$stmt->method('fetch')->willReturn(false);

		$this->db->method('escapeIdentifier')
			->willReturnCallback(fn(string $n): string => '`' . $n . '`');
		$this->db->method('executeRaw')->willReturn($stmt);

		$result = $this->tool->showCreateTable('nonexistent');

		$this->assertSame([], $result);
	}

	public function testSchemaToolThrowsRuntimeExceptionOnPdoError(): void
	{
		$this->db->method('prepare')
			->willThrowException(new \PDOException('table not found'));

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessageMatches('/table not found/');

		$this->tool->listTables();
	}

	/** @return \PDOStatement&MockObject */
	private function stmtReturning(mixed $rows): \PDOStatement
	{
		$stmt = $this->createMock(\PDOStatement::class);
		$stmt->method('fetchAll')->willReturn($rows);

		return $stmt;
	}
}
