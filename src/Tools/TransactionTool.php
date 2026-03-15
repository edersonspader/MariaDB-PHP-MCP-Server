<?php

declare(strict_types=1);

namespace App\Tools;

use App\Database;
use Mcp\Capability\Attribute\McpTool;

class TransactionTool
{
	public function __construct(private readonly Database $db) {}

	/** @return array{status: string} */
	#[McpTool(
		name: 'begin_transaction',
		description: 'Begin a database transaction. All subsequent exec() calls run inside this transaction until commit_transaction or rollback_transaction.',
	)]
	public function beginTransaction(): array
	{
		if ($this->db->inTransaction()) {
			throw new \RuntimeException('A transaction is already active.');
		}

		$this->db->beginTransaction();
		$this->db->getLogger()->info('begin_transaction');

		return ['status' => 'transaction started'];
	}

	/** @return array{status: string} */
	#[McpTool(
		name: 'commit_transaction',
		description: 'Commit the active transaction, persisting all changes made since begin_transaction.',
	)]
	public function commitTransaction(): array
	{
		if (!$this->db->inTransaction()) {
			throw new \RuntimeException('No active transaction to commit.');
		}

		$this->db->commit();
		$this->db->getLogger()->info('commit_transaction');

		return ['status' => 'transaction committed'];
	}

	/** @return array{status: string} */
	#[McpTool(
		name: 'rollback_transaction',
		description: 'Roll back the active transaction, discarding all changes made since begin_transaction.',
	)]
	public function rollbackTransaction(): array
	{
		if (!$this->db->inTransaction()) {
			throw new \RuntimeException('No active transaction to roll back.');
		}

		$this->db->rollBack();
		$this->db->getLogger()->info('rollback_transaction');

		return ['status' => 'transaction rolled back'];
	}
}
