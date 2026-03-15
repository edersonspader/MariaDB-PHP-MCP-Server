<?php

declare(strict_types=1);

namespace Tests;

use App\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
	/** @var list<string> */
	private array $envKeys = [
		'DB_HOST',
		'DB_PORT',
		'DB_NAME',
		'DB_USER',
		'DB_PASS',
		'DB_QUERY_TIMEOUT',
		'DB_MAX_ROWS',
		'DB_LOG_SQL',
	];

	protected function tearDown(): void
	{
		foreach ($this->envKeys as $key) {
			putenv($key);
		}
	}

	public function testFromEnvReturnsDefaults(): void
	{
		$config = Config::fromEnv();

		$this->assertSame('127.0.0.1', $config->dbHost);
		$this->assertSame('3306', $config->dbPort);
		$this->assertSame('mcp', $config->dbName);
		$this->assertSame('mcp', $config->dbUser);
		$this->assertSame('', $config->dbPass);
		$this->assertSame(30, $config->queryTimeout);
		$this->assertSame(500, $config->maxRows);
		$this->assertFalse($config->logSql);
	}

	public function testFromEnvReadsCustomValues(): void
	{
		putenv('DB_HOST=db.example.com');
		putenv('DB_PORT=3307');
		putenv('DB_NAME=mydb');
		putenv('DB_USER=admin');
		putenv('DB_PASS=secret');
		putenv('DB_QUERY_TIMEOUT=60');
		putenv('DB_MAX_ROWS=100');
		putenv('DB_LOG_SQL=true');

		$config = Config::fromEnv();

		$this->assertSame('db.example.com', $config->dbHost);
		$this->assertSame('3307', $config->dbPort);
		$this->assertSame('mydb', $config->dbName);
		$this->assertSame('admin', $config->dbUser);
		$this->assertSame('secret', $config->dbPass);
		$this->assertSame(60, $config->queryTimeout);
		$this->assertSame(100, $config->maxRows);
		$this->assertTrue($config->logSql);
	}

	public function testFromEnvLogSqlFalseVariants(): void
	{
		putenv('DB_LOG_SQL=false');
		$this->assertFalse(Config::fromEnv()->logSql);

		putenv('DB_LOG_SQL=0');
		$this->assertFalse(Config::fromEnv()->logSql);
	}
}
