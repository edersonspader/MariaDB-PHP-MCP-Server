<?php

declare(strict_types=1);

namespace App;

use Psr\Log\LoggerInterface;

class Database
{
	private \PDO $pdo;
	private bool $inTransaction = false;

	public function __construct(
		private readonly Config $config,
		private readonly LoggerInterface $logger,
	) {
		$this->connect();
	}

	private function connect(): void
	{
		$dsn = sprintf(
			'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
			$this->config->dbHost,
			$this->config->dbPort,
			$this->config->dbName,
		);

		$this->pdo = new \PDO(
			$dsn,
			$this->config->dbUser,
			$this->config->dbPass,
			[
				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
				\PDO::ATTR_TIMEOUT => $this->config->queryTimeout,
			],
		);
	}

	public function executeRaw(string $sql): \PDOStatement
	{
		try {
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute();
			return $stmt;
		} catch (\PDOException $e) {
			$msg = $e->getMessage();
			if (
				(string) $e->getCode() === 'HY000'
				&& (str_contains($msg, 'gone away') || str_contains($msg, 'Lost connection'))
				&& !$this->inTransaction
			) {
				$this->logger->warning('DB connection lost, reconnecting...');
				$this->connect();
				$stmt = $this->pdo->prepare($sql);
				$stmt->execute();
				return $stmt;
			}
			throw $e;
		}
	}

	public function prepare(string $sql): \PDOStatement
	{
		return $this->pdo->prepare($sql);
	}

	public function escapeIdentifier(string $name): string
	{
		return '`' . str_replace('`', '``', $name) . '`';
	}

	public function getConfig(): Config
	{
		return $this->config;
	}

	public function getLogger(): LoggerInterface
	{
		return $this->logger;
	}

	public function beginTransaction(): void
	{
		$this->pdo->beginTransaction();
		$this->inTransaction = true;
	}

	public function commit(): void
	{
		$this->pdo->commit();
		$this->inTransaction = false;
	}

	public function rollBack(): void
	{
		$this->pdo->rollBack();
		$this->inTransaction = false;
	}

	public function inTransaction(): bool
	{
		return $this->inTransaction;
	}
}
