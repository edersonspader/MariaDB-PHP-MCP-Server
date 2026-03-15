<?php

declare(strict_types=1);

namespace App\Tools;

use App\Database;
use Mcp\Capability\Attribute\McpTool;

class QueryTool
{
	public function __construct(private readonly Database $db) {}

	/** @return array<string, mixed> */
	#[McpTool(
		name: 'exec',
		description: 'Execute any SQL query. SELECT queries without LIMIT are automatically paginated. Returns { data, meta } for SELECT or { affected, execution_time_ms } for DML/DDL.',
	)]
	public function exec(string $sql, int $page = 1, int $pageSize = 0): array
	{
		$config = $this->db->getConfig();
		$logger = $this->db->getLogger();

		$pageSize = $pageSize > 0 ? $pageSize : $config->maxRows;
		$page = max(1, $page);

		if ($config->logSql) {
			$logger->info('exec', ['sql' => $sql, 'page' => $page, 'page_size' => $pageSize]);
		}

		try {
			$start = microtime(true);
			$isSelect = stripos(ltrim($sql), 'SELECT') === 0;
			$hasLimit = (bool) preg_match('/\bLIMIT\b/i', $sql);

			if ($isSelect && !$hasLimit) {
				$offset = ($page - 1) * $pageSize;
				$paginatedSql = rtrim($sql, '; ') . ' LIMIT ' . ($pageSize + 1) . ' OFFSET ' . $offset;
				$rows = $this->db->executeRaw($paginatedSql)->fetchAll();
				$hasMore = count($rows) > $pageSize;

				if ($hasMore) {
					array_pop($rows);
				}

				return [
					'data' => $rows,
					'meta' => [
						'page' => $page,
						'page_size' => $pageSize,
						'row_count' => count($rows),
						'has_more' => $hasMore,
						'execution_time_ms' => (int) round((microtime(true) - $start) * 1000),
					],
				];
			}

			$stmt = $this->db->executeRaw($sql);
			$elapsed = (int) round((microtime(true) - $start) * 1000);

			if ($stmt->columnCount()) {
				$rows = $stmt->fetchAll();

				return [
					'data' => $rows,
					'meta' => [
						'page' => 1,
						'page_size' => count($rows),
						'row_count' => count($rows),
						'has_more' => false,
						'execution_time_ms' => $elapsed,
					],
				];
			}

			return ['affected' => $stmt->rowCount(), 'execution_time_ms' => $elapsed];
		} catch (\PDOException $e) {
			$logger->error('exec error', ['error' => $e->getMessage(), 'sql' => $sql]);
			throw new \RuntimeException('Database error: ' . $e->getMessage());
		}
	}
}
