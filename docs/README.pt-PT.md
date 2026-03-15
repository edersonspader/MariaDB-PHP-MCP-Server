# MCP MariaDB Server

> 🇺🇸 [Read in English](../README.md) · 🇧🇷 [Ler em Português do Brasil](README.pt-BR.md)

Servidor MCP (Model Context Protocol) para interação de IA com bases de dados MariaDB/MySQL.

Expõe **10 ferramentas** que cobrem execução de SQL, introspecção de esquema, transações e verificação de conectividade.

## Funcionalidades

- Execução de SQL com **paginação automática** para consultas SELECT.
- **Descoberta de esquema**: listar tabelas, descrever colunas, índices, chaves estrangeiras e DDL completo.
- **Suporte a transações**: begin / commit / rollback.
- **Verificação de conectividade** (`ping`) com versão do servidor e latência.
- Reconnexão automática em caso de perda de ligação.
- Configuração através de variáveis de ambiente.
- Registo de todas as operações em `var/logs/mcp.log`.

## Instalação

### Requisitos

- PHP 8.5+
- Composer

```bash
composer install
```

## Configuração

Copie o ficheiro `.env.example` para `.env` e edite conforme necessário:

```bash
cp .env.example .env
```

| Variável de ambiente | Predefinição | Descrição                                          |
|----------------------|--------------|----------------------------------------------------|
| `DB_HOST`            | `127.0.0.1`  | Anfitrião da base de dados                         |
| `DB_PORT`            | `3306`       | Porto da base de dados                             |
| `DB_NAME`            | `mcp`        | Nome da base de dados                              |
| `DB_USER`            | `mcp`        | Utilizador da base de dados                        |
| `DB_PASS`            | *(vazio)*    | Palavra-passe da base de dados                     |
| `DB_QUERY_TIMEOUT`   | `30`         | Tempo limite de consulta em segundos               |
| `DB_MAX_ROWS`        | `500`        | Tamanho de página predefinido para resultados SELECT |
| `DB_LOG_SQL`         | `false`      | Registar SQL executado no ficheiro de log          |

## Execução

```bash
composer serve
# ou
php server.php
```

O servidor comunica via **stdio** e segue o protocolo MCP.

## Ferramentas disponíveis

### `exec`
Executa qualquer consulta SQL. Consultas SELECT sem cláusula `LIMIT` são paginadas automaticamente.

| Parâmetro  | Tipo    | Obrigatório | Descrição                                        |
|------------|---------|-------------|--------------------------------------------------|
| `sql`      | string  | ✓           | Consulta SQL a executar                          |
| `page`     | integer |             | Número de página (base 1, predefinição `1`)      |
| `pageSize` | integer |             | Linhas por página (predefinição `DB_MAX_ROWS`)   |

**Resposta — SELECT:**
```json
{ "data": [...], "meta": { "page": 1, "page_size": 500, "row_count": 42, "has_more": false, "execution_time_ms": 3 } }
```
**Resposta — DML/DDL:**
```json
{ "affected": 1, "execution_time_ms": 2 }
```

### `list_tables`
Lista todas as tabelas de uma base de dados (nome, tipo, motor, contagem de linhas, comentário).

| Parâmetro  | Tipo   | Obrigatório | Descrição                                    |
|------------|--------|-------------|----------------------------------------------|
| `database` | string |             | Predefinição: base de dados configurada      |

### `describe_table`
Devolve metadados das colunas: nome, tipo, nulabilidade, valor predefinido, chave e comentário.

| Parâmetro  | Tipo   | Obrigatório | Descrição                                    |
|------------|--------|-------------|----------------------------------------------|
| `table`    | string | ✓           | Nome da tabela                               |
| `database` | string |             | Predefinição: base de dados configurada      |

### `show_indexes`
Devolve todos os índices de uma tabela com nome, unicidade, ordem de colunas e tipo.

| Parâmetro  | Tipo   | Obrigatório | Descrição                                    |
|------------|--------|-------------|----------------------------------------------|
| `table`    | string | ✓           | Nome da tabela                               |
| `database` | string |             | Predefinição: base de dados configurada      |

### `list_foreign_keys`
Devolve chaves estrangeiras com tabela/coluna referenciada e regras ON DELETE/UPDATE.

| Parâmetro  | Tipo   | Obrigatório | Descrição                                    |
|------------|--------|-------------|----------------------------------------------|
| `table`    | string |             | Filtrar por tabela; vazio = todas as tabelas |
| `database` | string |             | Predefinição: base de dados configurada      |

### `show_create_table`
Devolve o DDL completo (`CREATE TABLE`) de uma tabela.

| Parâmetro  | Tipo   | Obrigatório | Descrição                                    |
|------------|--------|-------------|----------------------------------------------|
| `table`    | string | ✓           | Nome da tabela                               |
| `database` | string |             | Predefinição: base de dados configurada      |

### `begin_transaction`
Inicia uma transação na base de dados.

### `commit_transaction`
Confirma a transação activa.

### `rollback_transaction`
Reverte a transação activa.

### `ping`
Verifica a conectividade. Devolve `{ status, server_version, hostname, database, execution_time_ms }`.

## Integração com o Claude Desktop

Adicione ao seu `claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "MariaDB": {
      "command": "php",
      "args": ["/caminho/absoluto/para/server.php"],
      "env": {
        "DB_HOST": "127.0.0.1",
        "DB_NAME": "minhabd",
        "DB_USER": "mcp",
        "DB_PASS": "palavra-passe"
      }
    }
  }
}
```

O ficheiro de configuração encontra-se em:
- **macOS**: `~/Library/Application Support/Claude/claude_desktop_config.json`
- **Windows**: `%APPDATA%\Claude\claude_desktop_config.json`
- **Linux**: `~/.config/Claude/claude_desktop_config.json`

## Integração com VS Code
        "DB_USER": "mcp",
        "DB_PASS": "palavra-passe"
      }
    }
  }
}
```

## Desenvolvimento

```bash
composer test   # executar suite de testes PHPUnit
composer stan   # análise estática com PHPStan (nível max)
```

## Segurança

- Nunca faça commit do ficheiro `.env` ou de credenciais no repositório.
- A ferramenta `exec` aceita SQL puro — utilize um utilizador de base de dados dedicado com apenas os privilégios de que a IA necessita.

## Registos

Todas as operações são escritas em `var/logs/mcp.log`. Defina `DB_LOG_SQL=true` para registar também as instruções SQL executadas.
