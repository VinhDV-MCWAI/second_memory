<?php

declare(strict_types=1);

namespace Tests\Feature\Config;

use Illuminate\Support\Facades\DB;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * API-06: `config:cache` evaluates config/database.php in the CLI, so persistence must not depend on PHP_SAPI,
 * or the cached config would switch it off for every web request.
 */
final class PersistentConnectionConfigTest extends TestCase
{
    private const string ENV_KEY = 'DB_PERSISTENT';

    public function test_the_suite_does_not_use_persistent_connections(): void
    {
        $this->assertFalse(config('database.connections.pgsql.options')[PDO::ATTR_PERSISTENT]);
        $this->assertFalse(DB::connection()->getPdo()->getAttribute(PDO::ATTR_PERSISTENT));
    }

    /**
     * @return array<string, array{string|null, bool}>
     */
    public static function envValues(): array
    {
        return [
            'unset (default on)' => [null, true],
            'true' => ['true', true],
            'false' => ['false', false],
        ];
    }

    #[DataProvider('envValues')]
    public function test_the_config_file_evaluated_in_the_cli_follows_the_env(?string $value, bool $expected): void
    {
        $this->assertSame('cli', PHP_SAPI);

        $options = $this->withEnv($value, function (): array {
            /** @var array{connections: array{pgsql: array{options: array<int, bool>}}} $config */
            $config = require base_path('config/database.php');

            return $config['connections']['pgsql']['options'];
        });

        $this->assertSame($expected, $options[PDO::ATTR_PERSISTENT]);
    }

    /**
     * Runs $callback with DB_PERSISTENT set to $value (null = unset) in every place env() reads, then restores it.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function withEnv(?string $value, callable $callback): mixed
    {
        $saved = [$_SERVER[self::ENV_KEY] ?? null, $_ENV[self::ENV_KEY] ?? null, getenv(self::ENV_KEY)];
        $this->setEnv($value === null ? [null, null, false] : [$value, $value, $value]);

        try {
            return $callback();
        } finally {
            $this->setEnv($saved);
        }
    }

    /**
     * @param  array{string|null, string|null, string|false}  $values  $_SERVER, $_ENV and putenv values
     */
    private function setEnv(array $values): void
    {
        [$server, $env, $putenv] = $values;

        if ($server === null) {
            unset($_SERVER[self::ENV_KEY]);
        } else {
            $_SERVER[self::ENV_KEY] = $server;
        }

        if ($env === null) {
            unset($_ENV[self::ENV_KEY]);
        } else {
            $_ENV[self::ENV_KEY] = $env;
        }

        putenv($putenv === false ? self::ENV_KEY : self::ENV_KEY.'='.$putenv);
    }
}
