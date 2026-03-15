<?php

declare(strict_types=1);

namespace Tests\Tools;

use App\Database;
use App\Tools\TransactionTool;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[AllowMockObjectsWithoutExpectations]
class TransactionToolTest extends TestCase
{
	/** @var Database&MockObject */
	private Database $db;

	private TransactionTool $tool;

	protected function setUp(): void
	{
		$this->db = $this->createMock(Database::class);
		$this->db->method('getLogger')->willReturn(new NullLogger());

		$this->tool = new TransactionTool($this->db);
	}

	public function testBeginTransactionSucceeds(): void
	{
		$this->db->method('inTransaction')->willReturn(false);
		$this->db->expects($this->once())->method('beginTransaction');

		$result = $this->tool->beginTransaction();

		$this->assertSame(['status' => 'transaction started'], $result);
	}

	public function testBeginTransactionThrowsWhenAlreadyActive(): void
	{
		$this->db->method('inTransaction')->willReturn(true);

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('A transaction is already active.');

		$this->tool->beginTransaction();
	}

	public function testCommitTransactionSucceeds(): void
	{
		$this->db->method('inTransaction')->willReturn(true);
		$this->db->expects($this->once())->method('commit');

		$result = $this->tool->commitTransaction();

		$this->assertSame(['status' => 'transaction committed'], $result);
	}

	public function testCommitTransactionThrowsWhenNoneActive(): void
	{
		$this->db->method('inTransaction')->willReturn(false);

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('No active transaction to commit.');

		$this->tool->commitTransaction();
	}

	public function testRollbackTransactionSucceeds(): void
	{
		$this->db->method('inTransaction')->willReturn(true);
		$this->db->expects($this->once())->method('rollBack');

		$result = $this->tool->rollbackTransaction();

		$this->assertSame(['status' => 'transaction rolled back'], $result);
	}

	public function testRollbackTransactionThrowsWhenNoneActive(): void
	{
		$this->db->method('inTransaction')->willReturn(false);

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('No active transaction to roll back.');

		$this->tool->rollbackTransaction();
	}
}
