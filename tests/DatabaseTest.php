<?php

declare(strict_types=1);

namespace Tests;

use App\Config;
use App\Database;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[AllowMockObjectsWithoutExpectations]
class DatabaseTest extends TestCase
{
	private Database $db;

	protected function setUp(): void
	{
		putenv('DB_HOST=127.0.0.1');
		$config = Config::fromEnv();

		$this->db = $this->getMockBuilder(Database::class)
			->setConstructorArgs([$config, new NullLogger()])
			->disableOriginalConstructor()
			->onlyMethods([])
			->getMock();
	}

	protected function tearDown(): void
	{
		putenv('DB_HOST');
	}

	public function testEscapeIdentifierSimple(): void
	{
		$result = $this->db->escapeIdentifier('users');

		$this->assertSame('`users`', $result);
	}

	public function testEscapeIdentifierWithBacktick(): void
	{
		$result = $this->db->escapeIdentifier('tab`le');

		$this->assertSame('`tab``le`', $result);
	}

	public function testEscapeIdentifierEmpty(): void
	{
		$result = $this->db->escapeIdentifier('');

		$this->assertSame('``', $result);
	}
}
