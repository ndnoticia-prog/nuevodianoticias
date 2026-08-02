<?php

declare(strict_types=1);

namespace NDCore\Tests\Unit\Config;

use NDCore\Config\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    private string $tempDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDirectory = sys_get_temp_dir() . '/nd-core-config-test-' . bin2hex(random_bytes(4));
        mkdir($this->tempDirectory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tempDirectory . '/*.php') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->tempDirectory);

        parent::tearDown();
    }

    public function test_get_and_set_support_dot_notation(): void
    {
        $config = new Config();

        $config->set('cache.driver', 'redis');
        $config->set('cache.redis.host', '127.0.0.1');

        self::assertSame('redis', $config->get('cache.driver'));
        self::assertSame('127.0.0.1', $config->get('cache.redis.host'));
        self::assertSame(['host' => '127.0.0.1'], $config->get('cache.redis'));
    }

    public function test_get_returns_default_for_missing_key(): void
    {
        $config = new Config();

        self::assertSame('fallback', $config->get('missing.key', 'fallback'));
    }

    public function test_has_distinguishes_missing_from_present(): void
    {
        $config = new Config(['flag' => false]);

        self::assertTrue($config->has('flag'));
        self::assertFalse($config->has('missing'));
    }

    public function test_load_directory_merges_php_files_under_their_filename_key(): void
    {
        file_put_contents($this->tempDirectory . '/cache.php', "<?php\nreturn ['driver' => 'transient', 'ttl' => 3600];\n");

        $config = new Config();
        $config->loadDirectory($this->tempDirectory);

        self::assertSame('transient', $config->get('cache.driver'));
        self::assertSame(3600, $config->get('cache.ttl'));
    }

    public function test_load_directory_on_missing_path_is_a_no_op(): void
    {
        $config = new Config(['existing' => true]);

        $config->loadDirectory($this->tempDirectory . '/does-not-exist');

        self::assertTrue($config->get('existing'));
    }
}
