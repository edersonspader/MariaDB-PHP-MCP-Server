<?php

declare(strict_types=1);

namespace Tests\Tools;

use App\Config;
use App\Database;
use App\Tools\HealthTool;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[AllowMockObjectsWithoutExpectations]
class HealthToolTest extends TestCase
{
	/** @var Database&MockObject */
	private Database $db;

	private HealthTool $tool;

	protected function setUp(): void
	{
		putenv('DB_NAME=testdb');

		$this->db = $this->createMock(Database::class);
		$this->db->method('getConfig')->willReturn(Config::fromEnv());
		$this->db->method('getLogger')->willReturn(new NullLogger());

		$this->tool = new HealthTool($this->db);
	}

	protected function tearDown(): void
	{
		putenv('DB_NAME');
	}

	public function testPingReturnsExpectedStructure(): void
	{
		$row = ['ok' => 1, 'server_version' => '10.6.0-MariaDB', 'hostname' => 'dbhost'];
		$stmt = $this->createMock(\PDOStatement::class);
		$stmt->method('fetch')->willReturn($row);

		$this->db->method('executeRaw')->willReturn($stmt);

		$result = $this->tool->ping();

		$this->assertSame('ok', $result['status']);
		$this->assertSame('10.6.0-MariaDB', $result['server_version']);
		$this->assertSame('dbhost', $result['hostname']);
		$this->assertSame('testdb', $result['database']);
		$this->assertGreaterThanOrEqual(0, $result['execution_time_ms']);
	}

	public function testPingThrowsRuntimeExceptionOnPdoError(): void
	{
		$this->db->method('executeRaw')
			->willThrowException(new \PDOException('lost connection'));

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessageMatches('/lost connection/');

		$this->tool->ping();
	}
}
