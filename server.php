<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Config;
use Dotenv\Dotenv;
use Mcp\Capability\Registry\Container;
use Mcp\Server;
use Mcp\Server\Transport\StdioTransport;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

if (is_file(__DIR__ . '/.env')) {
    Dotenv::createImmutable(__DIR__)->safeLoad();
}

$config = Config::fromEnv();

$logger = new Logger('mcp');
$logger->pushHandler(new StreamHandler(__DIR__ . '/var/logs/mcp.log', Level::Notice));

$container = new Container();
$container->set(Config::class, $config);
$container->set(LoggerInterface::class, $logger);

$cache = new Psr16Cache(new FilesystemAdapter('mcp-discovery', 3600, __DIR__ . '/var/cache'));

$server = Server::builder()
    ->setServerInfo('mariadb-mcp', '2.0')
    ->setLogger($logger)
    ->setContainer($container)
    ->setDiscovery(
        basePath: __DIR__,
        scanDirs: ['src/Tools'],
        cache: $cache,
    )
    ->build();

$server->run(new StdioTransport());
