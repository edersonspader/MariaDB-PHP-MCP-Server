<?php

declare(strict_types=1);

namespace App\Tools;

use App\Database;
use Mcp\Capability\Attribute\McpTool;

class HealthTool
{
	public function __construct(private readonly Database $db) {}

	/** @return array{status: string, server_version: mixed, hostname: mixed, database: string, execution_time_ms: int} */
	#[McpTool(
		description: 'Check connectivity and return server version, hostname, database name, and round-trip time in milliseconds.',
	)]
	public function ping(): array
	{
		try {
			$start = microtime(true);
			/** @var array<string, mixed> $row */
			$row = $this->db->executeRaw(
				'SELECT 1 AS ok, @@version AS server_version, @@hostname AS hostname',
			)->fetch();

			return [
				'status' => 'ok',
				'server_version' => $row['server_version'] ?? null,
				'hostname' => $row['hostname'] ?? null,
				'database' => $this->db->getConfig()->dbName,
				'execution_time_ms' => (int) round((microtime(true) - $start) * 1000),
			];
		} catch (\PDOException $e) {
			$this->db->getLogger()->error('ping error', ['error' => $e->getMessage()]);
			throw new \RuntimeException('Database error: ' . $e->getMessage());
		}
	}
}
