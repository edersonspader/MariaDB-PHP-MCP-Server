# MCP MariaDB Server

> 🇧🇷 [Leia em Português (Brasil)](docs/README.pt-BR.md) · 🇵🇹 [Leia em Português (Portugal)](docs/README.pt-PT.md)

MCP (Model Context Protocol) Server for AI-assisted interaction with MariaDB/MySQL databases.

Exposes **10 tools** covering SQL execution, schema introspection, transactions, and health checks.

## Features

- SQL execution with **automatic pagination** for SELECT queries.
- **Schema discovery**: list tables, describe columns, indexes, foreign keys, and full DDL.
- **Transaction support**: begin / commit / rollback.
- **Health check** (`ping`) with server version and latency.
- Auto-reconnect on lost connections.
- Configuration via environment variables.
- Logging of all operations in `var/logs/mcp.log`.

## Installation

### Requirements

- PHP 8.5+
- Composer

```bash
composer install
```

## Configuration

Copy `.env.example` to `.env` and edit as needed:

```bash
cp .env.example .env
```

| Environment variable | Default     | Description                              |
|----------------------|-------------|------------------------------------------|
| `DB_HOST`            | `127.0.0.1` | Database host                            |
| `DB_PORT`            | `3306`      | Database port                            |
| `DB_NAME`            | `mcp`       | Database name                            |
| `DB_USER`            | `mcp`       | Database user                            |
| `DB_PASS`            | *(empty)*   | Database password                        |
| `DB_QUERY_TIMEOUT`   | `30`        | Query timeout in seconds                 |
| `DB_MAX_ROWS`        | `500`       | Default page size for SELECT results     |
| `DB_LOG_SQL`         | `false`     | Log executed SQL statements to log file  |

## Running

```bash
composer serve
# or
php server.php
```

The server communicates over **stdio** and follows the MCP protocol.

## Available Tools

### `exec`
Execute any SQL query. SELECT queries without a `LIMIT` clause are automatically paginated.

| Parameter  | Type    | Required | Description                                   |
|------------|---------|----------|-----------------------------------------------|
| `sql`      | string  | ✓        | SQL query to execute                          |
| `page`     | integer |          | Page number (1-based, default `1`)            |
| `pageSize` | integer |          | Rows per page (default `DB_MAX_ROWS`)         |

**Response — SELECT:**
```json
{ "data": [...], "meta": { "page": 1, "page_size": 500, "row_count": 42, "has_more": false, "execution_time_ms": 3 } }
```
**Response — DML/DDL:**
```json
{ "affected": 1, "execution_time_ms": 2 }
```

### `list_tables`
List all tables in a database (name, type, engine, row count, comment).

| Parameter  | Type   | Required | Description                          |
|------------|--------|----------|--------------------------------------|
| `database` | string |          | Defaults to the configured database  |

### `describe_table`
Return column metadata: name, type, nullability, default, key, and comment.

| Parameter  | Type   | Required | Description                          |
|------------|--------|----------|--------------------------------------|
| `table`    | string | ✓        | Table name                           |
| `database` | string |          | Defaults to the configured database  |

### `show_indexes`
Return all indexes of a table with index name, uniqueness, column order, and type.

| Parameter  | Type   | Required | Description                          |
|------------|--------|----------|--------------------------------------|
| `table`    | string | ✓        | Table name                           |
| `database` | string |          | Defaults to the configured database  |

### `list_foreign_keys`
Return foreign-key constraints with referenced table/column and ON DELETE/UPDATE rules.

| Parameter  | Type   | Required | Description                              |
|------------|--------|----------|------------------------------------------|
| `table`    | string |          | Filter by table; empty = all tables      |
| `database` | string |          | Defaults to the configured database      |

### `show_create_table`
Return the full `CREATE TABLE` DDL statement.

| Parameter  | Type   | Required | Description                          |
|------------|--------|----------|--------------------------------------|
| `table`    | string | ✓        | Table name                           |
| `database` | string |          | Defaults to the configured database  |

### `begin_transaction`
Begin a database transaction.

### `commit_transaction`
Commit the active transaction.

### `rollback_transaction`
Roll back the active transaction.

### `ping`
Check connectivity. Returns `{ status, server_version, hostname, database, execution_time_ms }`.

## Claude Desktop Integration

Add to your `claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "MariaDB": {
      "command": "php",
      "args": ["/absolute/path/to/server.php"],
      "env": {
        "DB_HOST": "127.0.0.1",
        "DB_NAME": "mydb",
        "DB_USER": "mcp",
        "DB_PASS": "secret"
      }
    }
  }
}
```

The config file is located at:
- **macOS**: `~/Library/Application Support/Claude/claude_desktop_config.json`
- **Windows**: `%APPDATA%\Claude\claude_desktop_config.json`
- **Linux**: `~/.config/Claude/claude_desktop_config.json`

## VS Code Integration

Add to your `.vscode/mcp.json`:

```json
{
  "servers": {
    "MariaDB": {
      "type": "stdio",
      "command": "php",
      "args": ["/absolute/path/to/server.php"],
      "env": {
        "DB_HOST": "127.0.0.1",
        "DB_NAME": "mydb",
        "DB_USER": "mcp",
        "DB_PASS": "secret"
      }
    }
  }
}
```

## Development

```bash
composer test   # run PHPUnit test suite
composer stan   # run PHPStan static analysis (level max)
```

## Security

- Never commit `.env` or credentials to version control.
- The `exec` tool accepts raw SQL — use a dedicated database user with only the privileges the AI actually needs.

## Logs

All operations are written to `var/logs/mcp.log`. Set `DB_LOG_SQL=true` to also log the executed SQL statements.
