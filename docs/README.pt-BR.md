# MCP MariaDB Server (PHP)

> 🇺🇸 [Read in English](../README.md) · 🇵🇹 [Ler em Português de Portugal](README.pt-PT.md)

Servidor MCP (Model Context Protocol) para interação de IA com bancos de dados MariaDB/MySQL.

Expõe **10 ferramentas** cobrindo execução de SQL, introspecção de schema, transações e health check.

## Funcionalidades

- Execução de SQL com **paginação automática** para consultas SELECT.
- **Descoberta de schema**: listar tabelas, descrever colunas, índices, chaves estrangeiras e DDL completo.
- **Suporte a transações**: begin / commit / rollback.
- **Health check** (`ping`) com versão do servidor e latência.
- Reconexão automática em caso de perda de conexão.
- Configuração via variáveis de ambiente.
- Logging de todas as operações em `var/logs/mcp.log`.

## Instalação

### Requisitos

- PHP 8.5+
- Composer

```bash
composer install
```

## Configuração

Copie o arquivo `.env.example` para `.env` e ajuste conforme necessário:

```bash
cp .env.example .env
```

| Variável de ambiente | Padrão      | Descrição                                      |
|----------------------|-------------|------------------------------------------------|
| `DB_HOST`            | `127.0.0.1` | Host do banco de dados                         |
| `DB_PORT`            | `3306`      | Porta do banco de dados                        |
| `DB_NAME`            | `mcp`       | Nome do banco de dados                         |
| `DB_USER`            | `mcp`       | Usuário do banco                               |
| `DB_PASS`            | *(vazio)*   | Senha do banco                                 |
| `DB_QUERY_TIMEOUT`   | `30`        | Timeout de query em segundos                   |
| `DB_MAX_ROWS`        | `500`       | Tamanho padrão de página para resultados SELECT|
| `DB_LOG_SQL`         | `false`     | Registrar SQL executado no arquivo de log      |

## Execução

```bash
composer serve
# ou
php server.php
```

O servidor é executado via **stdio** e segue o protocolo MCP.

## Ferramentas disponíveis

### `exec`
Executa qualquer consulta SQL. Consultas SELECT sem cláusula `LIMIT` são paginadas automaticamente.

| Parâmetro  | Tipo    | Obrigatório | Descrição                                       |
|------------|---------|-------------|--------------------------------------------------|
| `sql`      | string  | ✓           | Consulta SQL a ser executada                    |
| `page`     | integer |             | Número da página (base 1, padrão `1`)           |
| `pageSize` | integer |             | Linhas por página (padrão `DB_MAX_ROWS`)        |

**Resposta — SELECT:**
```json
{ "data": [...], "meta": { "page": 1, "page_size": 500, "row_count": 42, "has_more": false, "execution_time_ms": 3 } }
```
**Resposta — DML/DDL:**
```json
{ "affected": 1, "execution_time_ms": 2 }
```

### `list_tables`
Lista todas as tabelas de um banco de dados (nome, tipo, engine, contagem de linhas, comentário).

| Parâmetro  | Tipo   | Obrigatório | Descrição                              |
|------------|--------|-------------|----------------------------------------|
| `database` | string |             | Padrão: banco configurado              |

### `describe_table`
Retorna metadados das colunas: nome, tipo, nulabilidade, valor padrão, chave e comentário.

| Parâmetro  | Tipo   | Obrigatório | Descrição                              |
|------------|--------|-------------|----------------------------------------|
| `table`    | string | ✓           | Nome da tabela                        |
| `database` | string |             | Padrão: banco configurado              |

### `show_indexes`
Retorna todos os índices de uma tabela com nome, unicidade, ordem de colunas e tipo.

| Parâmetro  | Tipo   | Obrigatório | Descrição                              |
|------------|--------|-------------|----------------------------------------|
| `table`    | string | ✓           | Nome da tabela                        |
| `database` | string |             | Padrão: banco configurado              |

### `list_foreign_keys`
Retorna chaves estrangeiras com tabela/coluna referenciada e regras ON DELETE/UPDATE.

| Parâmetro  | Tipo   | Obrigatório | Descrição                                 |
|------------|--------|-------------|-------------------------------------------|
| `table`    | string |             | Filtrar por tabela; vazio = todas         |
| `database` | string |             | Padrão: banco configurado                 |

### `show_create_table`
Retorna o DDL completo (`CREATE TABLE`) de uma tabela.

| Parâmetro  | Tipo   | Obrigatório | Descrição                              |
|------------|--------|-------------|----------------------------------------|
| `table`    | string | ✓           | Nome da tabela                        |
| `database` | string |             | Padrão: banco configurado              |

### `begin_transaction`
Inicia uma transação no banco de dados.

### `commit_transaction`
Confirma a transação ativa.

### `rollback_transaction`
Desfaz a transação ativa.

### `ping`
Verifica a conectividade. Retorna `{ status, server_version, hostname, database, execution_time_ms }`.

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
        "DB_NAME": "meudb",
        "DB_USER": "mcp",
        "DB_PASS": "senha"
      }
    }
  }
}
```

O arquivo de configuração fica em:
- **macOS**: `~/Library/Application Support/Claude/claude_desktop_config.json`
- **Windows**: `%APPDATA%\Claude\claude_desktop_config.json`
- **Linux**: `~/.config/Claude/claude_desktop_config.json`

## Integração com VS Code
      "env": {
        "DB_HOST": "127.0.0.1",
        "DB_NAME": "meudb",
        "DB_USER": "mcp",
        "DB_PASS": "senha"
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

- Nunca comite o arquivo `.env` ou credenciais no repositório.
- A ferramenta `exec` aceita SQL puro — use um usuário de banco dedicado com apenas os privilégios que a IA realmente precisa.

## Logs

Todas as operações são gravadas em `var/logs/mcp.log`. Defina `DB_LOG_SQL=true` para também registrar os SQL executados.