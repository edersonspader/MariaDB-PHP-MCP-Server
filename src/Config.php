<?php

declare(strict_types=1);

namespace App;

class Config
{
	private function __construct(
		public readonly string $dbHost,
		public readonly string $dbPort,
		public readonly string $dbName,
		public readonly string $dbUser,
		public readonly string $dbPass,
		public readonly int $queryTimeout,
		public readonly int $maxRows,
		public readonly bool $logSql,
	) {}

	public static function fromEnv(): self
	{
		return new self(
			dbHost: getenv('DB_HOST') ?: '127.0.0.1',
			dbPort: getenv('DB_PORT') ?: '3306',
			dbName: getenv('DB_NAME') ?: 'mcp',
			dbUser: getenv('DB_USER') ?: 'mcp',
			dbPass: getenv('DB_PASS') ?: '',
			queryTimeout: (int) (getenv('DB_QUERY_TIMEOUT') ?: 30),
			maxRows: (int) (getenv('DB_MAX_ROWS') ?: 500),
			logSql: filter_var(getenv('DB_LOG_SQL') ?: false, FILTER_VALIDATE_BOOLEAN),
		);
	}
}
