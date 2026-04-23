<?php

declare(strict_types=1);

namespace App\Tools;

use App\Database;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;

class SchemaTool
{
	public function __construct(private readonly Database $db) {}

	/** @return list<array<string, mixed>> */
	#[McpTool(
		name: 'list_tables',
		description: 'List all tables in a database (name, type, engine, row count, comment). Defaults to the configured database.',
	)]
	public function listTables(string $database = ''): array
	{
		$database = $database ?: $this->db->getConfig()->dbName;
		try {
			$sql = <<<SQL
				SELECT TABLE_NAME, TABLE_TYPE, ENGINE, TABLE_ROWS, TABLE_COMMENT
				FROM information_schema.TABLES
				WHERE TABLE_SCHEMA = ?
				ORDER BY TABLE_TYPE, TABLE_NAME
			SQL;

			$stmt = $this->db->prepare($sql);
			$stmt->execute([$database]);

			/** @var list<array<string, mixed>> $rows */
			$rows = $stmt->fetchAll() ?: [];

			return $rows;
		} catch (\PDOException $e) {
			throw new ToolCallException('Database error: ' . $e->getMessage());
		}
	}

	/** @return list<array<string, mixed>> */
	#[McpTool(
		name: 'describe_table',
		description: 'Return column metadata for a table: name, type, nullability, default, key, and comment.',
	)]
	public function describeTable(string $table, string $database = ''): array
	{
		$database = $database ?: $this->db->getConfig()->dbName;
		try {
			$sql = <<<SQL
				SELECT COLUMN_NAME, ORDINAL_POSITION, COLUMN_DEFAULT, IS_NULLABLE,
					DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION, NUMERIC_SCALE,
					COLUMN_TYPE, COLUMN_KEY, EXTRA, COLUMN_COMMENT
				FROM information_schema.COLUMNS
				WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
				ORDER BY ORDINAL_POSITION
			SQL;

			$stmt = $this->db->prepare($sql);
			$stmt->execute([$database, $table]);

			/** @var list<array<string, mixed>> $rows */
			$rows = $stmt->fetchAll() ?: [];

			return $rows;
		} catch (\PDOException $e) {
			throw new ToolCallException('Database error: ' . $e->getMessage());
		}
	}

	/** @return list<array<string, mixed>> */
	#[McpTool(
		name: 'show_indexes',
		description: 'Return all indexes of a table: name, uniqueness, column order, type (BTREE/HASH/FULLTEXT), and cardinality.',
	)]
	public function showIndexes(string $table, string $database = ''): array
	{
		$database = $database ?: $this->db->getConfig()->dbName;
		try {
			$sql = <<<SQL
				SELECT INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME,
					COLLATION, CARDINALITY, INDEX_TYPE, COMMENT, INDEX_COMMENT
				FROM information_schema.STATISTICS
				WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
				ORDER BY INDEX_NAME, SEQ_IN_INDEX
			SQL;

			$stmt = $this->db->prepare($sql);
			$stmt->execute([$database, $table]);

			/** @var list<array<string, mixed>> $rows */
			$rows = $stmt->fetchAll() ?: [];

			return $rows;
		} catch (\PDOException $e) {
			throw new ToolCallException('Database error: ' . $e->getMessage());
		}
	}

	/** @return list<array<string, mixed>> */
	#[McpTool(
		name: 'list_foreign_keys',
		description: 'Return foreign-key constraints with referenced table/column and ON DELETE/UPDATE rules. Leave table empty to list all constraints in the database.',
	)]
	public function listForeignKeys(string $table = '', string $database = ''): array
	{
		$database = $database ?: $this->db->getConfig()->dbName;
		try {
			$sql = <<<SQL
				SELECT kcu.CONSTRAINT_NAME, kcu.TABLE_NAME, kcu.COLUMN_NAME,
					kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME,
					rc.UPDATE_RULE, rc.DELETE_RULE
				FROM information_schema.KEY_COLUMN_USAGE kcu
				JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
					ON rc.CONSTRAINT_SCHEMA = kcu.TABLE_SCHEMA
					AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
				WHERE kcu.TABLE_SCHEMA = ?
					AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
			SQL;

			$params = [$database];

			if ($table !== '') {
				$sql .= ' AND kcu.TABLE_NAME = ?';
				$params[] = $table;
			}

			$sql .= ' ORDER BY kcu.TABLE_NAME, kcu.CONSTRAINT_NAME';

			$stmt = $this->db->prepare($sql);
			$stmt->execute($params);

			/** @var list<array<string, mixed>> $rows */
			$rows = $stmt->fetchAll() ?: [];

			return $rows;
		} catch (\PDOException $e) {
			throw new ToolCallException('Database error: ' . $e->getMessage());
		}
	}

	/** @return array<string, mixed> */
	#[McpTool(
		name: 'show_create_table',
		description: 'Return the full CREATE TABLE DDL for a table (equivalent to SHOW CREATE TABLE).',
	)]
	public function showCreateTable(string $table, string $database = ''): array
	{
		$database = $database ?: $this->db->getConfig()->dbName;
		try {
			$ident = $this->db->escapeIdentifier($database) . '.' . $this->db->escapeIdentifier($table);
			$stmt = $this->db->executeRaw("SHOW CREATE TABLE {$ident}");
			$fetched = $stmt->fetch();
			/** @var array<string, mixed> $row */
			$row = is_array($fetched) ? $fetched : [];

			return $row;
		} catch (\PDOException $e) {
			throw new ToolCallException('Database error: ' . $e->getMessage());
		}
	}
}
