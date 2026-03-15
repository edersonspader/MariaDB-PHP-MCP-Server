<?php

declare(strict_types=1);

namespace Tests\Tools;

use App\Config;
use App\Database;
use App\Tools\QueryTool;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[AllowMockObjectsWithoutExpectations]
class QueryToolTest extends TestCase
{
	/** @var Database&MockObject */
	private Database $db;

	private QueryTool $tool;

	protected function setUp(): void
	{
		putenv('DB_MAX_ROWS=10');
		putenv('DB_LOG_SQL=false');

		$this->db = $this->createMock(Database::class);
		$this->db->method('getConfig')->willReturn(Config::fromEnv());
		$this->db->method('getLogger')->willReturn(new NullLogger());

		$this->tool = new QueryTool($this->db);
	}

	protected function tearDown(): void
	{
		putenv('DB_MAX_ROWS');
		putenv('DB_LOG_SQL');
	}

	public function testExecSelectWithoutLimitReturnsPaginatedResult(): void
	{
		$rows = [['id' => 1, 'name' => 'a'], ['id' => 2, 'name' => 'b']];
		$stmt = $this->createMock(\PDOStatement::class);
		$stmt->method('fetchAll')->willReturn($rows);

		$this->db->method('executeRaw')->willReturn($stmt);

		$result = $this->tool->exec('SELECT * FROM t');

		$data = $result['data'];
		$meta = $result['meta'];
		$this->assertIsArray($data);
		$this->assertIsArray($meta);
		$this->assertCount(2, $data);
		$this->assertSame(1, $meta['page']);
		$this->assertSame(2, $meta['row_count']);
		$this->assertFalse($meta['has_more']);
	}

	public function testExecSelectWithoutLimitDetectsHasMore(): void
	{
		// pageSize=10, so executeRaw fetches 11 rows; returning 11 means has_more=true
		$rows = array_fill(0, 11, ['id' => 1]);
		$stmt = $this->createMock(\PDOStatement::class);
		$stmt->method('fetchAll')->willReturn($rows);

		$this->db->method('executeRaw')->willReturn($stmt);

		$result = $this->tool->exec('SELECT * FROM t');

		$data = $result['data'];
		$meta = $result['meta'];
		$this->assertIsArray($data);
		$this->assertIsArray($meta);
		$this->assertTrue($meta['has_more']);
		$this->assertCount(10, $data);
	}

	public function testExecSelectWithoutLimitUsesPageOffset(): void
	{
		$this->db->expects($this->once())
			->method('executeRaw')
			->with($this->stringContains('LIMIT 11 OFFSET 10'))
			->willReturn($this->stmtWithRows([]));

		$this->tool->exec('SELECT * FROM t', page: 2);
	}

	public function testExecSelectWithLimitIsNotPaginated(): void
	{
		$rows = [['id' => 1]];
		$stmt = $this->createMock(\PDOStatement::class);
		$stmt->method('columnCount')->willReturn(1);
		$stmt->method('fetchAll')->willReturn($rows);

		$this->db->method('executeRaw')->willReturn($stmt);

		$result = $this->tool->exec('SELECT * FROM t LIMIT 5');

		$meta = $result['meta'];
		$this->assertIsArray($meta);
		$this->assertSame(1, $meta['page']);
		$this->assertFalse($meta['has_more']);
	}

	public function testExecDmlReturnsAffectedRows(): void
	{
		$stmt = $this->createMock(\PDOStatement::class);
		$stmt->method('columnCount')->willReturn(0);
		$stmt->method('rowCount')->willReturn(3);

		$this->db->method('executeRaw')->willReturn($stmt);

		$result = $this->tool->exec('DELETE FROM t WHERE id > 0');

		$this->assertSame(3, $result['affected']);
		$this->assertArrayHasKey('execution_time_ms', $result);
	}

	public function testExecThrowsRuntimeExceptionOnPdoError(): void
	{
		$this->db->method('executeRaw')
			->willThrowException(new \PDOException('connection refused'));

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessageMatches('/connection refused/');

		$this->tool->exec('SELECT 1');
	}

	public function testExecLogsSqlWhenEnabled(): void
	{
		putenv('DB_LOG_SQL=true');

		$db = $this->createMock(Database::class);
		$db->method('getConfig')->willReturn(Config::fromEnv());

		$logger = $this->createMock(\Psr\Log\LoggerInterface::class);
		$logger->expects($this->once())->method('info')->with('exec');
		$db->method('getLogger')->willReturn($logger);
		$db->method('executeRaw')->willReturn($this->stmtWithRows([]));

		(new QueryTool($db))->exec('SELECT 1');

		putenv('DB_LOG_SQL=false');
	}

	/** @return \PDOStatement&MockObject */
	private function stmtWithRows(mixed $rows): \PDOStatement
	{
		$stmt = $this->createMock(\PDOStatement::class);
		$stmt->method('fetchAll')->willReturn($rows);

		return $stmt;
	}
}
