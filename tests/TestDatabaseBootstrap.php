<?php
declare(strict_types=1);

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;

/**
 * Configures an isolated datasource before the test suite can use ORM tables.
 */
final class TestDatabaseBootstrap
{
    private const ENVIRONMENT_VARIABLE = 'KUALITET_TEST_DATABASE_URL';

    public static function boot(): void
    {
        $url = getenv(self::ENVIRONMENT_VARIABLE);
        self::assertSafeUrl($url === false ? '' : $url);

        putenv('KUALITET_TEST_MODE=1');
        putenv('DATABASE_TEST_URL=' . $url);

        require dirname(__DIR__) . '/config/bootstrap.php';

        $config = ConnectionManager::parseDsn($url);
        $config['persistent'] = false;
        $config['cacheMetadata'] = false;
        $config['log'] = false;

        foreach (['default', 'test'] as $connectionName) {
            ConnectionManager::drop($connectionName);
            ConnectionManager::setConfig($connectionName, $config);
            Configure::write('Datasources.' . $connectionName, $config);
        }

        self::assertConfigured($config);
    }

    public static function assertSafeUrl(string $url): void
    {
        if ($url === '') {
            throw new RuntimeException(self::ENVIRONMENT_VARIABLE . ' must be configured for tests.');
        }

        if ($url === 'sqlite:///:memory:') {
            return;
        }

        $database = trim((string) parse_url($url, PHP_URL_PATH), '/');
        if ($database === '' || !preg_match('/(^|[_-])test($|[_-])/i', $database)) {
            throw new RuntimeException('Test datasource must target an explicitly named test database.');
        }
    }

    private static function assertConfigured(array $expected): void
    {
        foreach (['default', 'test'] as $connectionName) {
            $config = ConnectionManager::getConfig($connectionName);
            if (($config['driver'] ?? null) !== $expected['driver'] ||
                ($config['database'] ?? null) !== $expected['database']) {
                throw new RuntimeException('Test datasource configuration did not replace ' . $connectionName . '.');
            }
        }
    }
}
